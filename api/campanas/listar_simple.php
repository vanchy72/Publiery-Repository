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
    
    // Construir query para campañas de marketing
    $sql = "SELECT c.id, c.nombre, c.tipo, c.estado, c.fecha_inicio, 
                   c.fecha_fin, c.presupuesto, c.clicks_generados, 
                   c.conversiones, c.roi_estimado
            FROM campanas_marketing c 
            WHERE 1=1";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " AND (c.nombre LIKE ? OR c.tipo LIKE ? OR c.estado LIKE ?)";
        $search = '%' . $filtro . '%';
        $params = [$search, $search, $search];
    }

    $sql .= " ORDER BY c.id DESC";

    // Ejecutar query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'campanas' => $campanas
    ]);

} catch (Exception $e) {
    error_log('Error listando campañas: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener campañas: ' . $e->getMessage()
    ], 500);
}
?>