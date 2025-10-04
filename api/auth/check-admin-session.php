<?php
/**
 * API: check-admin-session.php
 * Propósito: Verificar sesión de administrador para acceso al panel
 * Verifica tanto sesiones PHP como cookies/localStorage
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    
    $authenticated = false;
    $user = null;
    
    // Método 1: Verificar sesión PHP
    if ((isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) || 
        (isset($_SESSION['user_id']) && isset($_SESSION['user_rol']))) {
        
        $user_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'];
        $user_rol = $_SESSION['rol'] ?? $_SESSION['user_rol'];
        
        if ($user_rol === 'admin') {
            // Obtener datos del usuario desde BD
            $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ? AND rol = 'admin' AND estado = 'activo'");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $authenticated = true;
                // Normalizar variables de sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rol'] = $user['rol'];
            }
        }
    }
    
    // Método 2: Verificar token en cookie/localStorage (fallback)
    if (!$authenticated) {
        $token = $_COOKIE['admin_token'] ?? $_GET['token'] ?? null;
        
        if ($token) {
            $stmt = $db->prepare("
                SELECT u.id, u.nombre, u.email, u.rol 
                FROM usuarios u 
                WHERE u.token_activacion = ? 
                AND u.rol = 'admin' 
                AND u.estado = 'activo'
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $authenticated = true;
                // Establecer sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rol'] = $user['rol'];
            }
        }
    }
    
    if ($authenticated && $user) {
        echo json_encode([
            'authenticated' => true,
            'user' => $user,
            'session_id' => session_id(),
            'message' => 'Sesión de administrador válida'
        ]);
    } else {
        // Verificar bypass para testing (TEMPORAL)
        if ((isset($_GET['bypass']) && $_GET['bypass'] === 'dev123') || 
            (isset($_GET['test']) && $_GET['test'] === 'admin')) {
            
            $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE rol = 'admin' AND estado = 'activo' LIMIT 1");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_rol'] = $user['rol'];
                
                echo json_encode([
                    'authenticated' => true,
                    'user' => $user,
                    'bypass' => true,
                    'message' => 'Bypass temporal activado - Usuario: ' . $user['nombre']
                ]);
                exit;
            }
        }
        
        echo json_encode([
            'authenticated' => false,
            'error' => 'No hay sesión de administrador activa',
            'redirect' => 'admin-login.html',
            'help' => 'Use admin-login.html para autenticarse',
            'debug' => [
                'session_vars' => array_keys($_SESSION),
                'available_admin_emails' => ['admin@publiery.com', 'publierycompany@gmail.com'],
                'bypass_help' => 'Para testing temporal: añadir ?test=admin a la URL'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'authenticated' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'redirect' => 'admin-login.html'
    ]);
}
?>