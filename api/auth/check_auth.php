<?php
/**
 * Verificación de autenticación - Migrado a configuración moderna
 */
require_once __DIR__ . '/../../config/database.php';

// Verificar la presencia del token JWT en los headers
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

// Obtener el token Bearer
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Verificar el token
$token = getBearerToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
    exit;
}

// Verificar CSRF token
$csrf_token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
if (!$csrf_token) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token no proporcionado']);
    exit;
}

try {
    // Usar la conexión moderna PDO
    $conn = getDBConnection();
    
    // Verificar el token en la base de datos
    $stmt = $conn->prepare("SELECT user_id, rol FROM sessions WHERE token = ? AND csrf_token = ? AND expired = 0 LIMIT 1");
    $stmt->execute([$token, $csrf_token]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sesión inválida o expirada']);
        exit;
    }

    // Verificar que es admin
    if ($session['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    // Establecer variables globales de sesión
    define('CURRENT_USER_ID', $session['user_id']);
    define('CURRENT_USER_ROLE', $session['rol']);

} catch (Exception $e) {
    error_log('Error en check_auth: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al verificar autenticación']);
    exit;
}
?>