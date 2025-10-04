<?php
/**
 * SCRIPT PARA CREAR USUARIO ADMINISTRADOR PRINCIPAL
 * Email: publierycompany@gmail.com
 * Contraseña: Admin123456
 */

// Verificar si existe el archivo .env
if (!file_exists('.env')) {
    die("❌ ERROR: No existe el archivo .env. Créalo primero con la configuración de la base de datos.");
}

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Crear Administrador Principal</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #e91e63; padding-bottom: 10px; }
    .success { color: #4CAF50; font-weight: bold; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .error { color: #f44336; font-weight: bold; background: #fde8e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .warning { color: #ff9800; font-weight: bold; background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3; }
    .admin-data { background: #f8f9fa; padding: 20px; border-radius: 5px; border: 2px solid #e91e63; margin: 20px 0; }
    .step { margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";
echo "</head><body>";

try {
    echo "<div class='container'>";
    echo "<h1>👑 CREAR ADMINISTRADOR PRINCIPAL</h1>";
    
    $pdo = getDBConnection();
    echo "<div class='success'>✅ Conexión exitosa a la base de datos</div>";
    
    // Datos del administrador
    $admin_email = 'publierycompany@gmail.com';
    $admin_password = 'Admin123456';
    $admin_nombre = 'Administrador Publiery';
    $admin_documento = 'ADMIN_PUBLIERY_001';
    
    echo "<div class='admin-data'>";
    echo "<h2>📋 DATOS DEL ADMINISTRADOR A CREAR:</h2>";
    echo "<p><strong>Nombre:</strong> $admin_nombre</p>";
    echo "<p><strong>Email:</strong> $admin_email</p>";
    echo "<p><strong>Contraseña:</strong> $admin_password</p>";
    echo "<p><strong>Documento:</strong> $admin_documento</p>";
    echo "<p><strong>Rol:</strong> admin</p>";
    echo "<p><strong>Estado:</strong> activo</p>";
    echo "</div>";
    
    // Verificar si ya existe
    echo "<div class='step'>";
    echo "<h3>PASO 1: Verificando si el usuario ya existe...</h3>";
    $stmt = $pdo->prepare("SELECT id, nombre, rol, estado FROM usuarios WHERE email = ?");
    $stmt->execute([$admin_email]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        echo "<div class='warning'>⚠️ USUARIO YA EXISTE</div>";
        echo "<p><strong>ID:</strong> {$existing_user['id']}</p>";
        echo "<p><strong>Nombre:</strong> {$existing_user['nombre']}</p>";
        echo "<p><strong>Rol:</strong> {$existing_user['rol']}</p>";
        echo "<p><strong>Estado:</strong> {$existing_user['estado']}</p>";
        
        if ($existing_user['rol'] === 'admin') {
            echo "<div class='info'>✅ El usuario ya es administrador. Puedes usar estos datos para hacer login.</div>";
        } else {
            echo "<div class='warning'>⚠️ El usuario existe pero NO es administrador. Actualizando...</div>";
            
            // Actualizar a admin
            $stmt = $pdo->prepare("UPDATE usuarios SET rol = 'admin', estado = 'activo' WHERE email = ?");
            $stmt->execute([$admin_email]);
            
            echo "<div class='success'>✅ Usuario actualizado a administrador exitosamente</div>";
        }
    } else {
        echo "<div class='info'>✅ Usuario no existe. Procediendo a crear...</div>";
        echo "</div>";
        
        // Crear el hash de la contraseña
        echo "<div class='step'>";
        echo "<h3>PASO 2: Generando hash seguro de la contraseña...</h3>";
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        echo "<div class='info'>✅ Hash generado exitosamente</div>";
        echo "</div>";
        
        // Insertar usuario
        echo "<div class='step'>";
        echo "<h3>PASO 3: Creando usuario administrador...</h3>";
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, documento, password, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, 'admin', 'activo', NOW())
        ");
        
        $result = $stmt->execute([
            $admin_nombre,
            $admin_email, 
            $admin_documento,
            $password_hash
        ]);
        
        if ($result) {
            $admin_id = $pdo->lastInsertId();
            echo "<div class='success'>✅ ADMINISTRADOR CREADO EXITOSAMENTE</div>";
            echo "<p><strong>ID del usuario:</strong> $admin_id</p>";
            echo "</div>";
            
            // Verificar creación
            echo "<div class='step'>";
            echo "<h3>PASO 4: Verificando creación...</h3>";
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$admin_id]);
            $new_admin = $stmt->fetch();
            
            if ($new_admin) {
                echo "<div class='success'>✅ Verificación exitosa. Usuario creado correctamente.</div>";
                echo "<p><strong>Nombre:</strong> {$new_admin['nombre']}</p>";
                echo "<p><strong>Email:</strong> {$new_admin['email']}</p>";
                echo "<p><strong>Rol:</strong> {$new_admin['rol']}</p>";
                echo "<p><strong>Estado:</strong> {$new_admin['estado']}</p>";
                echo "<p><strong>Fecha registro:</strong> {$new_admin['fecha_registro']}</p>";
            }
            echo "</div>";
        } else {
            echo "<div class='error'>❌ ERROR: No se pudo crear el usuario</div>";
        }
    }
    
    // Instrucciones finales
    echo "<div class='info'>";
    echo "<h2>🎯 ¡LISTO PARA USAR!</h2>";
    echo "<p><strong>Ahora puedes hacer login con:</strong></p>";
    echo "<p>📧 <strong>Email:</strong> <code>$admin_email</code></p>";
    echo "<p>🔐 <strong>Contraseña:</strong> <code>$admin_password</code></p>";
    echo "<br>";
    echo "<p><strong>Pasos siguientes:</strong></p>";
    echo "<ol>";
    echo "<li>Ve a: <a href='login.html' target='_blank'>http://localhost/publiery/login.html</a></li>";
    echo "<li>Ingresa con los datos de arriba</li>";
    echo "<li>Deberías ser redirigido a: <a href='admin-panel.html' target='_blank'>admin-panel.html</a></li>";
    echo "<li>Desde ahí podrás gestionar toda la plataforma</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 Verifica que:</div>";
    echo "<ul>";
    echo "<li>XAMPP esté funcionando (Apache + MySQL)</li>";
    echo "<li>El archivo .env exista con la configuración correcta</li>";
    echo "<li>La base de datos publiery_db exista</li>";
    echo "<li>La tabla usuarios esté creada</li>";
    echo "</ul>";
}

echo "</body></html>";
?>
