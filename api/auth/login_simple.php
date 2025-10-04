<?php
// Headers CORS primero
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores PHP
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función simple para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../config/database.php';
    
    // Iniciar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Obtener datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        sendResponse(['success' => false, 'error' => 'Datos JSON inválidos'], 400);
    }

    $email = trim(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendResponse(['success' => false, 'error' => 'Email y contraseña requeridos'], 400);
    }

    // Conectar a base de datos
    $conn = getDBConnection();
    
    // Buscar usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(['success' => false, 'error' => 'Usuario no encontrado'], 401);
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        sendResponse(['success' => false, 'error' => 'Contraseña incorrecta'], 401);
    }

    // Login exitoso - regenerar sesión
    session_regenerate_id(true);

    // Crear token simple (sin JWT por ahora)
    $token = bin2hex(random_bytes(32));
    $csrfToken = bin2hex(random_bytes(16));

    // Guardar en sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['user_estado'] = $user['estado'];
    $_SESSION['token'] = $token;

    // Determinar redirección
    $redirect = 'tienda-lectores.html';
    if ($user['rol'] === 'afiliado') {
        $redirect = 'dashboard-afiliado.html';
    } elseif ($user['rol'] === 'escritor') {
        $redirect = 'dashboard-escritor-mejorado.html';
    } elseif ($user['rol'] === 'admin') {
        $redirect = 'admin-panel.html';
    }

    // Respuesta exitosa
    sendResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'estado' => $user['estado']
        ],
        'token' => $token,
        'csrf_token' => $csrfToken,
        'session_token' => session_id(),
        'redirect' => $redirect
    ]);

} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'error' => 'Error de autenticación',
        'debug' => $e->getMessage()
    ], 500);
}
?>