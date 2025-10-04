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

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener filtro si existe
    $filtro = $_GET['filtro'] ?? '';
    
    // Construir query para testimonios con información del usuario
    $sql = "SELECT t.id, t.testimonio, t.calificacion, t.fecha_testimonio, 
                   t.estado, t.destacado,
                   u.nombre as usuario_nombre, u.email as usuario_email,
                   l.titulo as libro_titulo
            FROM testimonios t 
            LEFT JOIN usuarios u ON t.usuario_id = u.id 
            LEFT JOIN libros l ON t.libro_id = l.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " AND (u.nombre LIKE ? OR l.titulo LIKE ? OR t.estado LIKE ? OR t.testimonio LIKE ?)";
        $search = '%' . $filtro . '%';
        $params = [$search, $search, $search, $search];
    }

    $sql .= " ORDER BY t.id DESC";

    // Ejecutar query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'testimonios' => $testimonios
    ]);

} catch (Exception $e) {
    error_log('Error listando testimonios: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener testimonios: ' . $e->getMessage()
    ], 500);
}
?>