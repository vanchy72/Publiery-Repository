<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación de admin (permitir localhost para testing)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isAuthenticated = isset($_SESSION['email']) && $_SESSION['rol'] === 'admin';

if (!$isAuthenticated && !$isLocalhost) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Obtener filtros
    $estado = $_GET['estado'] ?? '';
    $busqueda = $_GET['busqueda'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Construir query base
    $whereConditions = [];
    $params = [];
    
    // Estados de revisión editorial
    $estadosRevision = [
        'pendiente_revision',
        'en_revision', 
        'correccion_autor',
        'aprobado_autor',
        'rechazado'
    ];
    
    // Si no se especifica estado, mostrar solo los que necesitan revisión
    if (empty($estado)) {
        $whereConditions[] = "l.estado IN ('" . implode("','", $estadosRevision) . "')";
    } else {
        $whereConditions[] = "l.estado = ?";
        $params[] = $estado;
    }
    
    // Filtro de búsqueda
    if (!empty($busqueda)) {
        $whereConditions[] = "(l.titulo LIKE ? OR u.nombre LIKE ? OR l.categoria LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Query principal
    $sql = "
        SELECT 
            l.id,
            l.titulo,
            l.descripcion,
            l.precio,
            l.precio_afiliado,
            l.comision_porcentaje,
            l.imagen_portada,
            l.archivo_original,
            l.archivo_editado,
            l.estado,
            l.comentarios_editorial,
            l.categoria,
            l.fecha_registro,
            l.fecha_revision,
            l.fecha_aprobacion_autor,
            l.fecha_publicacion,
            l.isbn,
            u.nombre as autor_nombre,
            u.email as autor_email,
            u.id as autor_id,
            (SELECT COUNT(*) FROM ventas v WHERE v.libro_id = l.id) as total_ventas,
            (SELECT SUM(v.total) FROM ventas v WHERE v.libro_id = l.id) as ingresos_totales
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        $whereClause
        ORDER BY 
            CASE l.estado 
                WHEN 'pendiente_revision' THEN 1
                WHEN 'en_revision' THEN 2
                WHEN 'correccion_autor' THEN 3
                WHEN 'aprobado_autor' THEN 4
                ELSE 5
            END,
            l.fecha_registro DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $countSql = "
        SELECT COUNT(*) as total
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        $whereClause
    ";
    
    $countParams = array_slice($params, 0, -2); // Remover limit y offset
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener estadísticas de revisión
    $statsSql = "
        SELECT 
            estado,
            COUNT(*) as cantidad
        FROM libros 
        WHERE estado IN ('" . implode("','", $estadosRevision) . "')
        GROUP BY estado
    ";
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute();
    $estadisticas = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear estadísticas
    $stats = [];
    foreach ($estadisticas as $stat) {
        $stats[$stat['estado']] = (int)$stat['cantidad'];
    }
    
    // Formatear datos de libros
    foreach ($libros as &$libro) {
        $libro['precio'] = number_format($libro['precio'], 0, ',', '.');
        $libro['precio_afiliado'] = number_format($libro['precio_afiliado'], 0, ',', '.');
        $libro['ingresos_totales'] = $libro['ingresos_totales'] ? number_format($libro['ingresos_totales'], 0, ',', '.') : '0';
        $libro['fecha_registro'] = $libro['fecha_registro'] ? date('d/m/Y H:i', strtotime($libro['fecha_registro'])) : null;
        $libro['fecha_revision'] = $libro['fecha_revision'] ? date('d/m/Y H:i', strtotime($libro['fecha_revision'])) : null;
        $libro['fecha_aprobacion_autor'] = $libro['fecha_aprobacion_autor'] ? date('d/m/Y H:i', strtotime($libro['fecha_aprobacion_autor'])) : null;
        $libro['fecha_publicacion'] = $libro['fecha_publicacion'] ? date('d/m/Y H:i', strtotime($libro['fecha_publicacion'])) : null;
        
        // Determinar días en estado actual
        $fechaBase = $libro['fecha_revision'] ?: $libro['fecha_registro'];
        if ($fechaBase) {
            $diasEnEstado = floor((time() - strtotime($fechaBase)) / (24 * 60 * 60));
            $libro['dias_en_estado'] = $diasEnEstado;
        } else {
            $libro['dias_en_estado'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'libros' => $libros,
        'total' => (int)$totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalRecords / $limit),
        'estadisticas' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
