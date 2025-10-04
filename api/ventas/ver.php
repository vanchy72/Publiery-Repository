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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de ventas'], 403);
    exit;
}

// Obtener ID de la venta a ver
$data = json_decode(file_get_contents('php://input'), true);
$ventaId = $data['id'] ?? null;

if (!$ventaId) {
    jsonResponse(['success' => false, 'error' => 'ID de venta requerido'], 400);
}

try {
    $stmt = $db->prepare("SELECT v.id, v.libro_id, l.titulo as libro_titulo, l.autor_id, ua.nombre as libro_autor_nombre, v.comprador_id, uc.nombre as comprador_nombre, v.precio_venta, v.fecha_venta, v.estado FROM ventas v JOIN libros l ON v.libro_id = l.id JOIN usuarios uc ON v.comprador_id = uc.id JOIN usuarios ua ON l.autor_id = ua.id WHERE v.id = ? LIMIT 1");
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($venta) {
        jsonResponse(['success' => true, 'venta' => $venta]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Venta no encontrada'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles de la venta: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles de la venta: ' . $e->getMessage()
    ], 500);
}
?>
