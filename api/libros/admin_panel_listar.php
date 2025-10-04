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
    $estado = $_GET['estado'] ?? ''; // Nuevo parámetro para filtrar por estado exacto
    
    $sql = "SELECT 
                l.id,
                l.titulo,
                u.nombre as autor,
                l.categoria,
                l.precio,
                l.precio_afiliado,
                l.comision_porcentaje,
                l.estado,
                l.fecha_publicacion,
                l.fecha_registro
            FROM libros l 
            LEFT JOIN usuarios u ON l.autor_id = u.id";
    
    $params = [];
    $conditions = [];

    // Filtro por estado específico (tiene prioridad)
    if (!empty($estado)) {
        $conditions[] = "l.estado = ?";
        $params[] = $estado;
    }
    
    // Filtro general de búsqueda
    if (!empty($filtro) && empty($estado)) {
        $conditions[] = "(l.titulo LIKE ? OR u.nombre LIKE ? OR l.categoria LIKE ? OR l.estado LIKE ?) AND l.estado IS NOT NULL AND l.estado != ''";
        $params = array_merge($params, ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"]);
    }
    
    // Si no hay filtros específicos, excluir libros archivados y con estado vacío de la vista principal
    if (empty($estado) && empty($filtro)) {
        $conditions[] = "l.estado != 'archivado' AND l.estado IS NOT NULL AND l.estado != ''";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY l.fecha_registro DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'libros' => $libros,
        'total' => count($libros),
        'mensaje' => 'Libros cargados correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_libros: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'libros' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar libros',
        'error_detalle' => $e->getMessage()
    ]);
}
?>