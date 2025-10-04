<?php
/**
 * Establecer sesión de escritor para pruebas - usando un ID que exista
 */

session_start();

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Buscar un escritor existente en la BD
    $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE rol = 'escritor' AND estado = 'activo' LIMIT 1");
    $stmt->execute();
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escritor) {
        // Si no hay escritores, buscar cualquier usuario
        $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE rol = 'escritor' LIMIT 1");
        $stmt->execute();
        $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$escritor) {
        // Si aún no hay, crear Santiago
        $insertQuery = "
            INSERT INTO usuarios (nombre, email, contraseña, rol, estado, fecha_registro) 
            VALUES ('Santiago Escritor', 'santiago@publiery.com', ?, 'escritor', 'activo', NOW())
        ";
        
        $hashedPassword = password_hash('santiago123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([$hashedPassword]);
        
        $escritor_id = $conn->lastInsertId();
        $escritor = [
            'id' => $escritor_id,
            'nombre' => 'Santiago Escritor',
            'email' => 'santiago@publiery.com'
        ];
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "✅ <strong>Escritor creado:</strong> Santiago Escritor (ID: $escritor_id)";
        echo "</div>";
    }
    
    // Establecer sesión del escritor encontrado
    $_SESSION['user_id'] = $escritor['id'];
    $_SESSION['user_rol'] = 'escritor';
    $_SESSION['user_nombre'] = $escritor['nombre'];
    $_SESSION['user_email'] = $escritor['email'];
    $_SESSION['user_estado'] = 'activo';
    
    echo "<h2>✅ Sesión establecida como {$escritor['nombre']}</h2>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $_SESSION['user_id'] . "</li>";
    echo "<li><strong>Rol:</strong> " . $_SESSION['user_rol'] . "</li>";
    echo "<li><strong>Nombre:</strong> " . $_SESSION['user_nombre'] . "</li>";
    echo "<li><strong>Email:</strong> " . $_SESSION['user_email'] . "</li>";
    echo "<li><strong>Estado:</strong> " . $_SESSION['user_estado'] . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='prueba_subida_portadas.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Ir a Prueba de Portadas</a> ";
echo "<a href='dashboard-escritor-mejorado.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📝 Dashboard Escritor</a>";
echo "</div>";

echo "<p><small>⚠️ Esta sesión es solo para pruebas del sistema de portadas.</small></p>";
?>