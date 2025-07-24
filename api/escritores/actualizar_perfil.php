<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación
if (!function_exists('isAuthenticated') || !isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    http_response_code(401);
    exit;
}

$user = getCurrentUser();
if (!in_array($user['rol'], ['escritor', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    http_response_code(403);
    exit;
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$nombre = trim($input['nombre'] ?? '');
$email = trim(strtolower($input['email'] ?? ''));
$biografia = trim($input['biografia'] ?? '');

if (empty($nombre) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Nombre y email son obligatorios']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email inválido']);
    exit;
}

try {
    $conn = getDBConnection();
    // Verificar que el email no esté en uso por otro usuario
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'El email ya está en uso por otro usuario']);
        exit;
    }
    // Actualizar datos
    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, email = ?, biografia = ? WHERE id = ?');
    $stmt->execute([$nombre, $email, $biografia, $user['id']]);
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar perfil: ' . $e->getMessage()]);
    http_response_code(500);
} 