<?php
/**
 * API Notificaciones para Afiliados
 * Gestiona notificaciones en tiempo real y alertas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$user = getCurrentUser();
if ($user['rol'] !== 'afiliado' && $user['rol'] !== 'admin') {
    jsonResponse(['error' => 'Acceso denegado'], 403);
}

try {
    $conn = getDBConnection();
    $userId = $user['id'];
    $method = $_SERVER['REQUEST_METHOD'];

    // Obtener ID del afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        jsonResponse(['error' => 'Afiliado no encontrado'], 404);
    }
    
    $afiliadoId = $afiliado['id'];

    switch ($method) {
        case 'GET':
            // Obtener notificaciones del afiliado
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $noLeidas = isset($_GET['no_leidas']) && $_GET['no_leidas'] === 'true';
            
            $sql = "
                SELECT 
                    n.*,
                    CASE 
                        WHEN n.tipo = 'venta' THEN 'Nueva venta generada'
                        WHEN n.tipo = 'comision' THEN 'Comisión generada'
                        WHEN n.tipo = 'retiro' THEN 'Retiro procesado'
                        WHEN n.tipo = 'nuevo_afiliado' THEN 'Nuevo afiliado en tu red'
                        WHEN n.tipo = 'meta' THEN 'Meta alcanzada'
                        WHEN n.tipo = 'sistema' THEN 'Notificación del sistema'
                        ELSE 'Notificación'
                    END as titulo_formateado
                FROM notificaciones_afiliados n
                WHERE n.afiliado_id = ?
            ";
            
            if ($noLeidas) {
                $sql .= " AND n.leida = 0";
            }
            
            $sql .= " ORDER BY n.fecha_creacion DESC LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$afiliadoId, $limit]);
            $notificaciones = $stmt->fetchAll();

            // Contar notificaciones no leídas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones_afiliados WHERE afiliado_id = ? AND leida = 0");
            $stmt->execute([$afiliadoId]);
            $noLeidasCount = $stmt->fetch()['total'];

            jsonResponse([
                'success' => true, 
                'notificaciones' => $notificaciones,
                'no_leidas' => (int)$noLeidasCount
            ]);

        case 'POST':
            // Marcar notificaciones como leídas
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['marcar_todas']) && $data['marcar_todas']) {
                // Marcar todas como leídas
                $stmt = $conn->prepare("UPDATE notificaciones_afiliados SET leida = 1 WHERE afiliado_id = ?");
                $stmt->execute([$afiliadoId]);
            } elseif (isset($data['notificacion_id'])) {
                // Marcar una específica como leída
                $stmt = $conn->prepare("UPDATE notificaciones_afiliados SET leida = 1 WHERE id = ? AND afiliado_id = ?");
                $stmt->execute([$data['notificacion_id'], $afiliadoId]);
            } else {
                jsonResponse(['error' => 'Parámetros inválidos'], 400);
            }

            jsonResponse(['success' => true, 'message' => 'Notificaciones marcadas como leídas']);

        case 'PUT':
            // Actualizar configuración de notificaciones
            $data = json_decode(file_get_contents('php://input'), true);
            
            $configuraciones = [
                'email_ventas' => $data['email_ventas'] ?? true,
                'email_comisiones' => $data['email_comisiones'] ?? true,
                'email_retiros' => $data['email_retiros'] ?? true,
                'email_nuevos_afiliados' => $data['email_nuevos_afiliados'] ?? true,
                'push_ventas' => $data['push_ventas'] ?? true,
                'push_comisiones' => $data['push_comisiones'] ?? true,
                'push_retiros' => $data['push_retiros'] ?? true,
                'push_nuevos_afiliados' => $data['push_nuevos_afiliados'] ?? true
            ];

            // Actualizar configuración en la base de datos
            $stmt = $conn->prepare("
                INSERT INTO configuracion_notificaciones_afiliados 
                (afiliado_id, email_ventas, email_comisiones, email_retiros, email_nuevos_afiliados,
                 push_ventas, push_comisiones, push_retiros, push_nuevos_afiliados, fecha_actualizacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                email_ventas = VALUES(email_ventas),
                email_comisiones = VALUES(email_comisiones),
                email_retiros = VALUES(email_retiros),
                email_nuevos_afiliados = VALUES(email_nuevos_afiliados),
                push_ventas = VALUES(push_ventas),
                push_comisiones = VALUES(push_comisiones),
                push_retiros = VALUES(push_retiros),
                push_nuevos_afiliados = VALUES(push_nuevos_afiliados),
                fecha_actualizacion = NOW()
            ");

            $stmt->execute([
                $afiliadoId,
                $configuraciones['email_ventas'],
                $configuraciones['email_comisiones'],
                $configuraciones['email_retiros'],
                $configuraciones['email_nuevos_afiliados'],
                $configuraciones['push_ventas'],
                $configuraciones['push_comisiones'],
                $configuraciones['push_retiros'],
                $configuraciones['push_nuevos_afiliados']
            ]);

            jsonResponse(['success' => true, 'configuracion' => $configuraciones]);

        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }

} catch (Exception $e) {
    error_log("Error en notificaciones afiliado: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}

// Función auxiliar para crear notificación (usada por otros endpoints)
function crearNotificacionAfiliado($afiliadoId, $tipo, $mensaje, $datos = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notificaciones_afiliados 
            (afiliado_id, tipo, mensaje, datos_adicionales, fecha_creacion, leida)
            VALUES (?, ?, ?, ?, NOW(), 0)
        ");
        
        $datosJson = $datos ? json_encode($datos) : null;
        $stmt->execute([$afiliadoId, $tipo, $mensaje, $datosJson]);
        
        return $conn->lastInsertId();
    } catch (Exception $e) {
        error_log("Error creando notificación: " . $e->getMessage());
        return false;
    }
}
?> 