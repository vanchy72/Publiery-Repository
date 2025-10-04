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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver notificaciones'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de notificación requerido'], 400);
    exit;
}

try {
    // Obtener notificación completa
    $stmt = $db->prepare("
        SELECT 
            n.*,
            u.nombre as usuario_nombre,
            u.email as usuario_email,
            u.rol as usuario_rol,
            admin.nombre as admin_creador_nombre
        FROM notificaciones n
        INNER JOIN usuarios u ON n.usuario_id = u.id
        LEFT JOIN usuarios admin ON n.admin_creador_id = admin.id
        WHERE n.id = ?
    ");
    $stmt->execute([$id]);
    $notificacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notificacion) {
        jsonResponse(['success' => false, 'error' => 'Notificación no encontrada'], 404);
        exit;
    }

    // Formatear notificación
    $notificacion_formateada = [
        'id' => (int)$notificacion['id'],
        'usuario_id' => (int)$notificacion['usuario_id'],
        'usuario_nombre' => $notificacion['usuario_nombre'],
        'usuario_email' => $notificacion['usuario_email'],
        'usuario_rol' => $notificacion['usuario_rol'],
        'tipo' => $notificacion['tipo'],
        'titulo' => $notificacion['titulo'],
        'mensaje' => $notificacion['mensaje'],
        'datos_adicionales' => $notificacion['datos_adicionales'] ? json_decode($notificacion['datos_adicionales'], true) : null,
        'leida' => (bool)$notificacion['leida'],
        'destacada' => (bool)$notificacion['destacada'],
        'fecha_leida' => $notificacion['fecha_leida'],
        'enlace' => $notificacion['enlace'],
        'icono' => $notificacion['icono'],
        'admin_creador_id' => $notificacion['admin_creador_id'] ? (int)$notificacion['admin_creador_id'] : null,
        'admin_creador_nombre' => $notificacion['admin_creador_nombre'],
        'fecha_expiracion' => $notificacion['fecha_expiracion'],
        'fecha_creacion' => $notificacion['fecha_creacion'],
        'fecha_actualizacion' => $notificacion['fecha_actualizacion']
    ];

    jsonResponse([
        'success' => true,
        'notificacion' => $notificacion_formateada
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo notificación: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener notificación: ' . $e->getMessage()
    ], 500);
}
?>
