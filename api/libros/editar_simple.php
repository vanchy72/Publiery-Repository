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
        sendResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    }

    if (empty($data['titulo'])) {
        sendResponse(['success' => false, 'error' => 'Título requerido'], 400);
    }

    if (empty($data['precio'])) {
        sendResponse(['success' => false, 'error' => 'Precio requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el libro existe
    $stmt = $conn->prepare("SELECT id FROM libros WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }

    // Actualizar libro
    $sql = "UPDATE libros SET titulo = ?, precio = ?, estado = ? WHERE id = ?";
    $estado = $data['estado'] ?? 'borrador';
    
    $stmt = $conn->prepare($sql);
    $resultado = $stmt->execute([
        $data['titulo'], 
        $data['precio'], 
        $estado,
        $data['id']
    ]);
    
    if ($resultado) {
        sendResponse([
            'success' => true,
            'mensaje' => 'Libro actualizado correctamente'
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al actualizar libro'], 500);
    }

} catch (Exception $e) {
    error_log('Error editando libro: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al editar libro: ' . $e->getMessage()
    ], 500);
}
?>