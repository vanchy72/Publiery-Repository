<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden generar reportes'], 403);
    exit;
}

try {
    // Obtener parámetros
    $tipo = $_GET['tipo'] ?? 'general';
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t'); // Último día del mes actual

    $reporte = [];

    switch ($tipo) {
        case 'ventas':
            $reporte = generarReporteVentas($db, $fecha_inicio, $fecha_fin);
            break;
        case 'comisiones':
            $reporte = generarReporteComisiones($db, $fecha_inicio, $fecha_fin);
            break;
        case 'libros':
            $reporte = generarReporteLibros($db);
            break;
        case 'usuarios':
            $reporte = generarReporteUsuarios($db);
            break;
        case 'general':
        default:
            $reporte = generarReporteGeneral($db, $fecha_inicio, $fecha_fin);
            break;
    }

    jsonResponse([
        'success' => true,
        'reporte' => $reporte,
        'parametros' => [
            'tipo' => $tipo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]
    ]);

} catch (Exception $e) {
    error_log('Error generando reporte: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}

function generarReporteVentas($db, $fecha_inicio, $fecha_fin) {
    // Ventas por estado
    $stmt = $db->prepare("
        SELECT estado, COUNT(*) as cantidad, SUM(precio_pagado) as total
        FROM ventas 
        WHERE fecha_venta BETWEEN ? AND ?
        GROUP BY estado
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $ventasPorEstado = $stmt->fetchAll();

    // Ventas por libro más vendido
    $stmt = $db->prepare("
        SELECT l.titulo, l.autor, COUNT(v.id) as ventas, SUM(v.precio_pagado) as ingresos
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        WHERE v.fecha_venta BETWEEN ? AND ?
        GROUP BY v.libro_id
        ORDER BY ventas DESC
        LIMIT 10
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $librosMasVendidos = $stmt->fetchAll();

    // Ventas diarias
    $stmt = $db->prepare("
        SELECT DATE(fecha_venta) as fecha, COUNT(*) as cantidad, SUM(precio_pagado) as total
        FROM ventas 
        WHERE fecha_venta BETWEEN ? AND ?
        GROUP BY DATE(fecha_venta)
        ORDER BY fecha
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $ventasDiarias = $stmt->fetchAll();

    return [
        'periodo' => "$fecha_inicio al $fecha_fin",
        'ventas_por_estado' => $ventasPorEstado,
        'libros_mas_vendidos' => $librosMasVendidos,
        'ventas_diarias' => $ventasDiarias
    ];
}

function generarReporteComisiones($db, $fecha_inicio, $fecha_fin) {
    // Comisiones por estado
    $stmt = $db->prepare("
        SELECT estado, COUNT(*) as cantidad, SUM(monto) as total
        FROM comisiones c
        INNER JOIN ventas v ON c.venta_id = v.id
        WHERE v.fecha_venta BETWEEN ? AND ?
        GROUP BY estado
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $comisionesPorEstado = $stmt->fetchAll();

    // Comisiones por tipo
    $stmt = $db->prepare("
        SELECT tipo, COUNT(*) as cantidad, SUM(monto) as total
        FROM comisiones c
        INNER JOIN ventas v ON c.venta_id = v.id
        WHERE v.fecha_venta BETWEEN ? AND ?
        GROUP BY tipo
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $comisionesPorTipo = $stmt->fetchAll();

    // Top afiliados por comisiones
    $stmt = $db->prepare("
        SELECT u.nombre, u.email, COUNT(c.id) as num_comisiones, SUM(c.monto) as total_comisiones
        FROM comisiones c
        INNER JOIN ventas v ON c.venta_id = v.id
        INNER JOIN usuarios u ON c.usuario_id = u.id
        WHERE v.fecha_venta BETWEEN ? AND ?
        GROUP BY c.usuario_id
        ORDER BY total_comisiones DESC
        LIMIT 10
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $topAfiliados = $stmt->fetchAll();

    return [
        'periodo' => "$fecha_inicio al $fecha_fin",
        'comisiones_por_estado' => $comisionesPorEstado,
        'comisiones_por_tipo' => $comisionesPorTipo,
        'top_afiliados' => $topAfiliados
    ];
}

function generarReporteLibros($db) {
    // Libros por estado
    $stmt = $db->prepare("
        SELECT estado, COUNT(*) as cantidad
        FROM libros
        GROUP BY estado
    ");
    $stmt->execute();
    $librosPorEstado = $stmt->fetchAll();

    // Libros más vendidos
    $stmt = $db->prepare("
        SELECT l.titulo, l.autor, l.estado, COUNT(v.id) as total_ventas, SUM(v.precio_pagado) as ingresos_totales
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        GROUP BY l.id
        ORDER BY total_ventas DESC
        LIMIT 10
    ");
    $stmt->execute();
    $librosPopulares = $stmt->fetchAll();

    // Libros sin ventas
    $stmt = $db->prepare("
        SELECT l.titulo, l.autor, l.precio, l.fecha_publicacion
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        WHERE v.id IS NULL AND l.estado = 'publicado'
        ORDER BY l.fecha_publicacion DESC
    ");
    $stmt->execute();
    $librosSinVentas = $stmt->fetchAll();

    return [
        'libros_por_estado' => $librosPorEstado,
        'libros_populares' => $librosPopulares,
        'libros_sin_ventas' => $librosSinVentas
    ];
}

function generarReporteUsuarios($db) {
    // Usuarios por rol
    $stmt = $db->prepare("
        SELECT rol, COUNT(*) as cantidad
        FROM usuarios
        GROUP BY rol
    ");
    $stmt->execute();
    $usuariosPorRol = $stmt->fetchAll();

    // Usuarios más activos (con más ventas como afiliados)
    $stmt = $db->prepare("
        SELECT u.nombre, u.email, u.rol, COUNT(v.id) as ventas_generadas, SUM(c.monto) as comisiones_ganadas
        FROM usuarios u
        LEFT JOIN afiliados a ON u.id = a.usuario_id
        LEFT JOIN ventas v ON a.id = v.afiliado_id
        LEFT JOIN comisiones c ON c.usuario_id = u.id
        WHERE u.rol IN ('afiliado', 'escritor')
        GROUP BY u.id
        ORDER BY ventas_generadas DESC
        LIMIT 10
    ");
    $stmt->execute();
    $usuariosActivos = $stmt->fetchAll();

    // Registros recientes
    $stmt = $db->prepare("
        SELECT nombre, email, rol, fecha_registro
        FROM usuarios
        ORDER BY fecha_registro DESC
        LIMIT 10
    ");
    $stmt->execute();
    $registrosRecientes = $stmt->fetchAll();

    return [
        'usuarios_por_rol' => $usuariosPorRol,
        'usuarios_activos' => $usuariosActivos,
        'registros_recientes' => $registrosRecientes
    ];
}

function generarReporteGeneral($db, $fecha_inicio, $fecha_fin) {
    // Resumen general
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $totalUsuarios = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM libros WHERE estado = 'publicado'");
    $stmt->execute();
    $librosPublicados = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(precio_pagado) as ingresos FROM ventas WHERE fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $ventasPeriodo = $stmt->fetch();

    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(monto) as total_monto FROM comisiones c INNER JOIN ventas v ON c.venta_id = v.id WHERE v.fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $comisionesPeriodo = $stmt->fetch();

    return [
        'periodo' => "$fecha_inicio al $fecha_fin",
        'resumen' => [
            'total_usuarios' => $totalUsuarios,
            'libros_publicados' => $librosPublicados,
            'ventas_periodo' => [
                'cantidad' => $ventasPeriodo['total'] ?? 0,
                'ingresos' => $ventasPeriodo['ingresos'] ?? 0
            ],
            'comisiones_periodo' => [
                'cantidad' => $comisionesPeriodo['total'] ?? 0,
                'monto_total' => $comisionesPeriodo['total_monto'] ?? 0
            ]
        ]
    ];
}
?>