<?php
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar campañas'], 403);
    exit;
}

try {
    // Obtener parámetros de filtro
    $filtro = $_GET['filtro'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';

    // Construir consulta
    $whereConditions = [];
    $params = [];

    if (!empty($filtro)) {
        $whereConditions[] = "(nombre LIKE ? OR descripcion LIKE ?)";
        $filtroParam = "%$filtro%";
        $params[] = $filtroParam;
        $params[] = $filtroParam;
    }

    if (!empty($estado)) {
        $whereConditions[] = "estado = ?";
        $params[] = $estado;
    }

    if (!empty($tipo)) {
        $whereConditions[] = "tipo = ?";
        $params[] = $tipo;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta principal de campañas
    $sql = "
        SELECT 
            c.id,
            c.nombre,
            c.descripcion,
            c.tipo,
            c.estado,
            c.audiencia_tipo,
            c.fecha_programada,
            c.fecha_inicio,
            c.fecha_fin,
            c.contenido_asunto,
            c.total_destinatarios,
            c.total_enviados,
            c.total_abiertos,
            c.total_clicks,
            c.fecha_creacion,
            c.fecha_actualizacion,
            u.nombre as admin_creador
        FROM campanas c
        INNER JOIN usuarios u ON c.admin_creador_id = u.id
        $whereClause
        ORDER BY c.fecha_creacion DESC
        LIMIT 1000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos de campañas
    $campanasFormateadas = array_map(function($campana) {
        $tasaApertura = 0;
        if ($campana['total_enviados'] > 0) {
            $tasaApertura = round(($campana['total_abiertos'] / $campana['total_enviados']) * 100, 2);
        }

        return [
            'id' => (int)$campana['id'],
            'nombre' => $campana['nombre'],
            'descripcion' => $campana['descripcion'],
            'tipo' => $campana['tipo'],
            'estado' => $campana['estado'],
            'audiencia_tipo' => $campana['audiencia_tipo'],
            'fecha_programada' => $campana['fecha_programada'],
            'fecha_inicio' => $campana['fecha_inicio'],
            'fecha_fin' => $campana['fecha_fin'],
            'contenido_asunto' => $campana['contenido_asunto'],
            'total_destinatarios' => (int)$campana['total_destinatarios'],
            'total_enviados' => (int)$campana['total_enviados'],
            'total_abiertos' => (int)$campana['total_abiertos'],
            'total_clicks' => (int)$campana['total_clicks'],
            'tasa_apertura' => $tasaApertura,
            'fecha_creacion' => $campana['fecha_creacion'],
            'fecha_actualizacion' => $campana['fecha_actualizacion'],
            'admin_creador' => $campana['admin_creador']
        ];
    }, $campanas);

    // Calcular estadísticas
    $estadisticas = [];

    // Campañas activas (programadas + enviando)
    $stmt = $db->query("SELECT COUNT(*) as activas FROM campanas WHERE estado IN ('programada', 'enviando')");
    $estadisticas['activas'] = (int)($stmt->fetch()['activas'] ?? 0);

    // Total de emails enviados
    $stmt = $db->query("SELECT SUM(total_enviados) as emails_enviados FROM campanas");
    $estadisticas['emails_enviados'] = (int)($stmt->fetch()['emails_enviados'] ?? 0);

    // Tasa de apertura promedio
    $stmt = $db->query("
        SELECT 
            SUM(total_enviados) as total_enviados, 
            SUM(total_abiertos) as total_abiertos 
        FROM campanas 
        WHERE total_enviados > 0
    ");
    $totales = $stmt->fetch();
    $estadisticas['tasa_apertura'] = 0;
    if ($totales['total_enviados'] > 0) {
        $estadisticas['tasa_apertura'] = round(($totales['total_abiertos'] / $totales['total_enviados']) * 100, 2);
    }

    // Total de campañas
    $stmt = $db->query("SELECT COUNT(*) as total FROM campanas");
    $estadisticas['total'] = (int)($stmt->fetch()['total'] ?? 0);

    jsonResponse([
        'success' => true,
        'campanas' => $campanasFormateadas,
        'estadisticas' => $estadisticas,
        'total' => count($campanasFormateadas)
    ]);

} catch (Exception $e) {
    error_log('Error listando campañas para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener campañas: ' . $e->getMessage()
    ], 500);
}
?>
