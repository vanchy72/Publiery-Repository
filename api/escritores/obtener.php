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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden obtener datos de escritores'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de escritor requerido'], 400);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'escritor'");
    $stmt->execute([$id]);
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escritor) {
        jsonResponse(['success' => false, 'error' => 'Escritor no encontrado'], 404);
        exit;
    }
    
    // Eliminar información sensible
    unset($escritor['password']);
    
    jsonResponse([
        'success' => true,
        'escritor' => $escritor
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo escritor: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener escritor: ' . $e->getMessage()
    ], 500);
}
?>
