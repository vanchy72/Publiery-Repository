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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de libros'], 403);
    exit;
}

// Obtener ID del libro a ver
$data = json_decode(file_get_contents('php://input'), true);
$libroId = $data['id'] ?? null;

if (!$libroId) {
    jsonResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
}

try {
    $stmt = $db->prepare("SELECT l.id, l.titulo, l.categoria, l.precio, l.estado, l.fecha_publicacion, u.nombre as autor_nombre FROM libros l JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ? LIMIT 1");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        jsonResponse(['success' => true, 'libro' => $libro]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles del libro: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles del libro: ' . $e->getMessage()
    ], 500);
}
?>
