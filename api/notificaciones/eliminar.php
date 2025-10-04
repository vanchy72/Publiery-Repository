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
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden eliminar notificaciones'], 403);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || !empty($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        // Eliminar una notificación específica
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'error' => 'ID de notificación requerido'], 400);
            exit;
        }
        
        // Verificar que la notificación existe
        $stmt = $db->prepare("SELECT id, titulo FROM notificaciones WHERE id = ?");
        $stmt->execute([$id]);
        $notificacion = $stmt->fetch();
        
        if (!$notificacion) {
            jsonResponse(['success' => false, 'error' => 'Notificación no encontrada'], 404);
            exit;
        }
        
        // Eliminar notificación
        $stmt = $db->prepare("DELETE FROM notificaciones WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Notificación eliminada correctamente',
            'notificacion_eliminada' => $notificacion['titulo']
        ]);
        
    } else {
        // Método POST para eliminaciones masivas
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
            exit;
        }
        
        if (!empty($input['eliminar_todas_leidas'])) {
            // Eliminar todas las notificaciones leídas
            $usuario_id = !empty($input['usuario_id']) ? (int)$input['usuario_id'] : null;
            
            if ($usuario_id) {
                $stmt = $db->prepare("DELETE FROM notificaciones WHERE usuario_id = ? AND leida = 1");
                $stmt->execute([$usuario_id]);
            } else {
                $stmt = $db->prepare("DELETE FROM notificaciones WHERE leida = 1");
                $stmt->execute();
            }
            
            $count = $stmt->rowCount();
            
            jsonResponse([
                'success' => true,
                'message' => "Eliminadas $count notificaciones leídas"
            ]);
            
        } elseif (!empty($input['eliminar_antiguas'])) {
            // Eliminar notificaciones antiguas (más de X días)
            $dias = !empty($input['dias']) ? (int)$input['dias'] : 30;
            
            $stmt = $db->prepare("DELETE FROM notificaciones WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$dias]);
            
            $count = $stmt->rowCount();
            
            jsonResponse([
                'success' => true,
                'message' => "Eliminadas $count notificaciones antiguas (más de $dias días)"
            ]);
            
        } elseif (!empty($input['notificaciones_ids']) && is_array($input['notificaciones_ids'])) {
            // Eliminar múltiples notificaciones específicas
            $ids = array_map('intval', $input['notificaciones_ids']);
            
            if (empty($ids)) {
                jsonResponse(['success' => false, 'error' => 'No se proporcionaron IDs válidos'], 400);
                exit;
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM notificaciones WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            $count = $stmt->rowCount();
            
            jsonResponse([
                'success' => true,
                'message' => "Eliminadas $count notificaciones"
            ]);
            
        } else {
            jsonResponse(['success' => false, 'error' => 'Parámetros inválidos'], 400);
        }
    }

} catch (Exception $e) {
    error_log('Error eliminando notificación: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al eliminar notificación: ' . $e->getMessage()
    ], 500);
}
?>
