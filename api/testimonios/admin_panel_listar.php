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
    
    $sql = "SELECT id, nombre, email, testimonio, calificacion, estado, fecha_envio, es_destacado FROM testimonios";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE nombre LIKE ? OR email LIKE ? OR testimonio LIKE ? OR estado LIKE ?";
        $params = ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"];
    }
    
    $sql .= " ORDER BY fecha_envio DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'testimonios' => $testimonios,
        'total' => count($testimonios),
        'mensaje' => 'Testimonios cargados correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_testimonios: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'testimonios' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar testimonios',
        'error_detalle' => $e->getMessage()
    ]);
}
?>