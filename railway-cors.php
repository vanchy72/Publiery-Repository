<?php
/**
 * CORS Handler para Railway Backend
 * Maneja requests desde Netlify frontend
 */

// Headers CORS permisivos para producción
$allowed_origins = [
    'https://publiery.netlify.app',
    'https://vanchy72.github.io',
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:3000',
    'http://localhost:8000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins) || str_contains($origin, 'netlify.app')) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función helper para respuestas JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Función helper para errores
function jsonError($message, $status = 400) {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

// Incluir en todos los archivos API de Railway
?>