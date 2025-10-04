<?php
require_once __DIR__ . '/../../config/database.php';

// Iniciar sesión y verificar que es admin
if (session_status() === PHP_SESSION_NONE) {
}

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
    echo 'Solo los administradores pueden exportar campañas';
    exit;
}

try {
    // Obtener parámetros de filtro (misma lógica que admin_listar.php)
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';

    // Construir consulta
    $whereConditions = [];
    $params = [];

    if (!empty($filtro)) {
        $whereConditions[] = "(c.nombre LIKE ? OR c.descripcion LIKE ?)";
        $filtroParam = "%$filtro%";
        $params[] = $filtroParam;
        $params[] = $filtroParam;
    }

    if (!empty($estado)) {
        $whereConditions[] = "c.estado = ?";
        $params[] = $estado;
    }

    if (!empty($tipo)) {
        $whereConditions[] = "c.tipo = ?";
        $params[] = $tipo;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta para exportación
    $sql = "
        SELECT 
            c.id as 'ID',
            c.nombre as 'Nombre',
            c.descripcion as 'Descripción',
            c.tipo as 'Tipo',
            c.estado as 'Estado',
            c.audiencia_tipo as 'Audiencia',
            c.contenido_asunto as 'Asunto',
            c.total_destinatarios as 'Total Destinatarios',
            c.total_enviados as 'Total Enviados',
            c.total_abiertos as 'Total Abiertos',
            c.total_clicks as 'Total Clicks',
            CASE 
                WHEN c.total_enviados > 0 THEN ROUND((c.total_abiertos / c.total_enviados) * 100, 2)
                ELSE 0 
            END as 'Tasa Apertura (%)',
            c.fecha_programada as 'Fecha Programada',
            c.fecha_inicio as 'Fecha Inicio',
            c.fecha_fin as 'Fecha Fin',
            c.fecha_creacion as 'Fecha Creación',
            u.nombre as 'Creado Por'
        FROM campanas c
        INNER JOIN usuarios u ON c.admin_creador_id = u.id
        $whereClause
        ORDER BY c.fecha_creacion DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar CSV
    $filename = 'campanas_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (para que Excel abra correctamente los caracteres especiales)
    fwrite($output, "\xEF\xBB\xBF");

    // Escribir encabezados
    if (!empty($campanas)) {
        fputcsv($output, array_keys($campanas[0]), ';');
        
        // Escribir datos
        foreach ($campanas as $campana) {
            fputcsv($output, $campana, ';');
        }
    } else {
        fputcsv($output, ['No se encontraron campañas con los filtros aplicados'], ';');
    }

    fclose($output);

} catch (Exception $e) {
    error_log('Error exportando campañas: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al exportar campañas: ' . $e->getMessage();
}
?>
