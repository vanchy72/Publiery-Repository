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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de testimonios'], 403);
    exit;
}

// Obtener ID del testimonio a ver
$data = json_decode(file_get_contents('php://input'), true);
$testimonioId = $data['id'] ?? null;

if (!$testimonioId) {
    jsonResponse(['success' => false, 'error' => 'ID de testimonio requerido'], 400);
}

try {
    $stmt = $db->prepare("SELECT t.id, t.nombre, t.email, t.testimonio FROM testimonios t WHERE t.id = ? LIMIT 1");
    $stmt->execute([$testimonioId]);
    $testimonio = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($testimonio) {
        jsonResponse(['success' => true, 'testimonio' => $testimonio]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Testimonio no encontrado'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles del testimonio: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles del testimonio: ' . $e->getMessage()
    ], 500);
}
?>
