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
    echo 'Solo los administradores pueden exportar ventas';
    exit;
}

try {
    // Obtener parámetros de filtro (misma lógica que admin_listar.php)
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta
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

    // Consulta para exportación
    $sql = "
        SELECT 
            v.id as 'ID Venta',
            v.fecha_venta as 'Fecha',
            l.titulo as 'Libro',
            ue.nombre as 'Autor',
            uc.nombre as 'Comprador',
            uc.email as 'Email Comprador',
            COALESCE(ua.nombre, 'Sin afiliado') as 'Afiliado',
            COALESCE(a.codigo_afiliado, 'N/A') as 'Código Afiliado',
            v.tipo as 'Tipo',
            v.estado as 'Estado',
            v.cantidad as 'Cantidad',
            v.total as 'Total',
            v.monto_autor as 'Monto Autor',
            v.monto_empresa as 'Monto Empresa',
            v.transaction_id as 'Transaction ID'
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios ue ON l.autor_id = ue.id
        INNER JOIN usuarios uc ON v.comprador_id = uc.id
        LEFT JOIN afiliados a ON v.afiliado_id = a.id
        LEFT JOIN usuarios ua ON a.usuario_id = ua.id
        $whereClause
        ORDER BY v.fecha_venta DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar CSV (más simple que Excel verdadero)
    $filename = 'ventas_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (para que Excel abra correctamente los caracteres especiales)
    fwrite($output, "\xEF\xBB\xBF");

    // Escribir encabezados
    if (!empty($ventas)) {
        fputcsv($output, array_keys($ventas[0]), ';');
        
        // Escribir datos
        foreach ($ventas as $venta) {
            fputcsv($output, $venta, ';');
        }
    } else {
        fputcsv($output, ['No se encontraron ventas con los filtros aplicados'], ';');
    }

    fclose($output);

} catch (Exception $e) {
    error_log('Error exportando ventas: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al exportar ventas: ' . $e->getMessage();
}
?>
