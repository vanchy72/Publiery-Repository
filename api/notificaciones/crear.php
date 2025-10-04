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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden crear notificaciones'], 403);
    exit;
}

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    exit;
}

// Validaciones
$required = ['usuario_id', 'titulo', 'mensaje'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(['success' => false, 'error' => "El campo '$field' es requerido"], 400);
        exit;
    }
}

// Validar que el usuario existe
$stmt = $db->prepare("SELECT id, nombre FROM usuarios WHERE id = ?");
$stmt->execute([$input['usuario_id']]);
$usuario_destino = $stmt->fetch();

if (!$usuario_destino) {
    jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    exit;
}

// Tipos válidos
$tipos_validos = ['info', 'success', 'warning', 'error', 'sistema', 'venta', 'comision', 'afiliado'];
$tipo = $input['tipo'] ?? 'info';
if (!in_array($tipo, $tipos_validos)) {
    $tipo = 'info';
}

try {
    // Preparar datos
    $datos = [
        'usuario_id' => (int)$input['usuario_id'],
        'tipo' => $tipo,
        'titulo' => trim($input['titulo']),
        'mensaje' => trim($input['mensaje']),
        'datos_adicionales' => !empty($input['datos_adicionales']) ? json_encode($input['datos_adicionales']) : null,
        'destacada' => !empty($input['destacada']) ? 1 : 0,
        'enlace' => !empty($input['enlace']) ? trim($input['enlace']) : null,
        'icono' => !empty($input['icono']) ? trim($input['icono']) : null,
        'admin_creador_id' => $_SESSION['user_id'],
        'fecha_expiracion' => !empty($input['fecha_expiracion']) ? $input['fecha_expiracion'] : null
    ];
    
    // Insertar notificación
    $sql = "
        INSERT INTO notificaciones (
            usuario_id, tipo, titulo, mensaje, datos_adicionales, 
            destacada, enlace, icono, admin_creador_id, fecha_expiracion
        ) VALUES (
            :usuario_id, :tipo, :titulo, :mensaje, :datos_adicionales,
            :destacada, :enlace, :icono, :admin_creador_id, :fecha_expiracion
        )
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($datos);
    
    $notificacion_id = $db->lastInsertId();
    
    // Si es para todos los usuarios de un tipo
    if (!empty($input['enviar_a_todos']) && !empty($input['rol_destinatario'])) {
        $rol = $input['rol_destinatario'];
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE rol = ? AND id != ?");
        $stmt->execute([$rol, $input['usuario_id']]);
        $usuarios = $stmt->fetchAll();
        
        foreach ($usuarios as $usuario) {
            $datos['usuario_id'] = $usuario['id'];
            $stmt = $db->prepare($sql);
            $stmt->execute($datos);
        }
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Notificación creada correctamente',
        'notificacion_id' => (int)$notificacion_id,
        'usuario_destino' => $usuario_destino['nombre']
    ]);

} catch (Exception $e) {
    error_log('Error creando notificación: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al crear notificación: ' . $e->getMessage()
    ], 500);
}
?>
