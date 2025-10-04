<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de campañas/notificaciones'], 403);
    exit;
}

// Obtener ID de la campaña a ver
$data = json_decode(file_get_contents('php://input'), true);
$campanaId = $data['id'] ?? null;

if (!$campanaId) {
    jsonResponse(['success' => false, 'error' => 'ID de campaña/notificación requerido'], 400);
}

try {
    $stmt = $db->prepare("SELECT id, nombre, descripcion, tipo, audiencia_tipo, fecha_programada, estado FROM campanas WHERE id = ? LIMIT 1");
    $stmt->execute([$campanaId]);
    $campana = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($campana) {
        jsonResponse(['success' => true, 'campana' => $campana]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Campaña/Notificación no encontrada'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles de la campaña/notificación: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles de la campaña/notificación: ' . $e->getMessage()
    ], 500);
}
?>
