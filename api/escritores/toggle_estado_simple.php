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

    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de escritor requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el escritor existe
    $stmt = $conn->prepare("SELECT e.id, u.nombre, e.estado FROM escritores e JOIN usuarios u ON e.usuario_id = u.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escritor) {
        sendResponse(['success' => false, 'error' => 'Escritor no encontrado'], 404);
    }

    // Determinar nuevo estado
    $nuevoEstado = ($escritor['estado'] === 'activo') ? 'inactivo' : 'activo';

    // Actualizar estado en tabla escritores
    $stmt = $conn->prepare("UPDATE escritores SET estado = ? WHERE id = ?");
    $resultado = $stmt->execute([$nuevoEstado, $id]);
    
    if ($resultado) {
        sendResponse([
            'success' => true,
            'mensaje' => "Escritor {$nuevoEstado} correctamente",
            'nuevo_estado' => $nuevoEstado
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al cambiar estado'], 500);
    }

} catch (Exception $e) {
    error_log('Error cambiando estado escritor: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al cambiar estado: ' . $e->getMessage()
    ], 500);
}
?>