<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

    // Obtener ID del libro
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener datos del libro con información completa
    $sql = "SELECT l.id, l.titulo, l.precio, l.estado, l.fecha_publicacion,
                   u.nombre as autor_nombre, u.email as autor_email,
                   COUNT(v.id) as ventas_totales
            FROM libros l 
            LEFT JOIN usuarios u ON l.autor_id = u.id 
            LEFT JOIN ventas v ON l.id = v.libro_id
            WHERE l.id = ?
            GROUP BY l.id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        sendResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
    }
    
    sendResponse([
        'success' => true,
        'libro' => $libro
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo libro: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener libro: ' . $e->getMessage()
    ], 500);
}
?>