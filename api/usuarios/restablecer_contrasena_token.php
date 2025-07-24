<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

function registrarCambioContrasena($conn, $usuario_id, $tipo_cambio) {
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
    $stmt = $conn->prepare("INSERT INTO cambios_contrasena (usuario_id, admin_id, tipo_cambio, fecha, ip, user_agent) VALUES (?, NULL, ?, NOW(), ?, ?)");
    $stmt->execute([
        $usuario_id,
        $tipo_cambio,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['token']) || !isset($input['nueva'])) throw new Exception('Datos incompletos');
    $token = $input['token'];
    $nueva = $input['nueva'];
    if (strlen($nueva) < 6) throw new Exception('La contraseña debe tener al menos 6 caracteres');
    $conn = getDBConnection();
    // Buscar token válido
    $stmt = $conn->prepare('SELECT * FROM recuperacion_contrasena WHERE token = ? AND usado = 0 AND fecha_expiracion > NOW()');
    $stmt->execute([$token]);
    $rec = $stmt->fetch();
    if (!$rec) throw new Exception('El enlace de recuperación es inválido o ha expirado');
    $usuario_id = $rec['usuario_id'];
    // Cambiar contraseña
    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $conn->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([$hash, $usuario_id]);
    // Marcar token como usado
    $conn->prepare('UPDATE recuperacion_contrasena SET usado = 1 WHERE id = ?')->execute([$rec['id']]);
    // Registrar cambio (tipo usuario)
    registrarCambioContrasena($conn, $usuario_id, 'usuario');
    echo json_encode(['success' => true, 'message' => 'Contraseña restablecida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 