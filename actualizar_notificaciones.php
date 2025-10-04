<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🔧 ACTUALIZANDO TABLA DE NOTIFICACIONES\n\n";
    
    // Actualizar el enum para incluir 'campana_admin'
    $updateEnumSQL = "
    ALTER TABLE notificaciones 
    MODIFY COLUMN tipo ENUM(
        'info', 'success', 'warning', 'error', 'sistema', 'venta', 'comision', 
        'afiliado', 'libro_correccion', 'aprobacion', 'rechazo', 'campana_admin'
    ) NOT NULL DEFAULT 'info'
    ";
    
    if ($conn->exec($updateEnumSQL)) {
        echo "✅ Columna 'tipo' actualizada para incluir 'campana_admin'\n";
    } else {
        echo "❌ Error al actualizar la columna 'tipo'\n";
    }
    
    // Verificar que el cambio se aplicó
    $stmt = $conn->prepare("SHOW COLUMNS FROM notificaciones WHERE Field = 'tipo'");
    $stmt->execute();
    $column = $stmt->fetch();
    
    echo "\n📋 NUEVA DEFINICIÓN DE LA COLUMNA 'tipo':\n";
    echo "Tipo: {$column['Type']}\n";
    
    // Crear una notificación de prueba
    echo "\n🧪 CREANDO NOTIFICACIÓN DE PRUEBA...\n";
    
    // Obtener un afiliado para la prueba
    $stmt = $conn->prepare("
        SELECT u.id 
        FROM usuarios u
        INNER JOIN afiliados a ON u.id = a.usuario_id
        WHERE u.estado = 'activo' AND u.rol = 'afiliado'
        LIMIT 1
    ");
    $stmt->execute();
    $afiliado = $stmt->fetch();
    
    if ($afiliado) {
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales)
            VALUES (?, 'campana_admin', ?, ?, ?)
        ");
        
        $titulo = "Prueba - Nueva campaña del administrador";
        $mensaje = "Esta es una notificación de prueba para verificar el sistema.";
        $datos = json_encode(['test' => true, 'campana_id' => 999]);
        
        if ($stmt->execute([$afiliado['id'], $titulo, $mensaje, $datos])) {
            echo "✅ Notificación de prueba creada para el afiliado ID: {$afiliado['id']}\n";
        } else {
            echo "❌ Error al crear notificación de prueba\n";
        }
    } else {
        echo "❌ No hay afiliados activos para crear notificación de prueba\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>