<?php
// Limpiar cualquier output previo y suprimir warnings
if (ob_get_level()) {
    ob_clean();
}
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Iniciar sesión y verificar que es admin
if (session_status() === PHP_SESSION_NONE) {
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden marcar notificaciones'], 403);
    exit;
}

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    exit;
}

try {
    if (!empty($input['notificacion_id'])) {
        // Marcar una notificación específica
        $id = (int)$input['notificacion_id'];
        $leida = !empty($input['leida']) ? 1 : 0;
        
        $stmt = $db->prepare("
            UPDATE notificaciones 
            SET leida = ?, fecha_leida = ? 
            WHERE id = ?
        ");
        $fecha_leida = $leida ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$leida, $fecha_leida, $id]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Notificación no encontrada'], 404);
            exit;
        }
        
        $mensaje = $leida ? 'marcada como leída' : 'marcada como no leída';
        
    } elseif (!empty($input['marcar_todas_leidas'])) {
        // Marcar todas como leídas
        $usuario_id = !empty($input['usuario_id']) ? (int)$input['usuario_id'] : null;
        
        if ($usuario_id) {
            $stmt = $db->prepare("
                UPDATE notificaciones 
                SET leida = 1, fecha_leida = NOW() 
                WHERE usuario_id = ? AND leida = 0
            ");
            $stmt->execute([$usuario_id]);
        } else {
            $stmt = $db->prepare("
                UPDATE notificaciones 
                SET leida = 1, fecha_leida = NOW() 
                WHERE leida = 0
            ");
            $stmt->execute();
        }
        
        $count = $stmt->rowCount();
        $mensaje = "marcadas $count notificaciones como leídas";
        
    } elseif (!empty($input['notificaciones_ids']) && is_array($input['notificaciones_ids'])) {
        // Marcar múltiples notificaciones
        $ids = array_map('intval', $input['notificaciones_ids']);
        $leida = !empty($input['leida']) ? 1 : 0;
        
        if (empty($ids)) {
            jsonResponse(['success' => false, 'error' => 'No se proporcionaron IDs válidos'], 400);
            exit;
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $fecha_leida = $leida ? date('Y-m-d H:i:s') : null;
        
        $stmt = $db->prepare("
            UPDATE notificaciones 
            SET leida = ?, fecha_leida = ? 
            WHERE id IN ($placeholders)
        ");
        
        $params = [$leida, $fecha_leida];
        $params = array_merge($params, $ids);
        $stmt->execute($params);
        
        $count = $stmt->rowCount();
        $estado = $leida ? 'leídas' : 'no leídas';
        $mensaje = "marcadas $count notificaciones como $estado";
        
    } else {
        jsonResponse(['success' => false, 'error' => 'Parámetros inválidos'], 400);
        exit;
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Notificación ' . $mensaje . ' correctamente'
    ]);

} catch (Exception $e) {
    error_log('Error marcando notificación: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al marcar notificación: ' . $e->getMessage()
    ], 500);
}
?>
