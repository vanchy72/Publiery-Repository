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
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    // Obtener ID del libro
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el libro existe y está archivado
    $stmt = $conn->prepare("SELECT id, titulo, estado FROM libros WHERE id = ?");
    $stmt->execute([$id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        sendResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }

    if ($libro['estado'] !== 'archivado') {
        sendResponse(['success' => false, 'error' => 'Solo se pueden eliminar definitivamente libros archivados'], 400);
    }

    // ELIMINAR DEFINITIVAMENTE (con transacción para seguridad)
    $conn->beginTransaction();
    
    try {
        // Eliminar relaciones primero (si existen tablas relacionadas)
        // Por ejemplo, si hay tabla de ventas, comentarios, etc.
        
        // Eliminar el libro
        $stmt = $conn->prepare("DELETE FROM libros WHERE id = ?");
        $resultado = $stmt->execute([$id]);
        
        if ($resultado) {
            $conn->commit();
            sendResponse([
                'success' => true,
                'mensaje' => 'Libro eliminado definitivamente'
            ]);
        } else {
            $conn->rollback();
            sendResponse(['success' => false, 'error' => 'Error al eliminar libro'], 500);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error eliminando libro definitivamente: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al eliminar libro: ' . $e->getMessage()
    ], 500);
}
?>