<?php
// Limpiar cualquier output previo y suprimir warnings
if (ob_get_level()) {
    ob_clean();
}
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión y verificar que es admin
if (session_status() === PHP_SESSION_NONE) {
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver analíticas'], 403);
    exit;
}

try {
    $periodo = $_GET['periodo'] ?? '30'; // días
    $fechaDesde = date('Y-m-d', strtotime("-{$periodo} days"));
    $fechaHasta = date('Y-m-d');
    
    // === MÉTRICAS GENERALES ===
    
    // Usuarios registrados por período
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rol = 'afiliado' THEN 1 ELSE 0 END) as afiliados,
            SUM(CASE WHEN rol = 'escritor' THEN 1 ELSE 0 END) as escritores,
            SUM(CASE WHEN rol = 'lector' THEN 1 ELSE 0 END) as lectores,
            SUM(CASE WHEN fecha_registro >= ? THEN 1 ELSE 0 END) as nuevos_periodo
        FROM usuarios 
        WHERE estado = 'activo'
    ");
    $stmt->execute([$fechaDesde]);
    $usuarios = $stmt->fetch();
    
    // Ventas y ingresos
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_ventas,
            SUM(total) as ingresos_totales,
            AVG(total) as ticket_promedio,
            COUNT(CASE WHEN fecha_venta >= ? THEN 1 END) as ventas_periodo,
            SUM(CASE WHEN fecha_venta >= ? THEN total ELSE 0 END) as ingresos_periodo
        FROM ventas 
        WHERE estado = 'completada'
    ");
    $stmt->execute([$fechaDesde, $fechaDesde]);
    $ventas = $stmt->fetch();
    
    // Comisiones
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_comisiones,
            SUM(monto) as total_comisiones_monto,
            SUM(CASE WHEN estado = 'pagada' THEN monto ELSE 0 END) as comisiones_pagadas,
            SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as comisiones_pendientes,
            COUNT(CASE WHEN fecha_generacion >= ? THEN 1 END) as comisiones_periodo
        FROM comisiones
    ");
    $stmt->execute([$fechaDesde]);
    $comisiones = $stmt->fetch();
    
    // Libros más vendidos
    $stmt = $db->prepare("
        SELECT 
            l.titulo,
            l.precio,
            COUNT(v.id) as ventas,
            SUM(v.total) as ingresos,
            u.nombre as autor
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios u ON l.autor_id = u.id
        WHERE v.estado = 'completada' AND v.fecha_venta >= ?
        GROUP BY l.id, l.titulo, l.precio, u.nombre
        ORDER BY ventas DESC
        LIMIT 10
    ");
    $stmt->execute([$fechaDesde]);
    $libros_top = $stmt->fetchAll();
    
    // === DATOS PARA GRÁFICOS ===
    
    // Ventas por día (últimos 30 días)
    $stmt = $db->prepare("
        SELECT 
            DATE(fecha_venta) as fecha,
            COUNT(*) as ventas,
            SUM(total) as ingresos
        FROM ventas 
        WHERE fecha_venta >= ? AND estado = 'completada'
        GROUP BY DATE(fecha_venta)
        ORDER BY fecha ASC
    ");
    $stmt->execute([$fechaDesde]);
    $ventas_diarias = $stmt->fetchAll();
    
    // Registros por día
    $stmt = $db->prepare("
        SELECT 
            DATE(fecha_registro) as fecha,
            COUNT(*) as registros,
            SUM(CASE WHEN rol = 'afiliado' THEN 1 ELSE 0 END) as afiliados,
            SUM(CASE WHEN rol = 'escritor' THEN 1 ELSE 0 END) as escritores
        FROM usuarios 
        WHERE fecha_registro >= ?
        GROUP BY DATE(fecha_registro)
        ORDER BY fecha ASC
    ");
    $stmt->execute([$fechaDesde]);
    $registros_diarios = $stmt->fetchAll();
    
    // Distribución por roles
    $stmt = $db->prepare("
        SELECT 
            rol,
            COUNT(*) as cantidad,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo')), 2) as porcentaje
        FROM usuarios 
        WHERE estado = 'activo'
        GROUP BY rol
        ORDER BY cantidad DESC
    ");
    $stmt->execute();
    $distribucion_roles = $stmt->fetchAll();
    
    // Top afiliados por comisiones
    $stmt = $db->prepare("
        SELECT 
            u.nombre,
            u.email,
            a.codigo_afiliado,
            COUNT(c.id) as total_comisiones,
            SUM(c.monto) as total_ganado,
            SUM(CASE WHEN c.estado = 'pendiente' THEN c.monto ELSE 0 END) as pendiente
        FROM usuarios u
        INNER JOIN afiliados a ON u.id = a.usuario_id
        LEFT JOIN comisiones c ON u.id = c.afiliado_id
        WHERE u.rol = 'afiliado' AND c.fecha_generacion >= ?
        GROUP BY u.id, u.nombre, u.email, a.codigo_afiliado
        HAVING total_ganado > 0
        ORDER BY total_ganado DESC
        LIMIT 10
    ");
    $stmt->execute([$fechaDesde]);
    $top_afiliados = $stmt->fetchAll();
    
    // Estadísticas de campañas
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_campanas,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'enviando' THEN 1 ELSE 0 END) as enviando,
            SUM(total_enviados) as emails_enviados,
            SUM(total_abiertos) as emails_abiertos,
            AVG(CASE WHEN total_enviados > 0 THEN (total_abiertos * 100.0 / total_enviados) ELSE 0 END) as tasa_apertura_promedio
        FROM campanas
        WHERE fecha_creacion >= ?
    ");
    $stmt->execute([$fechaDesde]);
    $estadisticas_campanas = $stmt->fetch();
    
    // Rendimiento por mes (últimos 12 meses)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
            COUNT(*) as ventas,
            SUM(total) as ingresos,
            COUNT(DISTINCT libro_id) as libros_vendidos
        FROM ventas 
        WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND estado = 'completada'
        GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt->execute();
    $rendimiento_mensual = $stmt->fetchAll();
    
    // Tasas de conversión
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'afiliado' AND fecha_registro >= ?) as afiliados_registrados,
            (SELECT COUNT(DISTINCT afiliado_id) FROM comisiones WHERE fecha_generacion >= ?) as afiliados_activos,
            (SELECT COUNT(*) FROM ventas WHERE fecha_venta >= ?) as total_ventas,
            (SELECT COUNT(DISTINCT comprador_id) FROM ventas WHERE fecha_venta >= ?) as compradores_unicos
    ");
    $stmt->execute([$fechaDesde, $fechaDesde, $fechaDesde, $fechaDesde]);
    $conversiones = $stmt->fetch();
    
    // Calcular tasas
    $tasa_activacion_afiliados = $conversiones['afiliados_registrados'] > 0 ? 
        round(($conversiones['afiliados_activos'] / $conversiones['afiliados_registrados']) * 100, 2) : 0;
    
    $tasa_conversion_ventas = $conversiones['compradores_unicos'] > 0 ? 
        round(($conversiones['total_ventas'] / $conversiones['compradores_unicos']) * 100, 2) : 0;
    
    // === RESPUESTA FINAL ===
    
    jsonResponse([
        'success' => true,
        'periodo' => $periodo,
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
        'metricas_generales' => [
            'usuarios' => [
                'total' => (int)$usuarios['total'],
                'afiliados' => (int)$usuarios['afiliados'],
                'escritores' => (int)$usuarios['escritores'],
                'lectores' => (int)$usuarios['lectores'],
                'nuevos_periodo' => (int)$usuarios['nuevos_periodo']
            ],
            'ventas' => [
                'total' => (int)$ventas['total_ventas'],
                'ingresos_totales' => (float)$ventas['ingresos_totales'],
                'ticket_promedio' => (float)$ventas['ticket_promedio'],
                'ventas_periodo' => (int)$ventas['ventas_periodo'],
                'ingresos_periodo' => (float)$ventas['ingresos_periodo']
            ],
            'comisiones' => [
                'total' => (int)$comisiones['total_comisiones'],
                'monto_total' => (float)$comisiones['total_comisiones_monto'],
                'pagadas' => (float)$comisiones['comisiones_pagadas'],
                'pendientes' => (float)$comisiones['comisiones_pendientes'],
                'periodo' => (int)$comisiones['comisiones_periodo']
            ],
            'campanas' => [
                'total' => (int)$estadisticas_campanas['total_campanas'],
                'completadas' => (int)$estadisticas_campanas['completadas'],
                'emails_enviados' => (int)$estadisticas_campanas['emails_enviados'],
                'emails_abiertos' => (int)$estadisticas_campanas['emails_abiertos'],
                'tasa_apertura' => (float)$estadisticas_campanas['tasa_apertura_promedio']
            ]
        ],
        'graficos' => [
            'ventas_diarias' => $ventas_diarias,
            'registros_diarios' => $registros_diarios,
            'distribucion_roles' => $distribucion_roles,
            'rendimiento_mensual' => $rendimiento_mensual
        ],
        'rankings' => [
            'libros_top' => $libros_top,
            'afiliados_top' => $top_afiliados
        ],
        'conversiones' => [
            'tasa_activacion_afiliados' => $tasa_activacion_afiliados,
            'tasa_conversion_ventas' => $tasa_conversion_ventas,
            'afiliados_activos_pct' => $usuarios['afiliados'] > 0 ? 
                round(($conversiones['afiliados_activos'] / $usuarios['afiliados']) * 100, 2) : 0
        ]
    ]);

} catch (Exception $e) {
    error_log('Error en dashboard analytics: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error obteniendo datos de analíticas: ' . $e->getMessage()
    ], 500);
}
?>
