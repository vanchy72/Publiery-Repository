<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>👥 USUARIOS EXISTENTES EN EL SISTEMA</h2>";
    
    // Obtener todos los usuarios
    $stmt = $pdo->query("
        SELECT id, nombre, email, rol, estado, fecha_registro 
        FROM usuarios 
        ORDER BY fecha_registro DESC
    ");
    
    $usuarios = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Fecha</th></tr>";
    
    foreach ($usuarios as $usuario) {
        $color = ($usuario['estado'] === 'activo') ? 'green' : 'orange';
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nombre']}</td>";
        echo "<td>{$usuario['email']}</td>";
        echo "<td><strong>{$usuario['rol']}</strong></td>";
        echo "<td style='color: {$color}'>{$usuario['estado']}</td>";
        echo "<td>{$usuario['fecha_registro']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Contar por rol
    echo "<h3>📊 Resumen por roles:</h3>";
    $stmt = $pdo->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
    $resumen = $stmt->fetchAll();
    
    foreach ($resumen as $item) {
        echo "<p><strong>{$item['rol']}:</strong> {$item['total']} usuarios</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 USUARIOS DISPONIBLES PARA PRUEBAS:</h3>";
    
    // Escritor
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE rol = 'escritor' AND estado = 'activo' LIMIT 1");
    $escritor = $stmt->fetch();
    if ($escritor) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✍️ ESCRITOR DISPONIBLE:</h4>";
        echo "<p><strong>Email:</strong> {$escritor['email']}</p>";
        echo "<p><strong>Nombre:</strong> {$escritor['nombre']}</p>";
        echo "<p><strong>ID:</strong> {$escritor['id']}</p>";
        echo "<p><em>Contraseña probablemente: 123456</em></p>";
        echo "</div>";
    }
    
    // Afiliado
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE rol = 'afiliado' AND estado = 'activo' LIMIT 1");
    $afiliado = $stmt->fetch();
    if ($afiliado) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🌐 AFILIADO DISPONIBLE:</h4>";
        echo "<p><strong>Email:</strong> {$afiliado['email']}</p>";
        echo "<p><strong>Nombre:</strong> {$afiliado['nombre']}</p>";
        echo "<p><strong>ID:</strong> {$afiliado['id']}</p>";
        echo "<p><em>Contraseña probablemente: 123456</em></p>";
        echo "</div>";
    }
    
    // Lector/Cliente
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE rol = 'lector' AND estado = 'activo' LIMIT 1");
    $cliente = $stmt->fetch();
    if ($cliente) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🛒 CLIENTE/LECTOR DISPONIBLE:</h4>";
        echo "<p><strong>Email:</strong> {$cliente['email']}</p>";
        echo "<p><strong>Nombre:</strong> {$cliente['nombre']}</p>";
        echo "<p><strong>ID:</strong> {$cliente['id']}</p>";
        echo "<p><em>Contraseña probablemente: 123456</em></p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>🚀 PLAN DE ACCIÓN:</h3>";
    echo "<ol>";
    echo "<li><strong>USA LOS USUARIOS EXISTENTES</strong> para hacer las pruebas de compra</li>";
    echo "<li><strong>PRUEBA EL FLUJO COMPLETO</strong> de venta con afiliado</li>";
    echo "<li><strong>VERIFICA LAS COMISIONES</strong> que es lo más importante</li>";
    echo "<li><strong>DESPUÉS</strong> arreglamos el registro para usuarios nuevos</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Usuarios Existentes - Publiery</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h4 { margin: 0 0 10px 0; }
    </style>
</head>
<body>
</body>
</html>
