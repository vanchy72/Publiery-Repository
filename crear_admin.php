<?php
require_once 'config/database.php';

echo "<h1>🔧 CREAR USUARIO ADMINISTRADOR</h1>";

try {
    $db = getDBConnection();
    
    // Verificar si ya existe un admin
    $stmt = $db->query("SELECT * FROM usuarios WHERE rol = 'admin'");
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAdmin) {
        echo "<h2>✅ Admin ya existe</h2>";
        echo "👤 <strong>Nombre:</strong> " . $existingAdmin['nombre'] . "<br>";
        echo "📧 <strong>Email:</strong> " . $existingAdmin['email'] . "<br>";
        echo "🔑 <strong>Para cambiar contraseña:</strong> Usar el formulario abajo<br><br>";
    } else {
        echo "<h2>🆕 Creando nuevo admin...</h2>";
        
        // Datos del admin por defecto
        $adminData = [
            'nombre' => 'Administrador Publiery',
            'email' => 'admin@publiery.com',
            'password' => 'Admin123!',
            'documento' => 'ADMIN001',
            'rol' => 'admin',
            'estado' => 'activo'
        ];
        
        $hashedPassword = hashPassword($adminData['password']);
        
        $stmt = $db->prepare("
            INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $adminData['nombre'],
            $adminData['email'],
            $hashedPassword,
            $adminData['documento'],
            $adminData['rol'],
            $adminData['estado']
        ]);
        
        echo "✅ <strong>Admin creado exitosamente!</strong><br>";
        echo "👤 <strong>Nombre:</strong> " . $adminData['nombre'] . "<br>";
        echo "📧 <strong>Email:</strong> " . $adminData['email'] . "<br>";
        echo "🔑 <strong>Contraseña:</strong> " . $adminData['password'] . "<br><br>";
    }
    
    echo "<h2>🔄 Cambiar contraseña del admin</h2>";
    
    // Procesar cambio de contraseña si se envió el formulario
    if ($_POST && isset($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        
        if (strlen($newPassword) >= 6) {
            $hashedPassword = hashPassword($newPassword);
            
            $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE rol = 'admin'");
            $stmt->execute([$hashedPassword]);
            
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Contraseña actualizada!</strong><br>";
            echo "🔑 <strong>Nueva contraseña:</strong> " . htmlspecialchars($newPassword);
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ La contraseña debe tener al menos 6 caracteres";
            echo "</div>";
        }
    }
    
    // Obtener datos actuales del admin
    $stmt = $db->query("SELECT * FROM usuarios WHERE rol = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <label for="new_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Nueva contraseña:</label>
        <input type="password" id="new_password" name="new_password" required 
               style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;" 
               placeholder="Mínimo 6 caracteres">
        <br><br>
        <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            🔄 Cambiar Contraseña
        </button>
    </form>
    
    <h2>🎯 Instrucciones</h2>
    <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
        <strong>Para acceder al panel de admin:</strong><br>
        1. 🌐 Ve a: <a href="admin-login.html" target="_blank">admin-login.html</a><br>
        2. 📧 Email: <code><?php echo $admin['email']; ?></code><br>
        3. 🔑 Contraseña: <code>La que estableciste arriba</code><br>
        4. ✅ Haz clic en "Iniciar Sesión como Administrador"<br>
    </div>
    <?php
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
