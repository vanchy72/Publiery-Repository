<?php
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden ver los logs.'], 403);
}

// Ruta al archivo de log de errores de PHP (ajustar si es necesario)
// En XAMPP, a menudo se encuentra en php/logs/php_error_log o apache/logs/error.log
$phpErrorLogPath = ini_get('error_log');

error_log("DEBUG: ini_get('error_log') devuelve: " . ($phpErrorLogPath ?: '[VACIO]'));

// Fallback si ini_get('error_log') no devuelve una ruta válida o el archivo no existe
if (empty($phpErrorLogPath) || !file_exists($phpErrorLogPath)) {
    // Intentar rutas comunes para XAMPP en Windows
    $possibleLogPaths = [
        'C:/xampp/php/logs/php_error_log',
        'C:/xampp/apache/logs/error.log', // Este es el log de Apache que hemos estado usando
        'C:/xampp/php/logs/error.log', // Otra posible ubicación
    ];

    $foundPath = false;
    foreach ($possibleLogPaths as $path) {
        error_log("DEBUG: Comprobando ruta de log: " . $path);
        if (file_exists($path)) {
            error_log("DEBUG: Ruta encontrada: " . $path);
            $phpErrorLogPath = $path;
            $foundPath = true;
            break; // Se encontró la ruta, salir del bucle
        }
    }

    if (!$foundPath) {
        error_log("ERROR: No se pudo encontrar el archivo de log de errores de PHP en las rutas esperadas después de intentar fallbacks.");
        jsonResponse(['message' => 'No se pudo encontrar el archivo de log de errores de PHP en las rutas esperadas.'], 500);
    }
}

error_log("DEBUG: Ruta de log de PHP final seleccionada: " . $phpErrorLogPath);

if (!file_exists($phpErrorLogPath)) {
    error_log("ERROR: El archivo de log de PHP no existe en la ruta: " . $phpErrorLogPath);
    jsonResponse(['message' => 'El archivo de log de errores de PHP no existe.'], 500);
}

if (!is_readable($phpErrorLogPath)) {
    error_log("ERROR: El archivo de log de PHP no es legible en la ruta: " . $phpErrorLogPath);
    jsonResponse(['message' => 'El archivo de log de errores de PHP no es legible.'], 500);
}

// Leer las últimas N líneas del log (por ejemplo, 100 líneas)
$lines = file($phpErrorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lastLines = array_slice($lines, -100); // Obtener las últimas 100 líneas

jsonResponse(['success' => true, 'logs' => $lastLines], 200);
?>
