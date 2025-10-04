<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $comprador_id = $input['comprador_id'] ?? null;
    $codigo_afiliado = $input['codigo_afiliado'] ?? 'AF000071';
    $valor = $input['valor'] ?? 30000;
    
    if (!$comprador_id) {
        echo json_encode(['success' => false, 'error' => 'Comprador ID requerido']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Buscar el afiliado por código
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE codigo_afiliado = ?");
    $stmt->execute([$codigo_afiliado]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        echo json_encode(['success' => false, 'error' => 'Código de afiliado no válido: ' . $codigo_afiliado]);
        exit;
    }
    
    // Crear/buscar un libro para la venta
    $stmt = $conn->prepare("SELECT id FROM libros WHERE estado = 'publicado' LIMIT 1");
    $stmt->execute();
    $libro = $stmt->fetch();
    
    if (!$libro) {
        // Crear un libro de prueba
        $stmt = $conn->prepare("INSERT INTO libros (titulo, descripcion, precio, autor_id, estado, fecha_publicacion) VALUES (?, ?, ?, ?, 'publicado', NOW())");
        $stmt->execute(['Libro de Prueba Final', 'Descripción del libro de prueba', $valor, 1]);
        $libro_id = $conn->lastInsertId();
    } else {
        $libro_id = $libro['id'];
    }
    
    // Insertar la venta
    $stmt = $conn->prepare("INSERT INTO ventas (libro_id, comprador_id, valor, estado, fecha_venta, codigo_afiliado_usado) VALUES (?, ?, ?, 'completada', NOW(), ?)");
    $stmt->execute([$libro_id, $comprador_id, $valor, $codigo_afiliado]);
    $venta_id = $conn->lastInsertId();
    
    // Calcular y registrar comisión (30%)
    $comision = $valor * 0.30;
    $stmt = $conn->prepare("INSERT INTO comisiones (afiliado_id, venta_id, porcentaje, valor_comision, estado, fecha_generacion) VALUES (?, ?, 30, ?, 'pendiente', NOW())");
    $stmt->execute([$afiliado['id'], $venta_id, $comision]);
    $comision_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'venta_id' => $venta_id,
        'libro_id' => $libro_id,
        'comprador_id' => $comprador_id,
        'valor' => $valor,
        'comision' => $comision,
        'comision_id' => $comision_id,
        'afiliado_id' => $afiliado['id'],
        'codigo_afiliado' => $codigo_afiliado
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
}
?>
