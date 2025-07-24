<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

function generarPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

function registrarCambioContrasena($conn, $usuario_id, $admin_id, $tipo_cambio) {
    // Crear tabla si no existe
    $conn->exec("CREATE TABLE IF NOT EXISTS cambios_contrasena (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        admin_id INT NULL,
        tipo_cambio VARCHAR(20) NOT NULL,
        fecha DATETIME NOT NULL,
        ip VARCHAR(45),
        user_agent TEXT,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )");
    $stmt = $conn->prepare("INSERT INTO cambios_contrasena (usuario_id, admin_id, tipo_cambio, fecha, ip, user_agent) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([
        $usuario_id,
        $admin_id,
        $tipo_cambio,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['id'])) throw new Exception('ID de usuario no recibido');
    $usuario_id = intval($input['id']);
    $nueva = $input['nueva'] ?? '';
    if (!$nueva) {
        $nueva = generarPassword(10);
    }
    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $conn = getDBConnection();
    $conn->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([$hash, $usuario_id]);
    // Registrar cambio (tipo admin)
    $admin_id = null;
    if (isset($_SESSION['user_id'])) {
        // Si hay sesión PHP
        $admin_id = $_SESSION['user_id'];
    } else if (isset($input['admin_id'])) {
        // O si se envía explícitamente
        $admin_id = intval($input['admin_id']);
    }
    registrarCambioContrasena($conn, $usuario_id, $admin_id, 'admin');
    echo json_encode(['success' => true, 'message' => 'Contraseña restablecida', 'nueva' => $nueva]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 