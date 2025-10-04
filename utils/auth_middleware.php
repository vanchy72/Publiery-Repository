<?php
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/csrf.php';

class AuthMiddleware {
    public static function verifyAdmin() {
        // Verificar si hay una sesión activa
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol'])) {
            jsonResponse(['success' => false, 'error' => 'No hay sesión activa'], 401);
        }

        // Verificar si el usuario es admin
        if ($_SESSION['user_rol'] !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
        }

        // Obtener headers
        $headers = getallheaders();
        
        // Verificar JWT token
        $token = $headers['Authorization'] ?? null;
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            jsonResponse(['success' => false, 'error' => 'Token no proporcionado'], 401);
        }
        
        $jwt = substr($token, 7);
        try {
            $payload = JWT::decode($jwt);
            
            // Verificar que el token pertenece al usuario en sesión
            if ($payload['user_id'] !== $_SESSION['user_id']) {
                throw new Exception('Token inválido');
            }
            
            // Verificar CSRF token para métodos no seguros
            if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                $csrfToken = $headers[CSRF::getHeaderName()] ?? null;
                if (!$csrfToken || !CSRF::validateToken($csrfToken)) {
                    jsonResponse(['success' => false, 'error' => 'CSRF token inválido'], 403);
                }
            }
            
            return true;
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Token inválido o expirado'], 401);
        }
    }
    
    public static function refreshToken() {
        // Si hay una sesión activa y un token JWT, renovarlo
        if (isset($_SESSION['user_id']) && isset($_SESSION['jwt'])) {
            $headers = getallheaders();
            $token = $headers['Authorization'] ?? null;
            
            if ($token && strpos($token, 'Bearer ') === 0) {
                $jwt = substr($token, 7);
                try {
                    $payload = JWT::decode($jwt);
                    
                    // Si el token está por expirar (menos de 1 hora), generar uno nuevo
                    if ($payload['exp'] - time() < 3600) {
                        $csrfToken = CSRF::generateToken();
                        $newPayload = [
                            'user_id' => $_SESSION['user_id'],
                            'nombre' => $_SESSION['user_nombre'],
                            'rol' => $_SESSION['user_rol'],
                            'estado' => $_SESSION['user_estado'],
                            'csrf' => $csrfToken
                        ];
                        
                        $newToken = JWT::encode($newPayload);
                        $_SESSION['jwt'] = $newToken;
                        
                        return [
                            'token' => $newToken,
                            'csrf_token' => $csrfToken
                        ];
                    }
                } catch (Exception $e) {
                    // Si hay un error con el token, forzar nuevo login
                    session_destroy();
                    jsonResponse([
                        'success' => false,
                        'error' => 'Sesión expirada',
                        'redirect' => 'admin-login.html'
                    ], 401);
                }
            }
        }
        
        return null;
    }
}