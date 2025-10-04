<?php
/**
 * Endpoint para activar automáticamente a un afiliado tras la compra
 * Se debe llamar tras la confirmación de pago (por ejemplo, desde PayU o la tienda)
 * Recibe: user_id (o afiliado_id), venta_id (opcional)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $userId = intval($input['user_id'] ?? 0);
    $ventaId = intval($input['venta_id'] ?? 0);
    if (!$userId) {
        jsonResponse(['error' => 'Falta user_id'], 400);
    }
    // Solo admin, el propio usuario o sistemas internos pueden activar
    if ($user['rol'] !== 'admin' && $user['id'] !== $userId) {
        jsonResponse(['error' => 'No autorizado para activar este afiliado'], 403);
    }
    $conn = getDBConnection();
    // Verificar que el usuario existe y es afiliado
    $stmt = $conn->prepare("SELECT u.id, u.estado, u.rol, a.id as afiliado_id FROM usuarios u JOIN afiliados a ON u.id = a.usuario_id WHERE u.id = ? AND u.rol = 'afiliado'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado o no es afiliado'], 404);
    }
    // Verificar que la venta esté completada (si se envía venta_id)
    if ($ventaId) {
        $stmt = $conn->prepare("SELECT id, estado FROM ventas WHERE id = ? AND comprador_id = ?");
        $stmt->execute([$ventaId, $userId]);
        $venta = $stmt->fetch();
        if (!$venta || $venta['estado'] !== 'completada') {
            jsonResponse(['error' => 'La compra no está completada'], 400);
        }
    }
    // Activar usuario y registrar fecha de activación
    $conn->beginTransaction();
    $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ?");
    $stmt->execute([$userId]);
    $stmt = $conn->prepare("UPDATE afiliados SET fecha_activacion = NOW() WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $conn->commit();
    logActivity($userId, 'afiliado_activado', 'Afiliado activado automáticamente tras compra');
    jsonResponse(['success' => true, 'message' => 'Afiliado activado correctamente'], 200);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error en activación de afiliado: ' . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
} 