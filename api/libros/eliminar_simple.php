<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    // Obtener ID del libro
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el libro existe
    $stmt = $conn->prepare("SELECT id, titulo FROM libros WHERE id = ?");
    $stmt->execute([$id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        sendResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }

    // Cambiar estado a archivado en lugar de eliminar (para preservar historial)
    $stmt = $conn->prepare("UPDATE libros SET estado = 'archivado' WHERE id = ?");
    $resultado = $stmt->execute([$id]);
    
    if ($resultado) {
        sendResponse([
            'success' => true,
            'mensaje' => 'Libro archivado correctamente'
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al archivar libro'], 500);
    }

} catch (Exception $e) {
    error_log('Error archivando libro: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al archivar libro: ' . $e->getMessage()
    ], 500);
}
?>