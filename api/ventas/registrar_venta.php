<?php
/**
 * Endpoint para registrar venta y calcular comisiones
 * Registra la venta, calcula comisiones multinivel y distribuye ganancias
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../config/email.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validar datos de entrada
    $libroId = intval($input['libro_id'] ?? 0);
    $compradorId = intval($input['comprador_id'] ?? 0);
    $afiliadoId = intval($input['afiliado_id'] ?? 0);
    $total = floatval($input['total'] ?? 0);
    $transactionId = $input['transaction_id'] ?? '';
    
    if (!$libroId || !$compradorId || !$total) {
        jsonResponse(['error' => 'Datos de venta incompletos'], 400);
    }
    
    // Solo admin puede registrar ventas para otros
    if ($user['rol'] !== 'admin' && $compradorId !== $user['id']) {
        jsonResponse(['error' => 'No autorizado para registrar ventas para otros usuarios'], 403);
    }
    // Validar afiliado_id: solo el propio o nulo (excepto admin)
    if ($user['rol'] !== 'admin' && $afiliadoId) {
        $conn = getDBConnection();
        $stmt = $conn->prepare('SELECT id FROM afiliados WHERE usuario_id = ?');
        $stmt->execute([$user['id']]);
        $afiliado = $stmt->fetch();
        if (!$afiliado || $afiliado['id'] != $afiliadoId) {
            jsonResponse(['error' => 'No autorizado para usar ese afiliado_id'], 403);
        }
    }
    
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    try {
        // Obtener información del libro
        $stmt = $conn->prepare("SELECT l.*, u.id as autor_id FROM libros l JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ?");
        $stmt->execute([$libroId]);
        $libro = $stmt->fetch();
        
        if (!$libro) {
            throw new Exception('Libro no encontrado');
        }
        
        // Registrar la venta
        $stmt = $conn->prepare("
            INSERT INTO ventas (libro_id, comprador_id, afiliado_id, total, transaction_id, fecha_venta, estado)
            VALUES (?, ?, ?, ?, ?, NOW(), 'completada')
        ");
        $stmt->execute([$libroId, $compradorId, $afiliadoId, $total, $transactionId]);
        $ventaId = $conn->lastInsertId();
        
        // Calcular distribución de ganancias
        $gananciaAutor = $total * 0.30; // 30% para el autor
        $gananciaEmpresa = $total * 0.25; // 25% para la empresa
        $totalComisiones = $total * 0.45; // 45% para comisiones multinivel
        
        // Registrar ganancias del autor
        $stmt = $conn->prepare("
            INSERT INTO comisiones (venta_id, usuario_id, tipo, monto, porcentaje, nivel, fecha_creacion)
            VALUES (?, ?, 'autor', ?, 30, 0, NOW())
        ");
        $stmt->execute([$ventaId, $libro['autor_id'], $gananciaAutor]);
        
        // Registrar ganancia de la empresa
        $stmt = $conn->prepare("
            INSERT INTO comisiones (venta_id, usuario_id, tipo, monto, porcentaje, nivel, fecha_creacion)
            VALUES (?, 1, 'empresa', ?, 25, 0, NOW())
        ");
        $stmt->execute([$ventaId, $gananciaEmpresa]);
        
        // Calcular comisiones multinivel si hay afiliado
        if ($afiliadoId) {
            $comisionesCalculadas = calcularComisionesMultinivel($conn, $afiliadoId, $ventaId, $total); // <-- aquí
            
            // Registrar comisiones
            foreach ($comisionesCalculadas as $comision) {
                $stmt = $conn->prepare("
                    INSERT INTO comisiones (venta_id, usuario_id, tipo, monto, porcentaje, nivel, fecha_creacion)
                    VALUES (?, ?, 'afiliado', ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $ventaId,
                    $comision['usuario_id'],
                    $comision['monto'],
                    $comision['porcentaje'],
                    $comision['nivel']
                ]);
            }
        }
        
        // Activar afiliado si es su primera compra
        if ($afiliadoId) {
            $wasActivated = activarAfiliadoSiEsNecesario($conn, $afiliadoId);
            
            // Si se activó, enviar email de activación
            if ($wasActivated) {
                try {
                    // Obtener datos del afiliado para el email
                    $stmt = $conn->prepare("
                        SELECT u.nombre, u.email, a.codigo_afiliado, a.nivel 
                        FROM usuarios u 
                        JOIN afiliados a ON u.id = a.usuario_id 
                        WHERE a.id = ?
                    ");
                    $stmt->execute([$afiliadoId]);
                    $afiliadoData = $stmt->fetch();
                    
                    if ($afiliadoData) {
                        $userDataForEmail = [
                            'nombre' => $afiliadoData['nombre'],
                            'email' => $afiliadoData['email'],
                            'codigo_afiliado' => $afiliadoData['codigo_afiliado'],
                            'nivel' => $afiliadoData['nivel']
                        ];
                        
                        sendAffiliateActivationEmail($userDataForEmail);
                        logActivity($afiliadoId, 'activation_email_sent', 'Email de activación enviado');
                    }
                } catch (Exception $emailError) {
                    error_log("Error enviando email de activación: " . $emailError->getMessage());
                    // No fallar la venta si el email falla
                }
            }
        }
        
        $conn->commit();
        
        jsonResponse([
            'success' => true,
            'venta_id' => $ventaId,
            'message' => 'Venta registrada exitosamente',
            'distribucion' => [
                'autor' => $gananciaAutor,
                'empresa' => $gananciaEmpresa,
                'comisiones' => $totalComisiones
            ]
        ], 200);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Error registrando venta: ' . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}

/**
 * Calcular comisiones multinivel
 */
