<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden revisar testimonios'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'ID de testimonio requerido'], 400);
    exit;
}

$id = (int)$input['id'];
$estado = $input['estado'] ?? null;
$esDestacado = isset($input['es_destacado']) ? (bool)$input['es_destacado'] : null;
$observacionesAdmin = $input['observaciones_admin'] ?? '';

// Validar estado si se proporciona
if ($estado && !in_array($estado, ['pendiente', 'aprobado', 'rechazado'])) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    $db->beginTransaction();

    // Verificar que el testimonio existe
    $stmt = $db->prepare("SELECT id, nombre, estado FROM testimonios WHERE id = ?");
    $stmt->execute([$id]);
    $testimonio = $stmt->fetch();

    if (!$testimonio) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Testimonio no encontrado'], 404);
        exit;
    }

    // Construir la consulta de actualización dinámicamente
    $updateFields = [];
    $updateParams = [];

    if ($estado !== null) {
        $updateFields[] = "estado = ?";
        $updateParams[] = $estado;
        
        // Si se está cambiando el estado, actualizar fecha de revisión y admin revisor
        $updateFields[] = "fecha_revision = NOW()";
        $updateFields[] = "admin_revisor_id = ?";
        $updateParams[] = $_SESSION['user_id'];
    }

    if ($esDestacado !== null) {
        $updateFields[] = "es_destacado = ?";
        $updateParams[] = $esDestacado ? 1 : 0;
    }

    if ($observacionesAdmin !== '') {
        $updateFields[] = "observaciones_admin = ?";
        $updateParams[] = $observacionesAdmin;
    }

    // Si no hay campos para actualizar, al menos actualizar las observaciones
    if (empty($updateFields)) {
        $updateFields[] = "observaciones_admin = ?";
        $updateParams[] = $observacionesAdmin;
    }

    // Agregar el ID al final de los parámetros
    $updateParams[] = $id;

    // Ejecutar la actualización
    $sql = "UPDATE testimonios SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($updateParams);

    $db->commit();

    // Mensaje descriptivo según la acción
    $mensaje = "Testimonio actualizado correctamente";
    if ($estado) {
        $mensaje = "Testimonio marcado como '{$estado}'";
        if ($estado === 'aprobado' && $esDestacado) {
            $mensaje .= " y destacado";
        }
    } elseif ($esDestacado !== null) {
        $mensaje = $esDestacado ? "Testimonio marcado como destacado" : "Testimonio removido de destacados";
    }

    // Log de la acción para auditoría
    error_log("Admin {$_SESSION['user_id']} revisó testimonio {$id} de '{$testimonio['nombre']}': {$mensaje}");

    jsonResponse([
        'success' => true,
        'message' => $mensaje
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error revisando testimonio: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
