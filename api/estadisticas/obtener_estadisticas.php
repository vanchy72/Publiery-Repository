<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Cargar el archivo JSON de estadísticas
    $jsonFile = __DIR__ . '/estadisticas.json';
    
    if (!file_exists($jsonFile)) {
        throw new Exception('Archivo de estadísticas no encontrado');
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al parsear JSON de estadísticas');
    }
    
    // Filtrar solo estadísticas activas
    $estadisticasActivas = array_filter($data['estadisticas'], function($estadistica) {
        return isset($estadistica['activo']) && $estadistica['activo'] === true;
    });
    
    // Reindexar el array
    $estadisticasActivas = array_values($estadisticasActivas);
    
    // Limitar a 6 estadísticas para mostrar en la página principal
    $estadisticasLimitadas = array_slice($estadisticasActivas, 0, 6);
    
    echo json_encode([
        'success' => true,
        'data' => $estadisticasLimitadas,
        'total' => count($estadisticasActivas)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 