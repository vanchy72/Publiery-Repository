<?php
/**
 * API de Autenticación - Logout
 * Maneja el cierre de sesión de usuarios (compatible con sesiones PHP)
 */

session_start(); // Iniciar sesión para poder limpiarla

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Obtener el token del header Authorization (si existe)
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }
    
    // Si hay token, invalidarlo en la base de datos
    if ($token) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM sesiones WHERE token = ?");
            $stmt->execute([$token]);
        } catch (Exception $e) {
            error_log("Error invalidando token: " . $e->getMessage());
        }
    }
    
    // Limpiar todas las variables de sesión PHP
    $_SESSION = array();
    
    // Destruir la cookie de sesión en todos los contextos posibles
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        // Forzar eliminación en raíz y subdirectorios
        setcookie(session_name(), '', time() - 42000, '/');
        setcookie(session_name(), '', time() - 42000, '/publiery/');
    }
    
    // Limpiar cookies adicionales si existen
    setcookie('admin_token', '', time() - 3600, '/');
    setcookie('user_session', '', time() - 3600, '/');
    
    // Destruir la sesión
    session_destroy();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Sesión cerrada correctamente',
        'redirect' => 'admin-login.html'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en logout: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cerrar sesión: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 