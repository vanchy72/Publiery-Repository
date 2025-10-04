<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Obtener datos
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['id']) ? intval($data['id']) : null;

    if (!$userId) {
        sendResponse(['success' => false, 'error' => 'ID de usuario requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Generar nueva contraseña
    $nuevaPassword = 'temp' . rand(1000, 9999);
    $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    
    // Actualizar contraseña
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $resultado = $stmt->execute([$hashedPassword, $userId]);

    if ($resultado) {
        sendResponse([
            'success' => true, 
            'message' => 'Contraseña restablecida correctamente',
            'nueva' => $nuevaPassword
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al restablecer contraseña'], 500);
    }

} catch (Exception $e) {
    error_log('Error al restablecer contraseña: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'error' => 'Error al restablecer contraseña: ' . $e->getMessage()
    ], 500);
}
?>