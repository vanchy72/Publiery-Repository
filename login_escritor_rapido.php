<?php
echo "<h1>🔐 ACCESO RÁPIDO: Login Escritor</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; border-left: 5px solid #17a2b8; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.form-group { margin: 15px 0; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
.btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; font-size: 16px; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn:hover { opacity: 0.8; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Obtener escritores disponibles
    $stmt = $pdo->query("
        SELECT id, nombre, email, rol, estado, fecha_registro 
        FROM usuarios 
        WHERE rol = 'escritor' 
        ORDER BY fecha_registro DESC
        LIMIT 10
    ");
    $escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='panel info'>";
    echo "<h2>👥 Escritores Disponibles en el Sistema</h2>";
    echo "<p>Selecciona un escritor para hacer login automático:</p>";
    
    if (!empty($escritores)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Acción</th></tr>";
        
        foreach ($escritores as $escritor) {
            $estado_color = $escritor['estado'] === 'activo' ? '#28a745' : '#ffc107';
            echo "<tr>";
            echo "<td>#{$escritor['id']}</td>";
            echo "<td>" . htmlspecialchars($escritor['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($escritor['email']) . "</td>";
            echo "<td style='color: $estado_color;'>" . ucfirst($escritor['estado']) . "</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='login_escritor' value='{$escritor['id']}'>";
            echo "<button type='submit' class='btn btn-success' style='padding: 5px 10px; font-size: 12px;'>🔑 Login</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ No se encontraron escritores. Vamos a crear uno de prueba.</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Procesar login automático
    if (isset($_POST['login_escritor'])) {
        $escritor_id = (int)$_POST['login_escritor'];
        
        // Obtener datos del escritor
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'escritor'");
        $stmt->execute([$escritor_id]);
        $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escritor) {
            // Iniciar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $escritor['id'];
            $_SESSION['user_nombre'] = $escritor['nombre'];
            $_SESSION['user_email'] = $escritor['email'];
            $_SESSION['user_rol'] = $escritor['rol'];
            $_SESSION['user_estado'] = $escritor['estado'];
            $_SESSION['logged_in'] = true;
            
            echo "<div class='panel success'>";
            echo "<h2>🎉 ¡Login Exitoso!</h2>";
            echo "<p><strong>Bienvenido:</strong> " . htmlspecialchars($escritor['nombre']) . "</p>";
            echo "<p><strong>Rol:</strong> " . ucfirst($escritor['rol']) . "</p>";
            echo "<p><strong>Estado:</strong> " . ucfirst($escritor['estado']) . "</p>";
            echo "<div style='margin: 20px 0;'>";
            echo "<a href='dashboard-escritor-mejorado.html' target='_blank' class='btn btn-primary'>📝 Ir al Dashboard Escritor</a>";
            echo "<a href='diagnostico_subida_libros.php' target='_blank' class='btn btn-warning'>🔍 Verificar Sesión</a>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='panel info'>";
            echo "<h3>📋 Próximos Pasos:</h3>";
            echo "<ol>";
            echo "<li>✅ <strong>Sesión iniciada correctamente</strong></li>";
            echo "<li>🔗 <strong>Haz clic en 'Ir al Dashboard Escritor'</strong></li>";
            echo "<li>📝 <strong>Ve a la pestaña 'Subir Libro'</strong></li>";
            echo "<li>📤 <strong>Sube tu PDF + JPG</strong></li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='panel error'>";
            echo "<h3>❌ Error</h3>";
            echo "<p>No se pudo encontrar el escritor seleccionado.</p>";
            echo "</div>";
        }
    }
    
    // Crear escritor de prueba si no hay ninguno
    if (empty($escritores) && !isset($_POST['crear_escritor'])) {
        echo "<div class='panel warning'>";
        echo "<h3>🔧 Crear Escritor de Prueba</h3>";
        echo "<p>No hay escritores en el sistema. Vamos a crear uno para hacer pruebas:</p>";
        echo "<form method='POST'>";
        echo "<div class='form-group'>";
        echo "<label>Nombre del Escritor:</label>";
        echo "<input type='text' name='nombre_escritor' value='Escritor de Prueba' required>";
        echo "</div>";
        echo "<div class='form-group'>";
        echo "<label>Email:</label>";
        echo "<input type='email' name='email_escritor' value='escritor@prueba.com' required>";
        echo "</div>";
        echo "<button type='submit' name='crear_escritor' value='1' class='btn btn-success'>👤 Crear Escritor de Prueba</button>";
        echo "</form>";
        echo "</div>";
    }
    
    // Procesar creación de escritor
    if (isset($_POST['crear_escritor'])) {
        $nombre = trim($_POST['nombre_escritor']);
        $email = trim($_POST['email_escritor']);
        $password = password_hash('123456', PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, rol, estado, fecha_registro) 
                VALUES (?, ?, ?, 'escritor', 'activo', NOW())
            ");
            $stmt->execute([$nombre, $email, $password]);
            $nuevo_id = $pdo->lastInsertId();
            
            echo "<div class='panel success'>";
            echo "<h3>✅ Escritor Creado</h3>";
            echo "<p><strong>ID:</strong> #$nuevo_id</p>";
            echo "<p><strong>Nombre:</strong> $nombre</p>";
            echo "<p><strong>Email:</strong> $email</p>";
            echo "<p><strong>Password:</strong> 123456</p>";
            echo "<form method='POST' style='margin: 20px 0;'>";
            echo "<input type='hidden' name='login_escritor' value='$nuevo_id'>";
            echo "<button type='submit' class='btn btn-primary'>🔑 Hacer Login Automático</button>";
            echo "</form>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='panel error'>";
            echo "<h3>❌ Error</h3>";
            echo "<p>Error creando escritor: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // Mostrar estado actual de sesión
    echo "<div class='panel info'>";
    echo "<h3>🔍 Estado Actual de Sesión</h3>";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        echo "<table>";
        echo "<tr><th>Variable</th><th>Valor</th></tr>";
        echo "<tr><td>user_id</td><td>" . $_SESSION['user_id'] . "</td></tr>";
        echo "<tr><td>user_nombre</td><td>" . ($_SESSION['user_nombre'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>user_rol</td><td>" . ($_SESSION['user_rol'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>user_estado</td><td>" . ($_SESSION['user_estado'] ?? 'N/A') . "</td></tr>";
        echo "</table>";
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='dashboard-escritor-mejorado.html' target='_blank' class='btn btn-primary'>📝 Dashboard Escritor</a>";
        echo "<a href='api/escritores/subir_libro.php' target='_blank' class='btn btn-warning'>🔗 Test Backend</a>";
        echo "</div>";
        
    } else {
        echo "<p style='color: #dc3545;'>❌ No hay sesión activa. Selecciona un escritor arriba para hacer login.</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='panel error'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>