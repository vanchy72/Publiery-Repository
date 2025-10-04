<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden eliminar campañas'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'ID de campaña requerido'], 400);
    exit;
}

$id = (int)$input['id'];

try {
    $db->beginTransaction();

    // Verificar que la campaña existe
    $stmt = $db->prepare("SELECT id, nombre, estado FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    $campana = $stmt->fetch();

    if (!$campana) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Campaña no encontrada'], 404);
        exit;
    }

    // No permitir eliminar campañas que están enviando
    if ($campana['estado'] === 'enviando') {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'No se puede eliminar una campaña que está enviando'], 400);
        exit;
    }

    // Verificar si tiene envíos registrados
    $stmt = $db->prepare("SELECT COUNT(*) as total_envios FROM campana_envios WHERE campana_id = ?");
    $stmt->execute([$id]);
    $totalEnvios = $stmt->fetch()['total_envios'];

    if ($totalEnvios > 0) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'No se puede eliminar una campaña que ya tiene envíos registrados'], 400);
        exit;
    }

    // Eliminar la campaña (los envíos se eliminan automáticamente por CASCADE)
    $stmt = $db->prepare("DELETE FROM campanas WHERE id = ?");
    $stmt->execute([$id]);

    $db->commit();

    // Log de la acción
    error_log("Admin {$_SESSION['user_id']} eliminó campaña '{$campana['nombre']}' (ID: {$id})");

    jsonResponse([
        'success' => true,
        'message' => "Campaña '{$campana['nombre']}' eliminada correctamente"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error eliminando campaña: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
