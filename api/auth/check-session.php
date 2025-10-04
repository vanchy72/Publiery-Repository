<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

// Obtener el token del header Authorization
$headers = getallheaders();
$token = null;
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    echo json_encode([
        'success' => false,
        'error' => 'Token no proporcionado'
    ]);
    exit;
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT usuario_id, fecha_creacion, fecha_expiracion, activa FROM sesiones WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        // Validar que la sesión esté activa y no expirada
        if ($row['activa'] != 1) {
            echo json_encode([
                'success' => false,
                'error' => 'Sesión inactiva'
            ]);
            exit;
        }
        if (strtotime($row['fecha_expiracion']) < time()) {
            // Token expirado, marcar como inactivo
            $stmt = $conn->prepare("UPDATE sesiones SET activa = 0 WHERE token = ?");
            $stmt->execute([$token]);
            echo json_encode([
                'success' => false,
                'error' => 'Token expirado'
            ]);
            exit;
        }
        echo json_encode([
            'success' => true,
            'user_id' => $row['usuario_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Token inválido'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
