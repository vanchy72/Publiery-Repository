<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Consulta simple para obtener campañas
    $stmt = $conn->query("SELECT * FROM campanas ORDER BY fecha_creacion DESC");
    $campanas = $stmt->fetchAll();
    
    $result = [];
    foreach ($campanas as $campana) {
        $result[] = [
            'id' => $campana['id'],
            'nombre' => $campana['nombre'],
            'tipo' => $campana['tipo'],
            'estado' => $campana['estado'],
            'fecha_inicio' => $campana['fecha_creacion'],
            'roi' => '0',
            'compartida_red' => $campana['compartida_red']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'campanas' => $result,
        'total' => count($result)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>