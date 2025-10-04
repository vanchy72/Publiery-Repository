<?php
/**
 * ENDPOINT DE REGISTRO DEFINITIVO
 * Acepta tanto GET como POST para evitar problemas del servidor
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

// Función de sanitización
function sanitizeInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Acepta tanto GET como POST para evitar problemas
$method = $_SERVER['REQUEST_METHOD'];
$input = [];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
} elseif ($method === 'GET') {
    $input = $_GET;
} else {
    jsonResponse(['error' => 'Método no soportado'], 405);
}

// Si es GET sin parámetros, mostrar formulario de prueba
if ($method === 'GET' && empty($input)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Registro Funcional - Publiery</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin: 15px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            button { background: #4f46e5; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; margin: 10px 0; }
            .result { padding: 15px; margin: 15px 0; border-radius: 5px; }
            .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
            .error { background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; }
        </style>
    </head>
    <body>
        <h2>✅ Registro Funcional - Publiery</h2>
        <p>Este endpoint acepta tanto GET como POST para evitar problemas del servidor.</p>
        
        <form id="registroForm">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="Cliente Final" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="cliente.final@test.com" required>
            </div>
            
            <div class="form-group">
                <label>Documento:</label>
                <input type="text" name="documento" value="111222333" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" value="123456" required>
            </div>
            
            <div class="form-group">
                <label>Rol:</label>
                <select name="rol">
                    <option value="lector">Lector (Cliente)</option>
                    <option value="afiliado">Afiliado</option>
                    <option value="escritor">Escritor</option>
                </select>
            </div>
            
            <button type="button" onclick="registrarConGET()">🔧 Registrar con GET (Seguro)</button>
            <button type="button" onclick="registrarConPOST()">🚀 Registrar con POST (Normal)</button>
        </form>
        
        <div id="resultado"></div>

        <script>
            function registrarConGET() {
                const form = document.getElementById('registroForm');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                
                window.location.href = 'registro_funcional.php?' + params.toString();
            }
            
            async function registrarConPOST() {
                const form = document.getElementById('registroForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                const div = document.getElementById('resultado');
                div.innerHTML = '⏳ Registrando con POST...';
                
                try {
                    const response = await fetch('registro_funcional.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        div.innerHTML = `
                            <div class="result success">
                                <h3>✅ Registro POST Exitoso!</h3>
                                <p><strong>Usuario:</strong> ${result.user.nombre}</p>
                                <p><strong>Email:</strong> ${result.user.email}</p>
                                <p><strong>ID:</strong> ${result.user.id}</p>
                            </div>
                        `;
                    } else {
                        div.innerHTML = `
                            <div class="result error">
                                <h3>❌ Error POST:</h3>
                                <p>${result.error}</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    div.innerHTML = `
                        <div class="result error">
                            <h3>❌ Error de conexión:</h3>
                            <p>${error.message}</p>
                        </div>
                    `;
                }
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Validar y sanitizar datos
    $nombre = sanitizeInput($input['nombre'] ?? '');
    $email = trim(strtolower($input['email'] ?? ''));
    $documento = sanitizeInput($input['documento'] ?? '');
    $password = $input['password'] ?? '';
    $rol = sanitizeInput($input['rol'] ?? 'lector');
    
    // Validaciones básicas
    if (empty($nombre)) {
        jsonResponse(['error' => 'El nombre es requerido'], 400);
    }
    if (empty($email)) {
        jsonResponse(['error' => 'El email es requerido'], 400);
    }
    if (empty($password)) {
        jsonResponse(['error' => 'La contraseña es requerida'], 400);
    }
    if (empty($documento)) {
        jsonResponse(['error' => 'El documento es requerido'], 400);
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Email inválido'], 400);
    }
    
    // Conectar a BD
    $conn = getDBConnection();
    
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        jsonResponse(['error' => 'El email ya está registrado'], 400);
    }
    
    // Verificar si el documento ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE documento = ?");
    $stmt->execute([$documento]);
    if ($stmt->rowCount() > 0) {
        jsonResponse(['error' => 'El documento ya está registrado'], 400);
    }
    
    // Crear usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
        VALUES (?, ?, ?, ?, ?, 'activo', NOW())
    ");
    
    $stmt->execute([$nombre, $email, $hashedPassword, $documento, $rol]);
    $userId = $conn->lastInsertId();
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'user' => [
            'id' => $userId,
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'estado' => 'activo'
        ]
    ];
    
    if ($method === 'GET') {
        // Para GET, mostrar página de éxito
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Registro Exitoso</title>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 20px; border-radius: 10px; }
            </style>
        </head>
        <body>
            <div class="success">
                <h2>✅ ¡REGISTRO EXITOSO!</h2>
                <p><strong>Usuario:</strong> <?= htmlspecialchars($nombre) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>ID:</strong> <?= $userId ?></p>
                <p><strong>Rol:</strong> <?= htmlspecialchars($rol) ?></p>
                <h3>🎉 EL REGISTRO FUNCIONA PERFECTAMENTE</h3>
                <p><a href="login.html">👉 Ir al Login</a></p>
            </div>
        </body>
        </html>
        <?php
    } else {
        jsonResponse($response, 201);
    }
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Error en registro: ' . $e->getMessage(),
        'method' => $method
    ], 500);
}
?>
