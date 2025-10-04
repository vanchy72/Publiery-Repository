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
                e.id,
                u.nombre,
                u.email,
                e.estado,
                e.fecha_activacion,
                COUNT(l.id) as total_libros
            FROM escritores e
            JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN libros l ON l.autor_id = u.id";
    
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE u.nombre LIKE ? OR u.email LIKE ? OR e.estado LIKE ?";
        $params = ["%$filtro%", "%$filtro%", "%$filtro%"];
    }
    
    $sql .= " GROUP BY e.id ORDER BY e.fecha_activacion DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'escritores' => $escritores,
        'total' => count($escritores),
        'mensaje' => 'Escritores cargados correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_escritores: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'escritores' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar escritores',
        'error_detalle' => $e->getMessage()
    ]);
}
?>