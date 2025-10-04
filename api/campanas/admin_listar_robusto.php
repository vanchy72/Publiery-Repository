<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true]);
}

// Verificar autenticación de admin
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'error' => 'No autorizado'
    ], 401);
}

try {
    $conn = getDBConnection();
    
    // Verificar que es admin
    $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['rol'] !== 'admin') {
        sendJsonResponse([
            'success' => false,
            'error' => 'Acceso denegado'
        ], 403);
    }
    
    // Obtener campañas con información completa
    $stmt = $conn->query("
        SELECT 
            c.*,
            u.nombre as admin_nombre,
            COUNT(n.id) as total_notificaciones
        FROM campanas c
        LEFT JOIN usuarios u ON c.admin_creador_id = u.id
        LEFT JOIN notificaciones n ON JSON_EXTRACT(n.datos_adicionales, '$.campana_id') = c.id
        GROUP BY c.id
        ORDER BY c.fecha_creacion DESC
    ");
    
    $campanasRaw = $stmt->fetchAll();
    
    // Procesar campañas para el frontend
    $campanas = [];
    foreach ($campanasRaw as $campana) {
        $campanas[] = [
            'id' => (int)$campana['id'],
            'nombre' => $campana['nombre'],
            'descripcion' => $campana['descripcion'],
            'tipo' => $campana['tipo'],
            'estado' => $campana['estado'],
            'fecha_inicio' => $campana['fecha_creacion'],
            'fecha_creacion' => $campana['fecha_creacion'],
            'admin_creador' => $campana['admin_nombre'],
            'compartida_red' => (bool)$campana['compartida_red'],
            'total_notificaciones' => (int)$campana['total_notificaciones'],
            'roi' => '0', // Placeholder para ROI
            'imagen_promocional' => $campana['imagen_promocional'],
            'libro_ids' => $campana['libro_ids']
        ];
    }
    
    sendJsonResponse([
        'success' => true,
        'campanas' => $campanas,
        'total' => count($campanas),
        'pagina' => 1,
        'totalPaginas' => 1,
        'mensaje' => count($campanas) > 0 ? 'Campañas cargadas exitosamente' : 'No hay campañas registradas'
    ]);
    
} catch (Exception $e) {
    error_log("Error en campañas admin: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'campanas' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar campañas: ' . $e->getMessage()
    ], 500);
}
?>