<?php
/**
 * API de Autenticación - Logout
 * Maneja el cierre de sesión de usuarios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    // Obtener el token del header Authorization
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
    
    // Destruir la sesión PHP
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Limpiar todas las variables de sesión
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
        setcookie(session_name(), '', time() - 42000, '/publiery');
    }
    
    // Destruir la sesión
    session_destroy();
    
    jsonResponse(['success' => true, 'message' => 'Sesión cerrada correctamente'], 200);
    
} catch (Exception $e) {
    error_log("Error en logout: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?> 