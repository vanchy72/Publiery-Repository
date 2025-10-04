<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🧪 CREANDO USUARIO DE PRUEBA PARA ELIMINACIÓN\n";
    echo "============================================\n\n";
    
    // Crear un usuario de prueba
    $nombre = 'Usuario Para Eliminar';
    $email = 'eliminar.test.' . time() . '@example.com';
    $documento = 'ELIM-' . rand(100000, 999999);
    $password = password_hash('123456', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, documento, rol, password, estado, fecha_registro) VALUES (?, ?, ?, 'afiliado', ?, 'activo', NOW())");
    $stmt->execute([$nombre, $email, $documento, $password]);
    
    $usuario_id = $conn->lastInsertId();
    echo "✅ Usuario creado con ID: $usuario_id\n";
    echo "   - Nombre: $nombre\n";
    echo "   - Email: $email\n";
    echo "   - Documento: $documento\n\n";
    
    // Crear registro de afiliado
    $codigo = 'AF' . str_pad($usuario_id, 6, '0', STR_PAD_LEFT);
    $stmt = $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, nivel, frontal, fecha_activacion, comision_total, ventas_totales) VALUES (?, ?, 1, 0, NOW(), 0, 0)");
    $stmt->execute([$usuario_id, $codigo]);
    echo "✅ Registro de afiliado creado\n";
    
    // Crear una notificación de prueba
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, fecha_envio) VALUES (?, 'Test', 'Notificación de prueba', NOW())");
    $stmt->execute([$usuario_id]);
    echo "✅ Notificación de prueba creada\n\n";
    
    echo "📋 REGISTROS RELACIONADOS ANTES DE ELIMINAR:\n";
    echo "-------------------------------------------\n";
    
    // Verificar registros relacionados
    $tables = [
        'usuarios' => 'id',
        'afiliados' => 'usuario_id', 
        'notificaciones' => 'usuario_id'
    ];
    
    foreach ($tables as $table => $column) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
        $stmt->execute([$usuario_id]);
        $count = $stmt->fetch()['count'];
        echo "- $table: $count registros\n";
    }
    
    echo "\n💡 Para probar la eliminación:\n";
    echo "1. Ve al panel de administración\n";
    echo "2. Busca el usuario '$nombre'\n";
    echo "3. Haz clic en el botón rojo 'Eliminar'\n";
    echo "4. Confirma la eliminación\n";
    echo "5. Verifica que ya no aparece en la lista\n\n";
    
    echo "🔍 O ejecuta este comando para verificar después:\n";
    echo "SELECT * FROM usuarios WHERE id = $usuario_id;\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>