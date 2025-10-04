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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden cambiar estados de campañas'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['estado'])) {
    jsonResponse(['success' => false, 'error' => 'ID y estado requeridos'], 400);
    exit;
}

$id = (int)$input['id'];
$nuevoEstado = $input['estado'];

$estadosValidos = ['borrador', 'programada', 'enviando', 'completada', 'pausada', 'cancelada'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    $db->beginTransaction();

    // Verificar que la campaña existe
    $stmt = $db->prepare("SELECT id, nombre, estado, audiencia_tipo FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    $campana = $stmt->fetch();

    if (!$campana) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Campaña no encontrada'], 404);
        exit;
    }

    $estadoAnterior = $campana['estado'];

    // Validar transiciones de estado
    $transicionesValidas = [
        'borrador' => ['programada', 'cancelada'],
        'programada' => ['enviando', 'pausada', 'cancelada'],
        'enviando' => ['completada', 'pausada'],
        'pausada' => ['programada', 'enviando', 'cancelada'],
        'completada' => [], // No se puede cambiar desde completada
        'cancelada' => ['borrador'] // Solo se puede volver a borrador
    ];

    if (!in_array($nuevoEstado, $transicionesValidas[$estadoAnterior])) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => "No se puede cambiar de '{$estadoAnterior}' a '{$nuevoEstado}'"], 400);
        exit;
    }

    // Acciones específicas según el nuevo estado
    $fechaInicio = null;
    $fechaFin = null;
    $totalDestinatarios = 0;

    if ($nuevoEstado === 'programada' && $estadoAnterior === 'borrador') {
        // Calcular destinatarios al programar
        $totalDestinatarios = calcularDestinatarios($db, $campana['audiencia_tipo']);
    } elseif ($nuevoEstado === 'enviando') {
        // Marcar fecha de inicio
        $fechaInicio = date('Y-m-d H:i:s');
        if ($campana['audiencia_tipo'] && !$campana['total_destinatarios']) {
            $totalDestinatarios = calcularDestinatarios($db, $campana['audiencia_tipo']);
        }
    } elseif ($nuevoEstado === 'completada') {
        // Marcar fecha de fin
        $fechaFin = date('Y-m-d H:i:s');
    }

    // Actualizar estado
    $sql = "UPDATE campanas SET estado = ?, fecha_actualizacion = NOW()";
    $params = [$nuevoEstado];

    if ($fechaInicio) {
        $sql .= ", fecha_inicio = ?";
        $params[] = $fechaInicio;
    }

    if ($fechaFin) {
        $sql .= ", fecha_fin = ?";
        $params[] = $fechaFin;
    }

    if ($totalDestinatarios > 0) {
        $sql .= ", total_destinatarios = ?";
        $params[] = $totalDestinatarios;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $db->commit();

    // Log de la acción
    error_log("Admin {$_SESSION['user_id']} cambió estado de campaña '{$campana['nombre']}' de '{$estadoAnterior}' a '{$nuevoEstado}'");

    jsonResponse([
        'success' => true,
        'message' => "Estado de la campaña cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error cambiando estado de campaña: ' . $e->getMessage());
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
