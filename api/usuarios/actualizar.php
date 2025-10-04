<?php
require_once __DIR__ . '/../../config/database.php';
require_once '../../config/auth_functions.php';
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden actualizar usuarios'], 403);
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
}

$id = (int)$input['id'];
$nombre = sanitizeInput($input['nombre'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$rol = $input['rol'] ?? '';
$estado = $input['estado'] ?? '';

// Validaciones
if (empty($nombre) || empty($email) || empty($rol) || empty($estado)) {
    jsonResponse(['success' => false, 'error' => 'Todos los campos son requeridos'], 400);
}

if (!validateEmail($email)) {
    jsonResponse(['success' => false, 'error' => 'Email inválido'], 400);
}

$rolesValidos = ['afiliado', 'escritor', 'lector', 'admin'];
$estadosValidos = ['activo', 'inactivo', 'pendiente', 'suspendido'];

if (!in_array($rol, $rolesValidos)) {
    jsonResponse(['success' => false, 'error' => 'Rol inválido'], 400);
}

if (!in_array($estado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
}

try {
    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id, email FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuarioExistente = $stmt->fetch();
    
    if (!$usuarioExistente) {
        jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    }
    
    // Verificar que el email no esté en uso por otro usuario
    if ($email !== $usuarioExistente['email']) {
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'El email ya está en uso'], 400);
        }
    }
    
    // Preparar query de actualización
    $campos = ['nombre = ?', 'email = ?', 'rol = ?', 'estado = ?'];
    $valores = [$nombre, $email, $rol, $estado];
    
    // Si se proporciona contraseña, incluirla
    if (!empty($input['password'])) {
        if (!validatePassword($input['password'])) {
            jsonResponse(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }
        $campos[] = 'password = ?';
        $valores[] = hashPassword($input['password']);
    }
    
    $valores[] = $id; // Para el WHERE
    
    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($valores);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } else {
        jsonResponse(['success' => false, 'error' => 'No se realizaron cambios']);
    }
    
} catch (Exception $e) {
    error_log('Error actualizando usuario: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
