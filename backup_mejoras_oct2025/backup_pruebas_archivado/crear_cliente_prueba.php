<?php
/**
 * SCRIPT PARA CREAR CLIENTE DE PRUEBA
 * Para realizar compras a través de enlaces de afiliado
 */

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>🛒 Creando Cliente de Prueba</h2>\n";
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['cliente.prueba@test.com']);
    
    if ($stmt->rowCount() > 0) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "⚠️ El cliente de prueba ya existe - ID: {$usuario['id']}<br>\n";
        echo "📧 Email: cliente.prueba@test.com<br>\n";
        echo "🔑 Contraseña: 123456<br>\n";
    } else {
        // Crear cliente/lector
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->execute([
            'Cliente de Prueba',
            'cliente.prueba@test.com',
            $password_hash,
            '555666777',
            'lector',
            'activo',
            date('Y-m-d H:i:s')
        ]);
        
        $usuario_id = $pdo->lastInsertId();
        
        echo "✅ Cliente creado exitosamente<br>\n";
        echo "👤 Usuario ID: {$usuario_id}<br>\n";
        echo "📧 Email: cliente.prueba@test.com<br>\n";
        echo "🔑 Contraseña: 123456<br>\n";
        echo "📝 Rol: lector<br>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>🎯 INSTRUCCIONES PARA LA PRUEBA:</h3>\n";
    echo "<ol>\n";
    echo "<li><strong>Cierra sesión</strong> del afiliado María</li>\n";
    echo "<li><strong>Haz login</strong> con: cliente.prueba@test.com / 123456</li>\n";
    echo "<li><strong>Ve al enlace del afiliado:</strong> http://localhost/publiery/tienda-lectores.html?afiliado=AF000059</li>\n";
    echo "<li><strong>Compra el libro</strong> 'Mi Primer Libro - Prueba'</li>\n";
    echo "<li><strong>Verifica las comisiones</strong> con scripts_verificacion_pruebas.php</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Crear Cliente de Prueba - Publiery</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        ol li { margin: 10px 0; }
    </style>
</head>
<body>
</body>
</html>
