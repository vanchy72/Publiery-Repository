<?php
/**
 * Endpoint de confirmación automática de PayU
 * Recibe la confirmación en segundo plano y registra la venta
 */

require_once '../../config/payu.php';
require_once '../../config/database.php';

// Obtener datos de la confirmación de PayU
$referenceCode = $_POST['referenceCode'] ?? '';
$transactionId = $_POST['transactionId'] ?? '';
$signature = $_POST['signature'] ?? '';
$amount = $_POST['amount'] ?? '';
$currency = $_POST['currency'] ?? '';
$state = $_POST['state'] ?? '';
$extra1 = $_POST['extra1'] ?? '';

// Log de la confirmación para debugging
error_log("PayU Confirmation: " . json_encode($_POST));

// Verificar la firma de PayU
$isValidSignature = verifyPayUResponse([
    'referenceCode' => $referenceCode,
    'amount' => $amount,
    'currency' => $currency,
    'signature' => $signature
]);

if (!$isValidSignature) {
    error_log("PayU Confirmation: Firma inválida");
    http_response_code(400);
    exit('Invalid signature');
}

// Verificar que el pago fue aprobado
if ($state !== '4') {
    error_log("PayU Confirmation: Pago no aprobado, estado: $state");
    http_response_code(200);
    exit('Payment not approved');
}

// Extraer datos de la venta del campo extra1
$ventaData = json_decode($extra1, true);
if (!$ventaData) {
    error_log("PayU Confirmation: Error decodificando extra1: $extra1");
    http_response_code(400);
    exit('Invalid extra1 data');
}

$userId = intval($ventaData['user_id'] ?? 0);
$libroId = intval($ventaData['libro_id'] ?? 0);
$afiliadoId = intval($ventaData['afiliado_id'] ?? 0);

if (!$userId || !$libroId) {
    error_log("PayU Confirmation: Datos de venta incompletos");
    http_response_code(400);
    exit('Incomplete sale data');
}

try {
    // Llamar al endpoint de registro de venta
    $ventaData = [
        'libro_id' => $libroId,
        'comprador_id' => $userId,
        'afiliado_id' => $afiliadoId ?: 0,
        'total' => floatval($amount),
        'transaction_id' => $transactionId
    ];
    
    // Llamar al endpoint de registro de ventas
    $ventaResponse = registrarVenta($ventaData);
    
    if (!$ventaResponse['success']) {
        error_log("PayU Confirmation: Error registrando venta: " . $ventaResponse['error']);
        http_response_code(500);
        exit('Error registering sale');
    }
    
    error_log("PayU Confirmation: Venta registrada exitosamente - Venta ID: " . $ventaResponse['venta_id']);
    
    // Responder a PayU
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    error_log("PayU Confirmation: Error general: " . $e->getMessage());
    http_response_code(500);
    exit('General error');
}

/**
 * Función para registrar venta llamando al endpoint especializado
 */
function registrarVenta($ventaData) {
    try {
        $conn = getDBConnection();
        
        // Obtener información del libro
        $stmt = $conn->prepare("SELECT l.*, u.id as autor_id FROM libros l JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ?");
        $stmt->execute([$ventaData['libro_id']]);
        $libro = $stmt->fetch();
        
        if (!$libro) {
            return ['success' => false, 'error' => 'Libro no encontrado'];
        }
        
        $conn->beginTransaction();
        
        try {
            // Calcular distribución de ganancias
            $gananciaAutor = $ventaData['total'] * 0.30; // 30% para el autor
            $gananciaEmpresa = $ventaData['total'] * 0.25; // 25% para la empresa
            $totalComisiones = $ventaData['total'] * 0.45; // 45% para comisiones multinivel
            
            // Registrar la venta
            $stmt = $conn->prepare("
                INSERT INTO ventas (libro_id, comprador_id, afiliado_id, total, transaction_id, fecha_venta, estado)
                VALUES (?, ?, ?, ?, ?, NOW(), 'completada')
            ");
            $stmt->execute([
                $ventaData['libro_id'], 
                $ventaData['comprador_id'], 
                $ventaData['afiliado_id'], 
                $ventaData['total'], 
                $ventaData['transaction_id']
            ]);
            $ventaId = $conn->lastInsertId();
            
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
            if ($ventaData['afiliado_id']) {
                $comisionesCalculadas = calcularComisionesMultinivel($conn, $ventaData['afiliado_id'], $ventaId, $ventaData['total']); // <-- aquí
                
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
            if ($ventaData['afiliado_id']) {
                activarAfiliadoSiEsNecesario($conn, $ventaData['afiliado_id']);
            }
            
            $conn->commit();
            
            return [
                'success' => true,
                'venta_id' => $ventaId,
                'message' => 'Venta registrada exitosamente'
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log('Error registrando venta: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del servidor'];
    }
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
        
        // Registrar en log de actividad
        $stmt = $conn->prepare("
            INSERT INTO log_actividad (usuario_id, accion, detalles, fecha)
            VALUES (?, 'activacion_afiliado', 'Afiliado activado por primera compra', NOW())
        ");
        $stmt->execute([$afiliadoId]);
    }
} 