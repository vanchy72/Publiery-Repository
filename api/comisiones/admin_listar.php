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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar comisiones'], 403);
    exit;
}

try {
    // Obtener parámetros de filtro
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $nivel = $_GET['nivel'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta con joins para obtener información completa
    $whereConditions = [];
    $params = [];

    if (!empty($filtro)) {
        $whereConditions[] = "(ua.nombre LIKE ? OR af.codigo_afiliado LIKE ? OR v.id LIKE ?)";
        $filtroParam = "%$filtro%";
        $params[] = $filtroParam;
        $params[] = $filtroParam;
        $params[] = $filtroParam;
    }

    if (!empty($estado)) {
        $whereConditions[] = "c.estado = ?";
        $params[] = $estado;
    }

    if (!empty($nivel)) {
        $whereConditions[] = "c.nivel = ?";
        $params[] = $nivel;
    }

    if (!empty($fechaDesde)) {
        $whereConditions[] = "DATE(c.fecha_generacion) >= ?";
        $params[] = $fechaDesde;
    }

    if (!empty($fechaHasta)) {
        $whereConditions[] = "DATE(c.fecha_generacion) <= ?";
        $params[] = $fechaHasta;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta principal de comisiones
    $sql = "
        SELECT 
            c.id,
            c.venta_id,
            c.nivel,
            c.porcentaje,
            c.monto,
            c.estado,
            c.fecha_generacion,
            c.fecha_pago,
            ua.nombre as afiliado_nombre,
            af.codigo_afiliado,
            l.titulo as libro_titulo,
            v.total as venta_total
        FROM comisiones c
        INNER JOIN afiliados af ON c.afiliado_id = af.id
        INNER JOIN usuarios ua ON af.usuario_id = ua.id
        INNER JOIN ventas v ON c.venta_id = v.id
        INNER JOIN libros l ON v.libro_id = l.id
        $whereClause
        ORDER BY c.fecha_generacion DESC
        LIMIT 1000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos de comisiones
    $comisionesFormateadas = array_map(function($comision) {
        return [
            'id' => (int)$comision['id'],
            'venta_id' => (int)$comision['venta_id'],
            'nivel' => (int)$comision['nivel'],
            'porcentaje' => (float)$comision['porcentaje'],
            'monto' => (float)$comision['monto'],
            'estado' => $comision['estado'],
            'fecha_generacion' => $comision['fecha_generacion'],
            'fecha_pago' => $comision['fecha_pago'],
            'afiliado_nombre' => $comision['afiliado_nombre'],
            'codigo_afiliado' => $comision['codigo_afiliado'],
            'libro_titulo' => $comision['libro_titulo'],
            'venta_total' => (float)$comision['venta_total']
        ];
    }, $comisiones);

    // Calcular estadísticas
    $estadisticas = [];

    // Comisiones pendientes
    $stmt = $db->query("SELECT SUM(monto) as comisiones_pendientes FROM comisiones WHERE estado = 'pendiente'");
    $estadisticas['comisiones_pendientes'] = (float)($stmt->fetch()['comisiones_pendientes'] ?? 0);

    // Total pagado
    $stmt = $db->query("SELECT SUM(monto) as total_pagado FROM comisiones WHERE estado = 'pagada'");
    $estadisticas['total_pagado'] = (float)($stmt->fetch()['total_pagado'] ?? 0);

    // Afiliados con saldo pendiente
    $stmt = $db->query("
        SELECT COUNT(DISTINCT c.afiliado_id) as afiliados_con_saldo 
        FROM comisiones c 
        WHERE c.estado = 'pendiente' AND c.monto > 0
    ");
    $estadisticas['afiliados_con_saldo'] = (int)($stmt->fetch()['afiliados_con_saldo'] ?? 0);

    // Pagos este mes
    $stmt = $db->query("
        SELECT COUNT(*) as pagos_este_mes 
        FROM comisiones 
        WHERE estado = 'pagada' 
        AND MONTH(fecha_pago) = MONTH(CURDATE()) 
        AND YEAR(fecha_pago) = YEAR(CURDATE())
    ");
    $estadisticas['pagos_este_mes'] = (int)($stmt->fetch()['pagos_este_mes'] ?? 0);

    jsonResponse([
        'success' => true,
        'comisiones' => $comisionesFormateadas,
        'estadisticas' => $estadisticas,
        'total' => count($comisionesFormateadas)
    ]);

} catch (Exception $e) {
    error_log('Error listando comisiones para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener comisiones: ' . $e->getMessage()
    ], 500);
}
?>
