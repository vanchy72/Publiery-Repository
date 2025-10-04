<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    http_response_code(403);
    echo 'Solo los administradores pueden exportar comisiones';
    exit;
}

try {
    // Obtener parámetros de filtro (misma lógica que admin_listar.php)
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $nivel = $_GET['nivel'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta
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

    // Consulta para exportación
    $sql = "
        SELECT 
            c.id as 'ID Comisión',
            c.venta_id as 'ID Venta',
            ua.nombre as 'Afiliado',
            af.codigo_afiliado as 'Código Afiliado',
            ua.email as 'Email Afiliado',
            c.nivel as 'Nivel',
            c.porcentaje as 'Porcentaje (%)',
            c.monto as 'Monto',
            c.estado as 'Estado',
            c.fecha_generacion as 'Fecha Generación',
            c.fecha_pago as 'Fecha Pago',
            l.titulo as 'Libro',
            v.total as 'Total Venta',
            v.fecha_venta as 'Fecha Venta',
            uc.nombre as 'Comprador',
            uc.email as 'Email Comprador'
        FROM comisiones c
        INNER JOIN afiliados af ON c.afiliado_id = af.id
        INNER JOIN usuarios ua ON af.usuario_id = ua.id
        INNER JOIN ventas v ON c.venta_id = v.id
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios uc ON v.comprador_id = uc.id
        $whereClause
        ORDER BY c.fecha_generacion DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar CSV
    $filename = 'comisiones_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (para que Excel abra correctamente los caracteres especiales)
    fwrite($output, "\xEF\xBB\xBF");

    // Escribir encabezados
    if (!empty($comisiones)) {
        fputcsv($output, array_keys($comisiones[0]), ';');
        
        // Escribir datos
        foreach ($comisiones as $comision) {
            fputcsv($output, $comision, ';');
        }
    } else {
        fputcsv($output, ['No se encontraron comisiones con los filtros aplicados'], ';');
    }

    fclose($output);

} catch (Exception $e) {
    error_log('Error exportando comisiones: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al exportar comisiones: ' . $e->getMessage();
}
?>
