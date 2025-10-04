<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';

try {
    // Buscar directamente el código del usuario ID 71
    $usuario_id = 71;
    
    $conn = getDBConnection();
    
    echo json_encode(['step' => 1, 'message' => 'Conectando a base de datos']);
    
    // Buscar el código de afiliado
    $stmt = $conn->prepare("SELECT codigo_afiliado, nivel FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $afiliado = $stmt->fetch();
    
    echo json_encode(['step' => 2, 'message' => 'Consulta ejecutada']);
    
    if ($afiliado) {
        echo json_encode([
            'step' => 3,
            'success' => true,
            'codigo_afiliado' => $afiliado['codigo_afiliado'],
            'nivel' => $afiliado['nivel'],
            'usuario_id' => $usuario_id,
            'message' => 'Código encontrado exitosamente'
        ]);
    } else {
        // Si no existe, crearlo
        echo json_encode(['step' => 3, 'message' => 'Afiliado no encontrado, creando...']);
        
        $codigo_afiliado = 'AF' . str_pad($usuario_id, 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, nivel, frontal) VALUES (?, ?, 1, 1)");
        $stmt->execute([$usuario_id, $codigo_afiliado]);
        
        echo json_encode([
            'step' => 4,
            'success' => true,
            'codigo_afiliado' => $codigo_afiliado,
            'nivel' => 1,
            'usuario_id' => $usuario_id,
            'message' => 'Código creado exitosamente'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>
