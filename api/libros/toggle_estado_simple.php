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
    $estado = $input['estado'] ?? $_POST['estado'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    }

    if (!$estado) {
        sendResponse(['success' => false, 'error' => 'Estado requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el libro existe
    $stmt = $conn->prepare("SELECT id, titulo, estado FROM libros WHERE id = ?");
    $stmt->execute([$id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        sendResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }

    // Determinar nuevo estado basado en estados válidos de la BD
    $estadosValidos = ['pendiente_revision', 'en_revision', 'correccion_autor', 'aprobado_autor', 'publicado', 'rechazado'];
    
    // Lógica corregida para toggle
    if ($libro['estado'] === 'publicado') {
        // Si está publicado, lo despublicamos a 'aprobado_autor'
        $nuevoEstado = 'aprobado_autor';
    } else if (in_array($libro['estado'], ['aprobado_autor', 'pendiente_revision', 'en_revision', 'correccion_autor'])) {
        // Si está en cualquier estado previo, lo publicamos
        $nuevoEstado = 'publicado';
    } else {
        // Estado no válido para toggle
        sendResponse(['success' => false, 'error' => 'No se puede cambiar el estado actual: ' . $libro['estado']], 400);
    }

    // Preparar query de actualización según el nuevo estado
    if ($nuevoEstado === 'publicado') {
        // Si se está publicando, establecer la fecha de publicación actual
        $stmt = $conn->prepare("UPDATE libros SET estado = ?, fecha_publicacion = NOW() WHERE id = ?");
        $resultado = $stmt->execute([$nuevoEstado, $id]);
    } else {
        // Si se está despublicando, solo cambiar el estado (mantener fecha de publicación)
        $stmt = $conn->prepare("UPDATE libros SET estado = ? WHERE id = ?");
        $resultado = $stmt->execute([$nuevoEstado, $id]);
    }
    
    if ($resultado) {
        $accion = ($nuevoEstado === 'publicado') ? 'publicado' : 'despublicado';
        $mensaje = ($nuevoEstado === 'publicado') 
            ? "Libro publicado correctamente. Fecha de publicación actualizada."
            : "Libro despublicado correctamente";
            
        sendResponse([
            'success' => true,
            'mensaje' => $mensaje,
            'nuevo_estado' => $nuevoEstado,
            'fecha_actualizada' => ($nuevoEstado === 'publicado')
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al cambiar estado'], 500);
    }

} catch (Exception $e) {
    error_log('Error cambiando estado libro: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al cambiar estado: ' . $e->getMessage()
    ], 500);
}
?>