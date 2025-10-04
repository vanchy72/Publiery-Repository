
<?php
/**
 * Guardia de Autenticación para Endpoints de Usuarios.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401); // 401 Unauthorized
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Por favor, inicia sesión.']);
    exit;
}
