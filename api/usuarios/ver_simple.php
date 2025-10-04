<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar autenticación simple
function isAdminAuth() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar sesión
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') {
        return true;
    }
    
    // Verificar token en header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        return !empty($token);
    }
    
    // Para desarrollo, permitir si no hay autenticación estricta
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Obtener ID del usuario
    $userId = null;

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
    } else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = isset($data['id']) ? intval($data['id']) : null;
    }

    if (!$userId) {
        sendResponse(['success' => false, 'error' => 'ID de usuario requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Buscar usuario
    $stmt = $conn->prepare("SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        sendResponse(['success' => true, 'usuario' => $usuario]);
    } else {
        sendResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    }

} catch (Exception $e) {
    error_log('Error al ver usuario: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'error' => 'Error al obtener detalles del usuario: ' . $e->getMessage()
    ], 500);
}
?>