function calcularComisionesMultinivel($conn, $afiliadoId, $ventaId, $totalVenta) {
    $comisiones = [];
    // Nueva distribución: porcentajes sobre el total de la venta
    $porcentajes = [5, 10, 20, 5, 2.5, 2.5]; // Porcentajes por nivel
    $nivelActual = 0;
    $afiliadoActual = $afiliadoId;
    
    foreach ($porcentajes as $porcentaje) {
        if (!$afiliadoActual || $nivelActual >= 6) break;
        
        // Verificar que el afiliado esté activo
        $stmt = $conn->prepare("SELECT id, estado FROM afiliados WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$afiliadoActual]);
        $afiliado = $stmt->fetch();
        
        if ($afiliado) {
            $monto = $totalVenta * ($porcentaje / 100); // Ahora sobre el total de la venta
            $comisiones[] = [
                'usuario_id' => $afiliado['id'],
                'monto' => $monto,
                'porcentaje' => $porcentaje,
                'nivel' => $nivelActual + 1
            ];
        }
        
        // Obtener el afiliado del siguiente nivel (patrocinador)
        $stmt = $conn->prepare("SELECT patrocinador_id FROM afiliados WHERE id = ?");
        $stmt->execute([$afiliadoActual]);
        $result = $stmt->fetch();
        $afiliadoActual = $result ? $result['patrocinador_id'] : null;
        $nivelActual++;
    }
    
    return $comisiones;
}

/**
 * Activar afiliado si es su primera compra
 */
function activarAfiliadoSiEsNecesario($conn, $afiliadoId) {
    // Verificar si es la primera compra del afiliado
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_ventas 
        FROM ventas 
        WHERE afiliado_id = ? AND estado = 'completada'
    ");
    $stmt->execute([$afiliadoId]);
    $result = $stmt->fetch();
    
    if ($result['total_ventas'] == 1) {
        // Es la primera compra, activar el afiliado
        $stmt = $conn->prepare("
            UPDATE afiliados 
            SET estado = 'activo', fecha_activacion = NOW() 
            WHERE id = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$afiliadoId]);

        // También actualiza el estado en la tabla usuarios
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET estado = 'activo' 
            WHERE id = (SELECT usuario_id FROM afiliados WHERE id = ?)
        ");
        $stmt->execute([$afiliadoId]);

        // Registrar en log de actividad
        $stmt = $conn->prepare("
            INSERT INTO log_actividad (usuario_id, accion, detalles, fecha_creacion)
            VALUES (?, 'activacion_afiliado', 'Afiliado activado por primera compra', NOW())
        ");
        $stmt->execute([$afiliadoId]);
        
        return true; // Retornar true si se activó
    }
    
    return false; // Retornar false si no se activó
} 