<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden actualizar campañas'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'ID de campaña requerido'], 400);
    exit;
}

$id = (int)$input['id'];
$nombre = trim($input['nombre'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$tipo = $input['tipo'] ?? '';
$audienciaTipo = $input['audiencia_tipo'] ?? '';
$contenidoAsunto = trim($input['contenido_asunto'] ?? '');
$contenidoHtml = trim($input['contenido_html'] ?? '');
$contenidoTexto = trim($input['contenido_texto'] ?? '');
$fechaProgramada = $input['fecha_programada'] ?? null;

// Validaciones básicas
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

try {
    $db->beginTransaction();

    // Verificar que la campaña existe y se puede editar
    $stmt = $db->prepare("SELECT id, nombre, estado FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    $campana = $stmt->fetch();

    if (!$campana) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Campaña no encontrada'], 404);
        exit;
    }

    // No permitir editar campañas que están enviando o completadas
    if (in_array($campana['estado'], ['enviando', 'completada'])) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'No se puede editar una campaña que está enviando o completada'], 400);
        exit;
    }

    // Validar fecha programada si se proporciona
    $fechaProgramadaFormatted = null;
    if ($fechaProgramada) {
        $dateObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaProgramada);
        if (!$dateObj) {
            $db->rollBack();
            jsonResponse(['success' => false, 'error' => 'Fecha programada inválida'], 400);
            exit;
        }
        $fechaProgramadaFormatted = $dateObj->format('Y-m-d H:i:s');
    }

    // Actualizar campaña
    $stmt = $db->prepare("
        UPDATE campanas 
        SET nombre = ?, 
            descripcion = ?, 
            tipo = ?, 
            audiencia_tipo = ?, 
            contenido_asunto = ?, 
            contenido_html = ?, 
            contenido_texto = ?, 
            fecha_programada = ?,
            fecha_actualizacion = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $nombre,
        $descripcion,
        $tipo,
        $audienciaTipo,
        $contenidoAsunto,
        $contenidoHtml,
        $contenidoTexto,
        $fechaProgramadaFormatted,
        $id
    ]);

    // Si ahora tiene fecha programada, actualizar estado y destinatarios
    if ($fechaProgramadaFormatted && $campana['estado'] === 'borrador') {
        $totalDestinatarios = calcularDestinatarios($db, $audienciaTipo);
        $stmt = $db->prepare("UPDATE campanas SET total_destinatarios = ?, estado = 'programada' WHERE id = ?");
        $stmt->execute([$totalDestinatarios, $id]);
    }

    $db->commit();

    // Log de la acción
    error_log("Admin {$_SESSION['user_id']} actualizó campaña '{$nombre}' (ID: {$id})");

    jsonResponse([
        'success' => true,
        'message' => 'Campaña actualizada correctamente'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error actualizando campaña: ' . $e->getMessage());
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
