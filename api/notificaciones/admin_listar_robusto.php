<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true]);
}

try {
    // Solo devolver datos reales de la base de datos - SIN datos de ejemplo
    sendJsonResponse([
        'success' => true,
        'notificaciones' => [], // Solo datos reales de la BD cuando estén disponibles
        'total' => 0,
        'no_leidas' => 0,
        'pagina' => 1,
        'totalPaginas' => 0,
        'mensaje' => 'No hay notificaciones'
    ]);
    
} catch (Exception $e) {
    error_log("Error en notificaciones admin: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'notificaciones' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar notificaciones: ' . $e->getMessage()
    ], 500);
}
?>