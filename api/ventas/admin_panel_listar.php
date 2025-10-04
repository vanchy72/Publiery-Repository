<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependencias
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();
    $filtro = $_GET['filtro'] ?? '';
    
    $sql = "SELECT 
                v.id,
                l.titulo as libro,
                u.nombre as autor,
                ua.nombre as comprador,
                v.total,
                v.fecha_venta,
                v.estado
            FROM ventas v
            LEFT JOIN libros l ON v.libro_id = l.id
            LEFT JOIN usuarios u ON l.autor_id = u.id  
            LEFT JOIN usuarios ua ON v.comprador_id = ua.id";
    
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE l.titulo LIKE ? OR u.nombre LIKE ? OR ua.nombre LIKE ? OR v.estado LIKE ?";
        $params = ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"];
    }
    
    $sql .= " ORDER BY v.fecha_venta DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'ventas' => $ventas,
        'total' => count($ventas),
        'mensaje' => 'Ventas cargadas correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_ventas: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'ventas' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar ventas',
        'error_detalle' => $e->getMessage()
    ]);
}
?>