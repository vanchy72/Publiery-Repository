<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión y verificar que es admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden crear campañas'], 403);
    exit;
}

// Procesar datos (JSON o FormData)
$input = null;
$esFormData = isset($_FILES['imagen_promocional']);

if ($esFormData) {
    // Datos vienen de FormData
    $input = $_POST;
} else {
    // Datos vienen de JSON
    $input = json_decode(file_get_contents('php://input'), true);
}

if (!$input || !isset($input['nombre']) || !isset($input['tipo'])) {
    jsonResponse(['success' => false, 'error' => 'Datos requeridos: nombre y tipo'], 400);
    exit;
}

$nombre = trim($input['nombre']);
$descripcion = trim($input['descripcion'] ?? '');
$tipo = $input['tipo'];
$audienciaTipo = $input['audiencia_tipo'] ?? 'todos';
$contenidoAsunto = trim($input['contenido_asunto'] ?? '');
$contenidoHtml = trim($input['contenido_html'] ?? '');
$contenidoTexto = trim($input['contenido_texto'] ?? '');
$fechaProgramada = $input['fecha_programada'] ?? null;

// Procesar libros seleccionados
$libroIds = null;
if (isset($input['libro_ids']) && is_array($input['libro_ids'])) {
    $libroIds = implode(',', array_filter($input['libro_ids']));
} elseif (isset($input['libro_ids']) && !empty($input['libro_ids'])) {
    $libroIds = $input['libro_ids'];
}

// Validaciones
if (empty($nombre)) {
    jsonResponse(['success' => false, 'error' => 'El nombre es requerido'], 400);
    exit;
}

$tiposValidos = ['email', 'promocion', 'afiliados', 'sistema'];
if (!in_array($tipo, $tiposValidos)) {
    jsonResponse(['success' => false, 'error' => 'Tipo de campaña inválido'], 400);
    exit;
}

$audienciasValidas = ['todos', 'afiliados', 'escritores', 'lectores'];
if (!in_array($audienciaTipo, $audienciasValidas)) {
    jsonResponse(['success' => false, 'error' => 'Tipo de audiencia inválido'], 400);
    exit;
}

// Validar fecha programada si se proporciona
if ($fechaProgramada && !DateTime::createFromFormat('Y-m-d\TH:i', $fechaProgramada)) {
    jsonResponse(['success' => false, 'error' => 'Fecha programada inválida'], 400);
    exit;
}

// Manejar subida de imagen promocional
$imagenPath = null;
if ($esFormData && isset($_FILES['imagen_promocional']) && $_FILES['imagen_promocional']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/campanas/';
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $fileType = $_FILES['imagen_promocional']['type'];
    $fileSize = $_FILES['imagen_promocional']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        jsonResponse(['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG y GIF'], 400);
        exit;
    }

    if ($fileSize > $maxSize) {
        jsonResponse(['success' => false, 'error' => 'La imagen debe ser menor a 2MB'], 400);
        exit;
    }

    // Generar nombre único
    $extension = pathinfo($_FILES['imagen_promocional']['name'], PATHINFO_EXTENSION);
    $fileName = 'campana_admin_' . time() . '_' . $_SESSION['user_id'] . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['imagen_promocional']['tmp_name'], $filePath)) {
        $imagenPath = 'uploads/campanas/' . $fileName;
    } else {
        jsonResponse(['success' => false, 'error' => 'Error al subir la imagen'], 500);
        exit;
    }
}

try {
    $db->beginTransaction();

    // Insertar nueva campaña
    $stmt = $db->prepare("
        INSERT INTO campanas 
        (nombre, descripcion, tipo, audiencia_tipo, contenido_asunto, contenido_html, contenido_texto, fecha_programada, admin_creador_id, imagen_promocional, libro_ids)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $fechaProgramadaFormatted = $fechaProgramada ? date('Y-m-d H:i:s', strtotime($fechaProgramada)) : null;
    
    $stmt->execute([
        $nombre,
        $descripcion,
        $tipo,
        $audienciaTipo,
        $contenidoAsunto,
        $contenidoHtml,
        $contenidoTexto,
        $fechaProgramadaFormatted,
        $_SESSION['user_id'],
        $imagenPath,
        $libroIds
    ]);

    $campanaId = $db->lastInsertId();

    // Si la campaña está programada, calcular destinatarios
    if ($fechaProgramadaFormatted) {
        $totalDestinatarios = calcularDestinatarios($db, $audienciaTipo);
        $stmt = $db->prepare("UPDATE campanas SET total_destinatarios = ?, estado = 'programada' WHERE id = ?");
        $stmt->execute([$totalDestinatarios, $campanaId]);
    }

    $db->commit();

    // Log de la acción
    error_log("Admin {$_SESSION['user_id']} creó campaña '{$nombre}' (ID: {$campanaId})");

    // Obtener la campaña creada para devolver datos completos
    $stmt = $db->prepare("SELECT * FROM campanas WHERE id = ?");
    $stmt->execute([$campanaId]);
    $campanaCreada = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'message' => 'Campaña creada correctamente',
        'campana_id' => (int)$campanaId,
        'campana' => [
            'id' => (int)$campanaId,
            'nombre' => $campanaCreada['nombre'],
            'descripcion' => $campanaCreada['descripcion'],
            'tipo' => $campanaCreada['tipo'],
            'imagen_promocional' => $campanaCreada['imagen_promocional'],
            'libro_ids' => $campanaCreada['libro_ids']
        ]
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error creando campaña: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}

function calcularDestinatarios($db, $audienciaTipo) {
    switch ($audienciaTipo) {
        case 'afiliados':
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'afiliado' AND estado = 'activo'");
            break;
        case 'escritores':
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'escritor' AND estado = 'activo'");
            break;
        case 'lectores':
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'lector' AND estado = 'activo'");
            break;
        default: // todos
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
    }
    
    $result = $stmt->fetch();
    return (int)($result['total'] ?? 0);
}
?>
