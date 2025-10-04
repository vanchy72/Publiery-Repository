<?php
/**
 * API: admin-auth-unified.php  
 * Propósito: Manejo unificado de autenticación para administradores
 * Soluciona problemas de sesiones inconsistentes
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    
    $action = $_GET['action'] ?? 'check';
    
    switch ($action) {
        case 'check':
            checkAdminAuth($db);
            break;
            
        case 'clear':
            clearAdminAuth();
            break;
            
        case 'set_test':
            setTestAdminAuth($db);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'error' => $e->getMessage()
    ]);
}

function checkAdminAuth($db) {
    $authenticated = false;
    $user = null;
    $method = 'none';
    
    // Método 1: Verificar sesión PHP (preferido)
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol'])) {
        if ($_SESSION['user_rol'] === 'admin') {
            $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ? AND rol = 'admin' AND estado = 'activo'");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $authenticated = true;
                $method = 'session_php';
                
                // Normalizar todas las variables de sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rol'] = 'admin';
            }
        }
    }
    
    // Método 2: Bypass temporal para testing
    if (!$authenticated && isset($_GET['test']) && $_GET['test'] === 'admin') {
        $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE rol = 'admin' AND estado = 'activo' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Establecer sesión completa
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['user_rol'] = 'admin';
            $_SESSION['rol'] = 'admin';
            $_SESSION['user_nombre'] = $user['nombre'];
            
            $authenticated = true;
            $method = 'test_bypass';
        }
    }
    
    if ($authenticated && $user) {
        echo json_encode([
            'authenticated' => true,
            'user' => $user,
            'method' => $method,
            'session_id' => session_id(),
            'message' => 'Autenticación exitosa'
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'error' => 'No hay sesión de administrador válida',
            'redirect' => 'admin-login.html',
            'help' => [
                'login' => 'Usa admin-login.html para autenticarte',
                'test' => 'Para testing: añade ?test=admin a la URL',
                'clear' => 'Si hay problemas: añade ?action=clear para limpiar'
            ],
            'debug' => [
                'session_vars' => array_keys($_SESSION),
                'session_id' => session_id()
            ]
        ]);
    }
}

function clearAdminAuth() {
    // Limpiar TODA la autenticación
    session_destroy();
    
    // Limpiar cookies de autenticación
    setcookie('admin_token', '', time() - 3600, '/');
    setcookie('token', '', time() - 3600, '/');
    setcookie(session_name(), '', time() - 3600, '/');
    
    echo json_encode([
        'success' => true,
        'message' => 'Autenticación limpiada completamente'
    ]);
}

function setTestAdminAuth($db) {
    $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE rol = 'admin' AND estado = 'activo' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Regenerar sesión
        session_regenerate_id(true);
        
        // Establecer TODAS las variables de sesión necesarias
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['user_rol'] = 'admin';
        $_SESSION['rol'] = 'admin';
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_estado'] = 'activo';
        
        echo json_encode([
            'success' => true,
            'user' => $user,
            'message' => 'Sesión de prueba establecida para: ' . $user['nombre']
        ]);
    } else {
        throw new Exception('No se encontró usuario admin activo');
    }
}
?>