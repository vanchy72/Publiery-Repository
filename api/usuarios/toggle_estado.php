<?php
require_once __DIR__ . '/../../config/database.php';
require_once '../../session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden cambiar estados'], 403);
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['estado'])) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
}

$id = (int)$input['id'];
$estado = $input['estado'];

$estadosValidos = ['activo', 'inactivo', 'pendiente', 'suspendido'];

if (!in_array($estado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
}

try {
    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id, nombre, estado FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    }
    
    // No permitir que un admin se desactive a sí mismo
    if ($id == $_SESSION['user_id'] && $estado !== 'activo') {
        jsonResponse(['success' => false, 'error' => 'No puedes desactivar tu propia cuenta'], 400);
    }
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse([
            'success' => true, 
            'message' => "Estado del usuario '{$usuario['nombre']}' cambiado a '{$estado}'"
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'No se realizaron cambios']);
    }
    
} catch (Exception $e) {
    error_log('Error cambiando estado de usuario: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
