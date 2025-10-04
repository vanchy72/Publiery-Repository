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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden gestionar notificaciones'], 403);
    exit;
}

// Parámetros de filtrado
$filtro = $_GET['filtro'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$estado = $_GET['estado'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    // Construir consulta base
    $where_conditions = [];
    $params = [];
    
    if (!empty($filtro)) {
        $where_conditions[] = "(n.titulo LIKE ? OR n.mensaje LIKE ? OR u.nombre LIKE ?)";
        $params[] = "%$filtro%";
        $params[] = "%$filtro%";
        $params[] = "%$filtro%";
    }
    
    if (!empty($tipo)) {
        $where_conditions[] = "n.tipo = ?";
        $params[] = $tipo;
    }
    
    if (!empty($estado)) {
        if ($estado === 'leida') {
            $where_conditions[] = "n.leida = 1";
        } elseif ($estado === 'no_leida') {
            $where_conditions[] = "n.leida = 0";
        } elseif ($estado === 'destacada') {
            $where_conditions[] = "n.destacada = 1";
        }
    }
    
    if (!empty($usuario_id)) {
        $where_conditions[] = "n.usuario_id = ?";
        $params[] = $usuario_id;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Consulta principal
    $sql = "
        SELECT 
            n.*,
            u.nombre as usuario_nombre,
            u.email as usuario_email,
            u.rol as usuario_rol,
            admin.nombre as admin_creador_nombre
        FROM notificaciones n
        INNER JOIN usuarios u ON n.usuario_id = u.id
        LEFT JOIN usuarios admin ON n.admin_creador_id = admin.id
        $where_clause
        ORDER BY n.destacada DESC, n.fecha_creacion DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total para paginación
    $count_sql = "
        SELECT COUNT(*) as total
        FROM notificaciones n
        INNER JOIN usuarios u ON n.usuario_id = u.id
        LEFT JOIN usuarios admin ON n.admin_creador_id = admin.id
        $where_clause
    ";
    
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Formatear notificaciones
    $notificaciones_formateadas = array_map(function($notif) {
        return [
            'id' => (int)$notif['id'],
            'usuario_id' => (int)$notif['usuario_id'],
            'usuario_nombre' => $notif['usuario_nombre'],
            'usuario_email' => $notif['usuario_email'],
            'usuario_rol' => $notif['usuario_rol'],
            'tipo' => $notif['tipo'],
            'titulo' => $notif['titulo'],
            'mensaje' => $notif['mensaje'],
            'datos_adicionales' => $notif['datos_adicionales'] ? json_decode($notif['datos_adicionales'], true) : null,
            'leida' => (bool)$notif['leida'],
            'destacada' => (bool)$notif['destacada'],
            'fecha_leida' => $notif['fecha_leida'],
            'enlace' => $notif['enlace'],
            'icono' => $notif['icono'],
            'admin_creador_id' => $notif['admin_creador_id'] ? (int)$notif['admin_creador_id'] : null,
            'admin_creador_nombre' => $notif['admin_creador_nombre'],
            'fecha_expiracion' => $notif['fecha_expiracion'],
            'fecha_creacion' => $notif['fecha_creacion'],
            'fecha_actualizacion' => $notif['fecha_actualizacion']
        ];
    }, $notificaciones);
    
    // Estadísticas
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN leida = 0 THEN 1 ELSE 0 END) as no_leidas,
            SUM(CASE WHEN destacada = 1 THEN 1 ELSE 0 END) as destacadas,
            COUNT(DISTINCT usuario_id) as usuarios_con_notificaciones
        FROM notificaciones
    ";
    
    $stmt = $db->prepare($stats_sql);
    $stmt->execute();
    $estadisticas = $stmt->fetch();
    
    jsonResponse([
        'success' => true,
        'notificaciones' => $notificaciones_formateadas,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ],
        'estadisticas' => [
            'total' => (int)$estadisticas['total'],
            'no_leidas' => (int)$estadisticas['no_leidas'],
            'destacadas' => (int)$estadisticas['destacadas'],
            'usuarios_con_notificaciones' => (int)$estadisticas['usuarios_con_notificaciones']
        ],
        'total' => (int)$total
    ]);

} catch (Exception $e) {
    error_log('Error listando notificaciones: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener notificaciones: ' . $e->getMessage()
    ], 500);
}
?>
