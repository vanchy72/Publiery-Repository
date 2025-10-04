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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de escritores'], 403);
    exit;
}

// Obtener ID del escritor a ver
$data = json_decode(file_get_contents('php://input'), true);
$escritorId = $data['id'] ?? null;

if (!$escritorId) {
    jsonResponse(['success' => false, 'error' => 'ID de escritor requerido'], 400);
}

try {
    $stmt = $db->prepare("SELECT id, nombre, email, estado, fecha_registro, (SELECT COUNT(*) FROM libros WHERE autor_id = usuarios.id) as publicaciones FROM usuarios WHERE id = ? AND rol = 'escritor' LIMIT 1");
    $stmt->execute([$escritorId]);
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($escritor) {
        jsonResponse(['success' => true, 'escritor' => $escritor]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Escritor no encontrado'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles del escritor: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles del escritor: ' . $e->getMessage()
    ], 500);
}
?>
