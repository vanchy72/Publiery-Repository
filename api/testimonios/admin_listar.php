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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar testimonios'], 403);
    exit;
}

try {
    // Obtener parámetros de filtro
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

    // Consulta principal de testimonios
    $sql = "
        SELECT 
            id,
            nombre,
            email,
            testimonio,
            calificacion,
            estado,
            fecha_envio,
            fecha_revision,
            admin_revisor_id,
            observaciones_admin,
            es_destacado
        FROM testimonios
        $whereClause
        ORDER BY fecha_envio DESC
        LIMIT 1000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos de testimonios
    $testimoniosFormateados = array_map(function($testimonio) {
        return [
            'id' => (int)$testimonio['id'],
            'nombre' => $testimonio['nombre'],
            'email' => $testimonio['email'],
            'testimonio' => $testimonio['testimonio'],
            'calificacion' => (int)$testimonio['calificacion'],
            'estado' => $testimonio['estado'],
            'fecha_envio' => $testimonio['fecha_envio'],
            'fecha_revision' => $testimonio['fecha_revision'],
            'admin_revisor_id' => $testimonio['admin_revisor_id'] ? (int)$testimonio['admin_revisor_id'] : null,
            'observaciones_admin' => $testimonio['observaciones_admin'],
            'es_destacado' => (bool)$testimonio['es_destacado']
        ];
    }, $testimonios);

    // Calcular estadísticas
    $estadisticas = [];

    // Pendientes de revisión
    $stmt = $db->query("SELECT COUNT(*) as pendientes FROM testimonios WHERE estado = 'pendiente'");
    $estadisticas['pendientes'] = (int)($stmt->fetch()['pendientes'] ?? 0);

    // Aprobados
    $stmt = $db->query("SELECT COUNT(*) as aprobados FROM testimonios WHERE estado = 'aprobado'");
    $estadisticas['aprobados'] = (int)($stmt->fetch()['aprobados'] ?? 0);

    // Destacados
    $stmt = $db->query("SELECT COUNT(*) as destacados FROM testimonios WHERE es_destacado = 1 AND estado = 'aprobado'");
    $estadisticas['destacados'] = (int)($stmt->fetch()['destacados'] ?? 0);

    // Total
    $stmt = $db->query("SELECT COUNT(*) as total FROM testimonios");
    $estadisticas['total'] = (int)($stmt->fetch()['total'] ?? 0);

    jsonResponse([
        'success' => true,
        'testimonios' => $testimoniosFormateados,
        'estadisticas' => $estadisticas,
        'total' => count($testimoniosFormateados)
    ]);

} catch (Exception $e) {
    error_log('Error listando testimonios para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener testimonios: ' . $e->getMessage()
    ], 500);
}
?>
