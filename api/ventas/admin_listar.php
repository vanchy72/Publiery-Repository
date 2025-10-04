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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar ventas'], 403);
    exit;
}

try {
    // Obtener parámetros de filtro
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta con joins para obtener información completa
    $whereConditions = [];
    $params = [];

    if (!empty($filtro)) {
        $whereConditions[] = "(l.titulo LIKE ? OR uc.nombre LIKE ? OR v.transaction_id LIKE ?)";
        $filtroParam = "%$filtro%";
        $params[] = $filtroParam;
        $params[] = $filtroParam;
        $params[] = $filtroParam;
    }

    if (!empty($estado)) {
        $whereConditions[] = "v.estado = ?";
        $params[] = $estado;
    }

    if (!empty($tipo)) {
        $whereConditions[] = "v.tipo = ?";
        $params[] = $tipo;
    }

    if (!empty($fechaDesde)) {
        $whereConditions[] = "DATE(v.fecha_venta) >= ?";
        $params[] = $fechaDesde;
    }

    if (!empty($fechaHasta)) {
        $whereConditions[] = "DATE(v.fecha_venta) <= ?";
        $params[] = $fechaHasta;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta principal de ventas
    $sql = "
        SELECT 
            v.id,
            v.fecha_venta,
            v.cantidad,
            v.total,
            v.transaction_id,
            v.tipo,
            v.estado,
            v.monto_autor,
            v.monto_empresa,
            v.porcentaje_autor,
            v.porcentaje_empresa,
            v.precio_venta,
            l.titulo as libro_titulo,
            ue.nombre as escritor_nombre,
            uc.nombre as comprador_nombre,
            uc.email as comprador_email,
            ua.nombre as afiliado_nombre,
            a.codigo_afiliado as afiliado_codigo
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios ue ON l.autor_id = ue.id
        INNER JOIN usuarios uc ON v.comprador_id = uc.id
        LEFT JOIN afiliados a ON v.afiliado_id = a.id
        LEFT JOIN usuarios ua ON a.usuario_id = ua.id
        $whereClause
        ORDER BY v.fecha_venta DESC
        LIMIT 1000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos de ventas
    $ventasFormateadas = array_map(function($venta) {
        return [
            'id' => (int)$venta['id'],
            'fecha_venta' => $venta['fecha_venta'],
            'cantidad' => (int)$venta['cantidad'],
            'total' => (float)$venta['total'],
            'transaction_id' => $venta['transaction_id'],
            'tipo' => $venta['tipo'],
            'estado' => $venta['estado'],
            'monto_autor' => (float)$venta['monto_autor'],
            'monto_empresa' => (float)$venta['monto_empresa'],
            'porcentaje_autor' => (float)$venta['porcentaje_autor'],
            'porcentaje_empresa' => (float)$venta['porcentaje_empresa'],
            'precio_venta' => (float)$venta['precio_venta'],
            'libro_titulo' => $venta['libro_titulo'],
            'escritor_nombre' => $venta['escritor_nombre'],
            'comprador_nombre' => $venta['comprador_nombre'],
            'comprador_email' => $venta['comprador_email'],
            'afiliado_nombre' => $venta['afiliado_nombre'],
            'afiliado_codigo' => $venta['afiliado_codigo']
        ];
    }, $ventas);

    // Calcular estadísticas
    $estadisticas = [];

    // Total de ventas (sin filtros de fecha para el total global)
    $stmt = $db->query("SELECT SUM(total) as total_ventas FROM ventas WHERE estado = 'completada'");
    $estadisticas['total_ventas'] = (float)$stmt->fetch()['total_ventas'];

    // Ventas de hoy
    $stmt = $db->query("SELECT COUNT(*) as ventas_hoy FROM ventas WHERE DATE(fecha_venta) = CURDATE() AND estado = 'completada'");
    $estadisticas['ventas_hoy'] = (int)$stmt->fetch()['ventas_hoy'];

    // Ventas del mes actual
    $stmt = $db->query("SELECT COUNT(*) as ventas_mes FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURDATE()) AND YEAR(fecha_venta) = YEAR(CURDATE()) AND estado = 'completada'");
    $estadisticas['ventas_mes'] = (int)$stmt->fetch()['ventas_mes'];

    // Comisiones pagadas (suma de todas las comisiones)
    $stmt = $db->query("SELECT SUM(monto) as comisiones_pagadas FROM comisiones WHERE estado = 'pagada'");
    $estadisticas['comisiones_pagadas'] = (float)($stmt->fetch()['comisiones_pagadas'] ?? 0);

    jsonResponse([
        'success' => true,
        'ventas' => $ventasFormateadas,
        'estadisticas' => $estadisticas,
        'total' => count($ventasFormateadas)
    ]);

} catch (Exception $e) {
    error_log('Error listando ventas para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener ventas: ' . $e->getMessage()
    ], 500);
}
?>
