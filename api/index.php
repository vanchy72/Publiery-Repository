<?php
/**
 * API Index - Publiery
 * Archivo principal para las APIs de Vercel
 */

// Cargar configuración
require_once __DIR__ . '/../config/database.php';

// Headers básicos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Respuesta básica de estado
echo json_encode([
    'success' => true,
    'message' => 'Publiery API está funcionando',
    'version' => '1.0.0',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>