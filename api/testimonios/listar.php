<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar testimonios'], 403);
    exit;
}

try {
    // Asumiendo una tabla 'testimonios'
    $stmt = $db->query("SELECT t.id, t.nombre, t.email, t.testimonio FROM testimonios t ORDER BY t.id DESC");
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'testimonios' => $testimonios
    ]);
} catch (Exception $e) {
    error_log('Error listando testimonios: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener testimonios: ' . $e->getMessage()
    ], 500);
}
?>
