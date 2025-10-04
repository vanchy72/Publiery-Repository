<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Query más simple sin JOINs complejos
    $sql = "SELECT * FROM ventas ORDER BY id DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asegurar que los datos tengan las propiedades mínimas esperadas
    $ventasFormateadas = [];
    foreach ($ventas as $venta) {
        $ventasFormateadas[] = [
            'id' => $venta['id'] ?? '',
            'fecha_venta' => $venta['fecha_venta'] ?? $venta['fecha'] ?? $venta['created_at'] ?? 'N/A',
            'estado' => $venta['estado'] ?? $venta['status'] ?? 'pendiente',
            'comprador_nombre' => $venta['comprador_nombre'] ?? $venta['nombre_comprador'] ?? $venta['cliente'] ?? 'N/A',
            'comprador_email' => $venta['comprador_email'] ?? $venta['email'] ?? 'N/A',
            'precio_pagado' => $venta['precio_pagado'] ?? $venta['total'] ?? $venta['precio'] ?? $venta['monto'] ?? '0.00',
            'libro_titulo' => 'Libro #' . ($venta['libro_id'] ?? 'N/A'),
            'afiliado_nombre' => 'Afiliado #' . ($venta['afiliado_id'] ?? 'N/A')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'ventas' => $ventasFormateadas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener ventas: ' . $e->getMessage()
    ]);
}
?>