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
    $sql = "SELECT * FROM comisiones ORDER BY id DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asegurar que los datos tengan las propiedades mínimas esperadas
    $pagosFormateados = [];
    foreach ($comisiones as $comision) {
        $pagosFormateados[] = [
            'id' => $comision['id'] ?? '',
            'destinatario_nombre' => 'Usuario #' . ($comision['usuario_id'] ?? 'N/A'),
            'monto' => $comision['monto'] ?? $comision['cantidad'] ?? $comision['total'] ?? '0.00',
            'tipo_pago' => ($comision['tipo'] ?? 'comision') . 
                          (isset($comision['porcentaje']) ? ' (' . $comision['porcentaje'] . '%)' : ''),
            'fecha_pago' => $comision['fecha_pago'] ?? $comision['fecha_creacion'] ?? $comision['fecha'] ?? $comision['created_at'] ?? 'N/A',
            'estado' => $comision['estado'] ?? $comision['status'] ?? 'pendiente',
            'libro_titulo' => 'Venta #' . ($comision['venta_id'] ?? 'N/A'),
            'codigo_afiliado' => 'N/A'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'pagos' => $pagosFormateados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener pagos: ' . $e->getMessage()
    ]);
}
?>