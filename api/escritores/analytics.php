<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Obtener parámetros
    $escritor_id = isset($_GET['escritor_id']) ? $_GET['escritor_id'] : 101;
    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '12'; // meses
    
    // Métricas generales del período
    $metricas_query = "
        SELECT 
            COUNT(DISTINCT v.id) as total_ventas_periodo,
            SUM(v.monto_autor) as ganancias_periodo,
            AVG(v.monto_autor) as ganancia_promedio_periodo,
            COUNT(DISTINCT l.id) as libros_activos,
            COUNT(DISTINCT CASE WHEN v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN v.id END) as ventas_ultimo_mes,
            SUM(CASE WHEN v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN v.monto_autor ELSE 0 END) as ganancias_ultimo_mes
        FROM escritores e
        LEFT JOIN libros l ON e.usuario_id = l.autor_id AND l.estado = 'publicado'
        LEFT JOIN ventas v ON l.id = v.libro_id 
        AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL :periodo MONTH)
        WHERE e.id = :escritor_id
    ";
    
    $stmt = $pdo->prepare($metricas_query);
    $stmt->execute(['escritor_id' => $escritor_id, 'periodo' => $periodo]);
    $metricas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tendencias mensuales
    $tendencias_query = "
        SELECT 
            DATE_FORMAT(v.fecha_venta, '%Y-%m') as mes,
            COUNT(v.id) as ventas,
            SUM(v.monto_autor) as ganancias,
            COUNT(DISTINCT l.id) as libros_vendidos
        FROM ventas v
        JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL :periodo MONTH)
        GROUP BY DATE_FORMAT(v.fecha_venta, '%Y-%m')
        ORDER BY mes
    ";
    
    $stmt = $pdo->prepare($tendencias_query);
    $stmt->execute(['escritor_id' => $escritor_id, 'periodo' => $periodo]);
    $tendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis por libro
    $libros_analytics_query = "
        SELECT 
            l.id,
            l.titulo,
            l.precio,
            COUNT(v.id) as ventas_totales,
            SUM(v.monto_autor) as ganancias_totales,
            AVG(v.monto_autor) as ganancia_promedio,
            COUNT(CASE WHEN v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN v.id END) as ventas_ultimo_mes,
            SUM(CASE WHEN v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN v.monto_autor ELSE 0 END) as ganancias_ultimo_mes,
            MIN(v.fecha_venta) as primera_venta,
            MAX(v.fecha_venta) as ultima_venta
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND l.estado = 'publicado'
        GROUP BY l.id
        ORDER BY ganancias_totales DESC
    ";
    
    $stmt = $pdo->prepare($libros_analytics_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $libros_analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis de categorías
    $categorias_query = "
        SELECT 
            l.categoria,
            COUNT(v.id) as ventas_categoria,
            SUM(v.monto_autor) as ganancias_categoria,
            COUNT(DISTINCT l.id) as libros_categoria
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND l.estado = 'publicado'
        GROUP BY l.categoria
        ORDER BY ganancias_categoria DESC
    ";
    
    $stmt = $pdo->prepare($categorias_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis de precios
    $precios_query = "
        SELECT 
            CASE 
                WHEN l.precio < 10000 THEN 'Menos de $10,000'
                WHEN l.precio < 25000 THEN '$10,000 - $25,000'
                WHEN l.precio < 50000 THEN '$25,000 - $50,000'
                ELSE 'Más de $50,000'
            END as rango_precio,
            COUNT(v.id) as ventas_rango,
            SUM(v.monto_autor) as ganancias_rango,
            COUNT(DISTINCT l.id) as libros_rango
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND l.estado = 'publicado'
        GROUP BY rango_precio
        ORDER BY ganancias_rango DESC
    ";
    
    $stmt = $pdo->prepare($precios_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $precios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis de días de la semana
    $dias_query = "
        SELECT 
            DAYNAME(v.fecha_venta) as dia_semana,
            COUNT(v.id) as ventas_dia,
            SUM(v.monto_autor) as ganancias_dia
        FROM ventas v
        JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL :periodo MONTH)
        GROUP BY DAYNAME(v.fecha_venta)
        ORDER BY FIELD(DAYNAME(v.fecha_venta), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
    ";
    
    $stmt = $pdo->prepare($dias_query);
    $stmt->execute(['escritor_id' => $escritor_id, 'periodo' => $periodo]);
    $dias_semana = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis de afiliados que más venden
    $afiliados_query = "
        SELECT 
            u.nombre as afiliado_nombre,
            COUNT(v.id) as ventas_afiliado,
            SUM(v.monto_autor) as ganancias_afiliado
        FROM ventas v
        JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        JOIN afiliados a ON v.afiliado_id = a.id
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE e.id = :escritor_id
        AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL :periodo MONTH)
        GROUP BY a.id, u.nombre
        ORDER BY ganancias_afiliado DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($afiliados_query);
    $stmt->execute(['escritor_id' => $escritor_id, 'periodo' => $periodo]);
    $afiliados_top = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'metricas_generales' => $metricas,
        'tendencias_mensuales' => $tendencias,
        'analisis_libros' => $libros_analytics,
        'analisis_categorias' => $categorias,
        'analisis_precios' => $precios,
        'analisis_dias_semana' => $dias_semana,
        'afiliados_top' => $afiliados_top
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?> 