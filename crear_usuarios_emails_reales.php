<?php
/**
 * SCRIPT PARA CREAR USUARIOS CON EMAILS REALES
 * Para probar envío de emails de notificación
 */

// Verificar si existe el archivo .env
if (!file_exists('.env')) {
    die("❌ ERROR: No existe el archivo .env. Créalo primero con la configuración de la base de datos.");
}

require_once 'config/database.php';
require_once 'config/email.php';

echo "<!DOCTYPE html><html><head><title>Crear Usuarios con Emails Reales</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
    .form-group { margin: 20px 0; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
    .btn { background: #2196F3; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
    .btn:hover { background: #1976D2; }
    .btn-success { background: #4CAF50; }
    .btn-warning { background: #ff9800; }
    .success { color: #4CAF50; font-weight: bold; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .error { color: #f44336; font-weight: bold; background: #fde8e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .warning { color: #ff9800; font-weight: bold; background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3; }
    .user-card { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #2196F3; }
    .email-test { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; }
</style>";
echo "</head><body>";

try {
    echo "<div class='container'>";
    echo "<h1>📧 CREAR USUARIOS CON EMAILS REALES</h1>";
    
    $pdo = getDBConnection();
    echo "<div class='success'>✅ Conexión exitosa a la base de datos</div>";
    
    // Si se envió el formulario
    if ($_POST && isset($_POST['crear_usuario'])) {
        $nombre = trim($_POST['nombre']);
        $email = trim(strtolower($_POST['email']));
        $documento = trim($_POST['documento']);
        $password = $_POST['password'];
        $rol = $_POST['rol'];
        $enviar_email = isset($_POST['enviar_email']);
        
        // Validaciones
        if (empty($nombre) || empty($email) || empty($documento) || empty($password)) {
            echo "<div class='error'>❌ Todos los campos son obligatorios</div>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<div class='error'>❌ Email inválido</div>";
        } else {
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                echo "<div class='warning'>⚠️ Ya existe un usuario con ese email</div>";
            } else {
                // Crear usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nombre, email, documento, password, rol, estado, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, 'activo', NOW())
                ");
                
                if ($stmt->execute([$nombre, $email, $documento, $password_hash, $rol])) {
                    $usuario_id = $pdo->lastInsertId();
                    echo "<div class='success'>✅ Usuario creado exitosamente (ID: $usuario_id)</div>";
                    
                    // Crear registro en tabla específica según el rol
                    if ($rol === 'escritor') {
                        $stmt = $pdo->prepare("INSERT INTO escritores (usuario_id, estado) VALUES (?, 'activo')");
                        $stmt->execute([$usuario_id]);
                    } elseif ($rol === 'afiliado') {
                        $codigo_afiliado = 'AF' . str_pad($usuario_id, 6, '0', STR_PAD_LEFT);
                        $stmt = $pdo->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, nivel, frontal, estado) VALUES (?, ?, 1, 1, 'activo')");
                        $stmt->execute([$usuario_id, $codigo_afiliado]);
                        echo "<div class='info'>📋 Código de afiliado asignado: $codigo_afiliado</div>";
                    } elseif ($rol === 'lector') {
                        $stmt = $pdo->prepare("INSERT INTO lectores (usuario_id, estado) VALUES (?, 'activo')");
                        $stmt->execute([$usuario_id]);
                    }
                    
                    // Enviar email de bienvenida si se solicitó
                    if ($enviar_email) {
                        echo "<div class='email-test'>";
                        echo "<h3>📧 PRUEBA DE ENVÍO DE EMAIL</h3>";
                        
                        try {
                            $emailService = new EmailService();
                            $asunto = "¡Bienvenido a Publiery!";
                            $mensaje = "
                            <h2>¡Hola $nombre!</h2>
                            <p>Te damos la bienvenida a Publiery.</p>
                            <p><strong>Tus datos de acceso:</strong></p>
                            <ul>
                                <li><strong>Email:</strong> $email</li>
                                <li><strong>Contraseña:</strong> $password</li>
                                <li><strong>Rol:</strong> $rol</li>
                            </ul>
                            <p>Puedes acceder en: <a href='" . APP_URL . "/login.html'>Iniciar Sesión</a></p>
                            <p>¡Gracias por unirte a nuestra plataforma!</p>
                            ";
                            
                            if ($emailService->sendEmail($email, $asunto, $mensaje)) {
                                echo "<div class='success'>✅ Email enviado exitosamente a: $email</div>";
                            } else {
                                echo "<div class='error'>❌ Error al enviar email. Verifica la configuración SMTP.</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='error'>❌ Error en el sistema de emails: " . $e->getMessage() . "</div>";
                        }
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Error al crear el usuario</div>";
                }
            }
        }
    }
    
    // Mostrar usuarios existentes con emails reales
    echo "<h2>👥 USUARIOS CON EMAILS REALES EXISTENTES</h2>";
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE email LIKE '%@gmail.com' OR email LIKE '%@hotmail.com' OR email LIKE '%@yahoo.com' OR email NOT LIKE '%test.com' ORDER BY fecha_registro DESC");
    $usuarios_reales = $stmt->fetchAll();
    
    if ($usuarios_reales) {
        foreach ($usuarios_reales as $usuario) {
            echo "<div class='user-card'>";
            echo "<strong>{$usuario['nombre']}</strong> ({$usuario['rol']}) - {$usuario['email']} ";
            echo "<span style='color: " . ($usuario['estado'] === 'activo' ? 'green' : 'orange') . "'>[{$usuario['estado']}]</span>";
            echo "<br><small>Registrado: {$usuario['fecha_registro']}</small>";
            echo "</div>";
        }
    } else {
        echo "<div class='info'>No hay usuarios con emails reales registrados.</div>";
    }
    
    // Formulario para crear usuarios
    echo "<h2>➕ CREAR NUEVO USUARIO CON EMAIL REAL</h2>";
    echo "<div class='info'>💡 Usa emails reales (Gmail, Hotmail, etc.) para probar el envío de notificaciones.</div>";
    
    echo "<form method='POST'>";
    echo "<div class='form-group'>";
    echo "<label>Nombre Completo:</label>";
    echo "<input type='text' name='nombre' required placeholder='Ej: María García López'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Email Real:</label>";
    echo "<input type='email' name='email' required placeholder='Ej: maria@gmail.com'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Documento:</label>";
    echo "<input type='text' name='documento' required placeholder='Ej: 12345678'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Contraseña:</label>";
    echo "<input type='password' name='password' required placeholder='Mínimo 6 caracteres'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Rol:</label>";
    echo "<select name='rol' required>";
    echo "<option value='escritor'>Escritor</option>";
    echo "<option value='afiliado'>Afiliado</option>";
    echo "<option value='lector'>Lector</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>";
    echo "<input type='checkbox' name='enviar_email' checked> Enviar email de bienvenida (para probar SMTP)";
    echo "</label>";
    echo "</div>";
    
    echo "<button type='submit' name='crear_usuario' class='btn btn-success'>✅ Crear Usuario y Enviar Email</button>";
    echo "</form>";
    
    echo "<div class='info'>";
    echo "<h3>📋 USUARIOS RECOMENDADOS PARA CREAR:</h3>";
    echo "<p><strong>1. Escritor Real:</strong> Usa tu email personal para recibir notificaciones de libros</p>";
    echo "<p><strong>2. Afiliado Real:</strong> Usa otro email tuyo para probar comisiones y activaciones</p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
