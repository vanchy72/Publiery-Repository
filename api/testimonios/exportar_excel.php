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
    echo 'Solo los administradores pueden exportar testimonios';
    exit;
}

try {
    // Obtener parámetros de filtro (misma lógica que admin_listar.php)
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $calificacion = $_GET['calificacion'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta
    $whereConditions = [];
    $params = [];

    if (!empty($filtro)) {
        $whereConditions[] = "(nombre LIKE ? OR email LIKE ? OR testimonio LIKE ?)";
        $filtroParam = "%$filtro%";
        $params[] = $filtroParam;
        $params[] = $filtroParam;
        $params[] = $filtroParam;
    }

    if (!empty($estado)) {
        $whereConditions[] = "estado = ?";
        $params[] = $estado;
    }

    if (!empty($calificacion)) {
        $whereConditions[] = "calificacion = ?";
        $params[] = $calificacion;
    }

    if (!empty($fechaDesde)) {
        $whereConditions[] = "DATE(fecha_envio) >= ?";
        $params[] = $fechaDesde;
    }

    if (!empty($fechaHasta)) {
        $whereConditions[] = "DATE(fecha_envio) <= ?";
        $params[] = $fechaHasta;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta para exportación
    $sql = "
        SELECT 
            id as 'ID',
            nombre as 'Nombre',
            email as 'Email',
            testimonio as 'Testimonio',
            calificacion as 'Calificación',
            estado as 'Estado',
            CASE WHEN es_destacado = 1 THEN 'Sí' ELSE 'No' END as 'Es Destacado',
            fecha_envio as 'Fecha Envío',
            fecha_revision as 'Fecha Revisión',
            observaciones_admin as 'Observaciones Admin'
        FROM testimonios
        $whereClause
        ORDER BY fecha_envio DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar CSV
    $filename = 'testimonios_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (para que Excel abra correctamente los caracteres especiales)
    fwrite($output, "\xEF\xBB\xBF");

    // Escribir encabezados
    if (!empty($testimonios)) {
        fputcsv($output, array_keys($testimonios[0]), ';');
        
        // Escribir datos
        foreach ($testimonios as $testimonio) {
            fputcsv($output, $testimonio, ';');
        }
    } else {
        fputcsv($output, ['No se encontraron testimonios con los filtros aplicados'], ';');
    }

    fclose($output);

} catch (Exception $e) {
    error_log('Error exportando testimonios: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al exportar testimonios: ' . $e->getMessage();
}
?>
