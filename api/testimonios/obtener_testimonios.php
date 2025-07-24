<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Cargar el archivo JSON de testimonios
    $jsonFile = __DIR__ . '/testimonios.json';
    
    if (!file_exists($jsonFile)) {
        throw new Exception('Archivo de testimonios no encontrado');
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al parsear JSON de testimonios');
    }
    
    // Verificar si se solicitan todos los testimonios (para el panel de admin)
    $mostrarTodos = isset($_GET['all']) && $_GET['all'] == '1';
    
    if ($mostrarTodos) {
        // Para el panel de administrador: mostrar todos los testimonios
        $testimonios = $data['testimonios'];
    } else {
        // Para la página principal: filtrar solo testimonios activos
        $testimonios = array_filter($data['testimonios'], function($testimonio) {
            return isset($testimonio['activo']) && $testimonio['activo'] === true;
        });
        
        // Reindexar el array
        $testimonios = array_values($testimonios);
        
        // Limitar a 3 testimonios para mostrar en la página principal
        $testimonios = array_slice($testimonios, 0, 3);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $testimonios,
        'total' => count($testimonios)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 