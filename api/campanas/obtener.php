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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver campañas'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de campaña requerido'], 400);
    exit;
}

try {
    // Obtener información completa de la campaña
    $stmt = $db->prepare("
        SELECT 
            c.*,
            u.nombre as admin_creador
        FROM campanas c
        INNER JOIN usuarios u ON c.admin_creador_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $campana = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campana) {
        jsonResponse(['success' => false, 'error' => 'Campaña no encontrada'], 404);
        exit;
    }

    // Formatear datos de la campaña
    $campanaFormateada = [
        'id' => (int)$campana['id'],
        'nombre' => $campana['nombre'],
        'descripcion' => $campana['descripcion'],
        'tipo' => $campana['tipo'],
        'estado' => $campana['estado'],
        'audiencia_tipo' => $campana['audiencia_tipo'],
        'audiencia_filtros' => $campana['audiencia_filtros'] ? json_decode($campana['audiencia_filtros'], true) : null,
        'fecha_programada' => $campana['fecha_programada'],
        'fecha_inicio' => $campana['fecha_inicio'],
        'fecha_fin' => $campana['fecha_fin'],
        'contenido_asunto' => $campana['contenido_asunto'],
        'contenido_html' => $campana['contenido_html'],
        'contenido_texto' => $campana['contenido_texto'],
        'total_destinatarios' => (int)$campana['total_destinatarios'],
        'total_enviados' => (int)$campana['total_enviados'],
        'total_abiertos' => (int)$campana['total_abiertos'],
        'total_clicks' => (int)$campana['total_clicks'],
        'fecha_creacion' => $campana['fecha_creacion'],
        'fecha_actualizacion' => $campana['fecha_actualizacion'],
        'admin_creador' => $campana['admin_creador']
    ];

    jsonResponse([
        'success' => true,
        'campana' => $campanaFormateada
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo campaña: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener campaña: ' . $e->getMessage()
    ], 500);
}
?>
