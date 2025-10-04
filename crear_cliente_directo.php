<?php
/**
 * CREAR CLIENTE DIRECTAMENTE EN LA BASE DE DATOS
 * Solucionando el problema del endpoint de registro
 */

require_once 'config/database.php';

function crearClienteDirecto() {
    try {
        $pdo = getDBConnection();
        
        echo "<h2>🛒 Creando Cliente Directamente en BD</h2>";
        
        // Datos del cliente
        $cliente_data = [
            'nombre' => 'Cliente Comprador',
            'email' => 'cliente.comprador@test.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'documento' => '111222333',
            'rol' => 'lector',
            'estado' => 'activo',
            'fecha_registro' => date('Y-m-d H:i:s')
        ];
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$cliente_data['email']]);
        
        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "⚠️ El cliente ya existe - ID: {$usuario['id']}<br>";
            return $usuario['id'];
        }
        
        // Crear cliente
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $cliente_data['nombre'],
            $cliente_data['email'],
            $cliente_data['password'],
            $cliente_data['documento'],
            $cliente_data['rol'],
            $cliente_data['estado'],
            $cliente_data['fecha_registro']
        ]);
        
        $cliente_id = $pdo->lastInsertId();
        
        echo "✅ Cliente creado exitosamente!<br>";
        echo "👤 ID: {$cliente_id}<br>";
        echo "📧 Email: {$cliente_data['email']}<br>";
        echo "🔑 Contraseña: 123456<br>";
        echo "📝 Rol: {$cliente_data['rol']}<br>";
        
        return $cliente_id;
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Ejecutar
$cliente_id = crearClienteDirecto();

if ($cliente_id) {
    echo "<hr>";
    echo "<h3>🎯 AHORA PUEDES CONTINUAR CON LA PRUEBA:</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>📋 PASOS PARA LA PRUEBA DE COMPRA:</h4>";
    echo "<ol>";
    echo "<li><strong>Cierra sesión</strong> del afiliado María (si está abierta)</li>";
    echo "<li><strong>Ve a login:</strong> <a href='login.html' target='_blank'>http://localhost/publiery/login.html</a></li>";
    echo "<li><strong>Haz login con el cliente:</strong><br>";
    echo "    📧 Email: <code>cliente.comprador@test.com</code><br>";
    echo "    🔑 Contraseña: <code>123456</code></li>";
    echo "<li><strong>Ve al enlace del afiliado:</strong><br>";
    echo "    <a href='tienda-lectores.html?afiliado=AF000059' target='_blank'>http://localhost/publiery/tienda-lectores.html?afiliado=AF000059</a></li>";
    echo "<li><strong>Compra el libro</strong> 'Mi Primer Libro - Prueba'</li>";
    echo "<li><strong>Verifica comisiones:</strong> <a href='scripts_verificacion_pruebas.php' target='_blank'>scripts_verificacion_pruebas.php</a></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h4>🎉 ¡El cliente está listo! Ahora SÍ puedes hacer la compra con afiliado.</h4>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cliente Creado - Publiery</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        a { color: #4f46e5; text-decoration: none; }
        a:hover { text-decoration: underline; }
        ol li { margin: 8px 0; }
    </style>
</head>
<body>
</body>
</html>
