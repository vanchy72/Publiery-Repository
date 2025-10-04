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
                a.id,
                u.nombre,
                u.email,
                a.codigo_afiliado,
                a.comision_total,
                a.ventas_totales,
                u.estado,
                a.fecha_activacion as fecha_registro
            FROM afiliados a 
            JOIN usuarios u ON a.usuario_id = u.id";
    
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE u.nombre LIKE ? OR u.email LIKE ? OR a.codigo_afiliado LIKE ?";
        $params = ["%$filtro%", "%$filtro%", "%$filtro%"];
    }
    
    $sql .= " ORDER BY a.fecha_activacion DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'afiliados' => $afiliados,
        'total' => count($afiliados),
        'mensaje' => 'Afiliados cargados correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_afiliados: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'afiliados' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar afiliados',
        'error_detalle' => $e->getMessage()
    ]);
}
?>