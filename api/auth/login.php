<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

// Recibir datos del usuario
$data = json_decode(file_get_contents('php://input'), true);
$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email y contraseña requeridos']);
    exit;
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && verifyPassword($password, $user['password'])) {
        // Revisar estado
        if ($user['estado'] === 'pendiente' && $user['rol'] === 'afiliado') {
            // Afiliado pendiente de activación
            $_SESSION['user_id'] = $user['id'];
            echo json_encode([
                'success' => true,
                'is_pending_activation' => true,
                'user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol'],
                    'estado' => $user['estado']
                ],
                'session_token' => session_id(), // Agregar session_token
                'redirect' => 'dashboard-afiliado.html'
            ]);
            exit;
        } elseif ($user['estado'] !== 'activo' && $user['rol'] === 'afiliado') {
            // Solo los afiliados no activos son bloqueados
            echo json_encode(['success' => false, 'error' => 'Tu cuenta no está activa.']);
            exit;
        }
        // Login exitoso
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true); // Regenerar session_id para evitar duplicados
        $token = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $fechaCreacion = date('Y-m-d H:i:s');
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+1 day'));
        // Eliminar sesiones previas del usuario
        $stmt = $conn->prepare("UPDATE sesiones SET activa = 0 WHERE usuario_id = ?");
        $stmt->execute([$user['id']]);
        // Guardar el token en la tabla sesiones
        $stmt = $conn->prepare("INSERT INTO sesiones (usuario_id, token, fecha_creacion, fecha_expiracion, ip_address, user_agent, activa) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user['id'], $token, $fechaCreacion, $fechaExpiracion, $ip, $userAgent]);
        // Cambiar la lógica para que los afiliados pendientes también vayan a dashboard-afiliado.html
        $redirect = 'tienda-lectores.html'; // Por defecto para lectores (tienda independiente)
        if ($user['rol'] === 'afiliado') {
            $redirect = 'dashboard-afiliado.html';
        } elseif ($user['rol'] === 'escritor') {
            $redirect = 'dashboard-escritor.html';
        } elseif ($user['rol'] === 'admin') {
            $redirect = 'admin-panel.html';
        }
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol'],
                'estado' => $user['estado']
            ],
            'session_token' => $token, // Agregar session_token
            'redirect' => $redirect
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
        exit;
    }
} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
    exit;
} 