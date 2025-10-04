<?php
// Test simple para verificar que las APIs funcionan
header('Content-Type: application/json');

echo json_encode([
    'message' => 'API funcionando',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get_params' => $_GET,
    'post_data' => file_get_contents('php://input')
]);
?>