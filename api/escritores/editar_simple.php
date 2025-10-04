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
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    // Obtener datos del formulario
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input ?? $_POST;
    
    // Validar campos requeridos
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'error' => 'ID de escritor requerido'], 400);
    }

    if (empty($data['nombre'])) {
        sendResponse(['success' => false, 'error' => 'Nombre requerido'], 400);
    }

    if (empty($data['email'])) {
        sendResponse(['success' => false, 'error' => 'Email requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el escritor existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'escritor'");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Escritor no encontrado'], 404);
    }

    // Verificar que el email no esté en uso por otro usuario
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$data['email'], $data['id']]);
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'El email ya está en uso por otro usuario'], 400);
    }

    // Actualizar escritor
    $sql = "UPDATE usuarios SET nombre = ?, email = ?, estado = ? WHERE id = ?";
    $estado = $data['estado'] ?? 'activo';
    
    $stmt = $conn->prepare($sql);
    $resultado = $stmt->execute([
        $data['nombre'], 
        $data['email'], 
        $estado,
        $data['id']
    ]);
    
    if ($resultado) {
        sendResponse([
            'success' => true,
            'mensaje' => 'Escritor actualizado correctamente'
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al actualizar escritor'], 500);
    }

} catch (Exception $e) {
    error_log('Error editando escritor: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al editar escritor: ' . $e->getMessage()
    ], 500);
}
?>