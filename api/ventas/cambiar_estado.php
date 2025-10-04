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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden cambiar estados de ventas'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['estado'])) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    exit;
}

$id = (int)$input['id'];
$estado = $input['estado'];

$estadosValidos = ['completada', 'pendiente', 'cancelada'];

if (!in_array($estado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    $db->beginTransaction();

    // Verificar que la venta existe
    $stmt = $db->prepare("
        SELECT v.id, v.estado, v.total, l.titulo as libro_titulo 
        FROM ventas v 
        INNER JOIN libros l ON v.libro_id = l.id 
        WHERE v.id = ?
    ");
    $stmt->execute([$id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Venta no encontrada'], 404);
        exit;
    }

    $estadoAnterior = $venta['estado'];

    // Validar cambios de estado
    if ($estadoAnterior === $estado) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'La venta ya tiene ese estado'], 400);
        exit;
    }

    // Si se está cancelando una venta completada, verificar que no tenga comisiones pagadas
    if ($estadoAnterior === 'completada' && $estado === 'cancelada') {
        $stmt = $db->prepare("SELECT COUNT(*) as comisiones_pagadas FROM comisiones WHERE venta_id = ? AND estado = 'pagada'");
        $stmt->execute([$id]);
        $comisionesPagadas = $stmt->fetch()['comisiones_pagadas'];

        if ($comisionesPagadas > 0) {
            $db->rollBack();
            jsonResponse(['success' => false, 'error' => 'No se puede cancelar una venta con comisiones ya pagadas'], 400);
            exit;
        }
    }

    // Actualizar estado de la venta
    $stmt = $db->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    // Si se está cambiando a cancelada, actualizar también las comisiones relacionadas
    if ($estado === 'cancelada') {
        $stmt = $db->prepare("UPDATE comisiones SET estado = 'cancelada' WHERE venta_id = ? AND estado != 'pagada'");
        $stmt->execute([$id]);
    }

    // Si se está cambiando de cancelada a completada, reactivar comisiones
    if ($estadoAnterior === 'cancelada' && $estado === 'completada') {
        $stmt = $db->prepare("UPDATE comisiones SET estado = 'pendiente' WHERE venta_id = ?");
        $stmt->execute([$id]);
    }

    $db->commit();

    jsonResponse([
        'success' => true,
        'message' => "Estado de la venta '{$venta['libro_titulo']}' cambiado de '{$estadoAnterior}' a '{$estado}'"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error cambiando estado de venta: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
