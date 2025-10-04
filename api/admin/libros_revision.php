<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../../config/database.php";

try {
    $pdo = getDBConnection();
    
    // Obtener libros pendientes de revisión
    $stmt = $pdo->query("
        SELECT l.*, u.nombre as autor_nombre, u.email as autor_email
        FROM libros l 
        LEFT JOIN usuarios u ON l.autor_id = u.id 
        WHERE l.estado = 'pendiente_revision'
        ORDER BY l.fecha_registro DESC
    ");
    
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    foreach ($libros as &$libro) {
        $libro['fecha_registro_formateada'] = date('d/m/Y H:i', strtotime($libro['fecha_registro']));
        $libro['precio_formateado'] = '$' . number_format($libro['precio'], 0, ',', '.');
        $libro['tiene_archivo'] = !empty($libro['archivo_original']);
        $libro['tiene_portada'] = !empty($libro['imagen_portada']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $libros,
        'total' => count($libros),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>