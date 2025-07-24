<?php
/**
 * Verificar Sesión
 * Endpoint para verificar el estado de la sesión actual
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Verificar si hay una sesión activa
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'error' => 'No hay sesión activa'
        ]);
        exit;
    }

    // Obtener información del usuario
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, rol, estado FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'error' => 'Usuario no encontrado'
        ]);
        exit;
    }

    // Verificar si es afiliado y obtener información adicional
    if ($user['rol'] === 'afiliado') {
        $stmt = $conn->prepare("SELECT * FROM afiliados WHERE usuario_id = ?");
        $stmt->execute([$user['id']]);
        $afiliado = $stmt->fetch();
        
        if (!$afiliado) {
            echo json_encode([
                'success' => false,
                'authenticated' => true,
                'user' => $user,
                'error' => 'Usuario afiliado sin registro en tabla afiliados'
            ]);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'user' => $user,
        'session_id' => session_id(),
        'message' => 'Sesión válida'
    ]);

} catch (Exception $e) {
    error_log('Error verificando sesión: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?> 