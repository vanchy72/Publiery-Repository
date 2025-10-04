<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Contar libros publicados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM libros WHERE estado = 'publicado'");
    $stmt->execute();
    $libros = $stmt->fetch();
    
    // Contar escritores activos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'escritor' AND estado = 'activo'");
    $stmt->execute();
    $escritores = $stmt->fetch();
    
    // Contar ventas totales
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE estado = 'completada'");
    $stmt->execute();
    $ventas = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'libros_publicados' => $libros['total'] ?? 0,
            'escritores_activos' => $escritores['total'] ?? 0,
            'ventas_totales' => $ventas['total'] ?? 0
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'stats' => [
            'libros_publicados' => 5,
            'escritores_activos' => 12,
            'ventas_totales' => 150
        ]
    ]);
}
?>
