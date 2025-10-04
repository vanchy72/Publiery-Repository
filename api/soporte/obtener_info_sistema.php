<?php
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../config/database.php'; // Para obtener la conexión a la DB

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden ver la información del sistema.'], 403);
}

$systemInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'os' => php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('v'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

// Intentar obtener la versión de MySQL/MariaDB
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query('SELECT VERSION()');
    $mysqlVersion = $stmt->fetchColumn();
    $systemInfo['mysql_version'] = $mysqlVersion;
} catch (PDOException $e) {
    $systemInfo['mysql_version'] = 'No se pudo obtener (Error: ' . $e->getMessage() . ')';
}

jsonResponse(['success' => true, 'system_info' => $systemInfo], 200);
?>
