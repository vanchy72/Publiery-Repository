<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Verificar que es admin
requireAdmin();

// Obtener ID del usuario desde GET o POST
$userId = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['id']) ? intval($data['id']) : null;
}

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'ID de usuario requerido'], 400);
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        jsonResponse(['success' => true, 'usuario' => $usuario]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    }
} catch (Exception $e) {
    error_log('Error al obtener detalles del usuario: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalles del usuario: ' . $e->getMessage()
    ], 500);
}
?>

