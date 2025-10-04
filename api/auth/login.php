<?php
// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/response.php';
    require_once __DIR__ . '/../../utils/jwt.php';
    require_once __DIR__ . '/../../utils/csrf.php';
} catch (Exception $e) {
    error_log('Error cargando dependencias: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
    exit;
}

// Recibir datos del usuario
$data = json_decode(file_get_contents('php://input'), true);
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'error' => 'Email y contraseña requeridos'], 400);
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && verifyPassword($password, $user['password'])) {
        // Iniciar sesión manualmente si no está iniciada
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Generar tokens
        $csrfToken = bin2hex(random_bytes(32));
        
        // Generar JWT token
        $jwtPayload = [
            'user_id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'estado' => $user['estado'],
            'csrf' => $csrfToken
        ];
        $jwtToken = JWT::encode($jwtPayload);

        // Guardar datos en sesión - USAR NOMBRES CONSISTENTES
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['id'] = $user['id']; // Fallback para compatibilidad
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['nombre'] = $user['nombre']; // Fallback para compatibilidad
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['rol'] = $user['rol']; // Fallback para compatibilidad
        $_SESSION['user_estado'] = $user['estado'];
        $_SESSION['estado'] = $user['estado']; // Fallback para compatibilidad
        $_SESSION['email'] = $user['email']; // Agregar email a la sesión
        $_SESSION['jwt'] = $jwtToken;
        $_SESSION['csrf_token'] = $csrfToken;

        // Determinar redirección
        $redirect = 'tienda-lectores.html';
        if ($user['rol'] === 'afiliado') {
            $redirect = 'dashboard-afiliado.html';
        } elseif ($user['rol'] === 'escritor') {
            $redirect = 'dashboard-escritor-mejorado.html';
        } elseif ($user['rol'] === 'admin') {
            $redirect = 'admin-panel.html';
        }

        // Preparar respuesta
        $responseData = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol'],
                'estado' => $user['estado']
            ],
            'token' => $jwtToken,
            'csrf_token' => $csrfToken,
            'session_token' => session_id(),
            'redirect' => $redirect
        ];

        // Mensaje especial para afiliados pendientes
        if ($user['estado'] === 'pendiente' && $user['rol'] === 'afiliado') {
            $responseData['is_pending_activation'] = true;
        }

        jsonResponse($responseData, 200);
    } else {
        jsonResponse(['success' => false, 'error' => 'Credenciales incorrectas'], 401);
    }
} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error de autenticación',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], 500);
}
