<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    // Recibir datos del testimonio a actualizar
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $activo = $data['activo'] ?? null;
    
    if ($id === null || $activo === null) {
        throw new Exception('ID y estado activo son requeridos');
    }
    
    // Cargar el archivo JSON de testimonios
    $jsonFile = __DIR__ . '/testimonios.json';
    
    if (!file_exists($jsonFile)) {
        throw new Exception('Archivo de testimonios no encontrado');
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $testimoniosData = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al parsear JSON de testimonios');
    }
    
    // Buscar el testimonio por ID
    $testimonioEncontrado = false;
    foreach ($testimoniosData['testimonios'] as &$testimonio) {
        if ($testimonio['id'] == $id) {
            $testimonio['activo'] = (bool)$activo;
            $testimonioEncontrado = true;
            break;
        }
    }
    
    if (!$testimonioEncontrado) {
        throw new Exception('Testimonio no encontrado');
    }
    
    // Guardar los cambios en el archivo JSON
    $resultado = file_put_contents($jsonFile, json_encode($testimoniosData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($resultado === false) {
        throw new Exception('Error al guardar los cambios');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Testimonio actualizado correctamente',
        'id' => $id,
        'activo' => $activo
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 