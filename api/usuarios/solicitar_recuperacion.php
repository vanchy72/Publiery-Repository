<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/email.php'; // Debe contener la función sendEmail($to, $subject, $body)
header('Content-Type: application/json; charset=utf-8');

function generarToken($length = 40) {
    return bin2hex(random_bytes($length / 2));
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['email'])) throw new Exception('Email no recibido');
    $email = trim($input['email']);
    $conn = getDBConnection();
    // Buscar usuario
    $stmt = $conn->prepare('SELECT id, nombre FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    if (!$usuario) throw new Exception('Si el email existe, se enviará un enlace'); // No revelar si existe o no
    // Crear tabla si no existe
    $conn->exec("CREATE TABLE IF NOT EXISTS recuperacion_contrasena (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        token VARCHAR(100) NOT NULL,
        fecha_solicitud DATETIME NOT NULL,
        fecha_expiracion DATETIME NOT NULL,
        usado TINYINT(1) DEFAULT 0,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )");
    // Generar token y guardar
    $token = generarToken(40);
    $ahora = date('Y-m-d H:i:s');
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $conn->prepare('INSERT INTO recuperacion_contrasena (usuario_id, token, fecha_solicitud, fecha_expiracion) VALUES (?, ?, ?, ?)')
        ->execute([$usuario['id'], $token, $ahora, $expira]);
    // Enviar email
    if (!defined('APP_URL')) {
        define('APP_URL', 'http://localhost/publiery');
    }
    $enlace = APP_URL . "/restablecer.html?token=$token";
    $asunto = "Recuperación de contraseña - Publiery";
    $cuerpo = "Hola {$usuario['nombre']},<br><br>Hemos recibido una solicitud para restablecer tu contraseña.<br>Haz clic en el siguiente enlace para crear una nueva contraseña:<br><br><a href='$enlace'>$enlace</a><br><br>Este enlace es válido por 1 hora.";
    sendEmail($email, $asunto, $cuerpo);
    echo json_encode(['success' => true, 'message' => 'Si el email existe, se enviará un enlace']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 