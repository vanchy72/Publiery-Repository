<?php
function jsonResponse($data, $statusCode = 200) {
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Establecer headers
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
    }
    
    echo json_encode($data);
    exit(); // Importante: terminar la ejecución aquí
}
?>
