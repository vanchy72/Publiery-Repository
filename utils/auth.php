<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';

function isAdmin() {
    // Primero verificar si hay token JWT en headers
    $token = getBearerToken();
    if ($token) {
        try {
            $payload = JWT::decode($token);
            return isset($payload['rol']) && $payload['rol'] === 'admin';
        } catch (Exception $e) {
            // Token inválido, continuar con verificación de sesión
        }
    }
    
    // Fallback a verificación de sesión
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

function isUserLoggedIn() {
    // Primero verificar si hay token JWT en headers
    $token = getBearerToken();
    if ($token) {
        try {
            $payload = JWT::decode($token);
            return isset($payload['user_id']);
        } catch (Exception $e) {
            // Token inválido, continuar con verificación de sesión
        }
    }
    
    // Fallback a verificación de sesión
    return isset($_SESSION['user_id']);
}

function getCurrentUserFromToken() {
    // Primero verificar si hay token JWT en headers
    $token = getBearerToken();
    if ($token) {
        try {
            $payload = JWT::decode($token);
            return [
                'id' => $payload['user_id'],
                'nombre' => $payload['nombre'],
                'rol' => $payload['rol'],
                'estado' => $payload['estado']
            ];
        } catch (Exception $e) {
            // Token inválido, continuar con verificación de sesión
        }
    }
    
    // Fallback a verificación de sesión
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'],
            'rol' => $_SESSION['user_rol'],
            'estado' => $_SESSION['user_estado']
        ];
    }
    
    return null;
}

function getBearerToken() {
    // Obtener todos los headers
    $headers = [];
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        // Fallback para servidores que no tienen apache_request_headers
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
    }
    
    // Verificar Authorization header
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    
    // También verificar en $_SERVER directamente
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $matches = [];
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function requireAuth() {
    if (!isUserLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Acceso denegado. Autenticación requerida'], 401);
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo administradores'], 403);
        exit;
    }
}
?>
