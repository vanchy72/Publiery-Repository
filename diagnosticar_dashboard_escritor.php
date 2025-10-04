<?php
/**
 * DIAGNÓSTICO DEL DASHBOARD DEL ESCRITOR
 * Para identificar qué está causando el error al cargar datos
 */

session_start();
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Diagnóstico Dashboard Escritor</title>";
echo "<style>
    body { font-family: monospace; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    .error { color: #f44336; background: #fde8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .success { color: #4CAF50; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #ff9800; background: #fff3e0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f0f0f0; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 DIAGNÓSTICO DASHBOARD ESCRITOR</h1>";

try {
    // 1. Verificar sesión
    echo "<h2>1. VERIFICACIÓN DE SESIÓN:</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<div class='success'>✅ Sesión activa - User ID: {$_SESSION['user_id']}</div>";
        if (isset($_SESSION['user_rol'])) {
            echo "<div class='success'>✅ Rol de usuario: {$_SESSION['user_rol']}</div>";
        } else {
            echo "<div class='warning'>⚠️ Rol de usuario no definido en sesión</div>";
        }
        if (isset($_SESSION['user_nombre'])) {
            echo "<div class='success'>✅ Nombre de usuario: {$_SESSION['user_nombre']}</div>";
        }
    } else {
        echo "<div class='error'>❌ No hay sesión activa</div>";
        echo "<div class='info'>💡 Necesitas hacer login primero</div>";
    }

    // 2. Verificar conexión a BD
    echo "<h2>2. VERIFICACIÓN DE BASE DE DATOS:</h2>";
    $conn = getDBConnection();
    echo "<div class='success'>✅ Conexión a BD exitosa</div>";

    // 3. Verificar usuario en BD
    if (isset($_SESSION['user_id'])) {
        echo "<h2>3. VERIFICACIÓN DE USUARIO EN BD:</h2>";
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            echo "<div class='success'>✅ Usuario encontrado en BD</div>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            foreach ($usuario as $key => $value) {
                if ($key !== 'password') { // No mostrar contraseña
                    echo "<tr><td>$key</td><td>$value</td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Usuario no encontrado en BD</div>";
        }
    }

    // 4. Verificar tabla escritores
    echo "<h2>4. VERIFICACIÓN DE TABLA ESCRITORES:</h2>";
    $stmt = $conn->query("SHOW TABLES LIKE 'escritores'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Tabla 'escritores' existe</div>";
        
        // Verificar estructura
        $stmt = $conn->query("DESCRIBE escritores");
        $columnas = $stmt->fetchAll();
        echo "<h3>Estructura de tabla escritores:</h3>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
        foreach ($columnas as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
        
        // Verificar si existe registro del escritor
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("SELECT * FROM escritores WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $escritor = $stmt->fetch();
            
            if ($escritor) {
                echo "<div class='success'>✅ Registro de escritor encontrado</div>";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                foreach ($escritor as $key => $value) {
                    echo "<tr><td>$key</td><td>$value</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ No existe registro en tabla escritores para este usuario</div>";
                echo "<div class='info'>💡 Se creará automáticamente al acceder al dashboard</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Tabla 'escritores' no existe</div>";
        echo "<div class='info'>💡 Creando tabla escritores...</div>";
        
        // Crear tabla escritores
        $sql = "CREATE TABLE escritores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            estado ENUM('pendiente_revision','activo','inactivo','suspendido') DEFAULT 'pendiente_revision',
            biografia TEXT,
            especialidades VARCHAR(500),
            total_libros INT DEFAULT 0,
            total_ventas DECIMAL(10,2) DEFAULT 0.00,
            fecha_activacion DATETIME NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )";
        
        if ($conn->exec($sql)) {
            echo "<div class='success'>✅ Tabla escritores creada exitosamente</div>";
        } else {
            echo "<div class='error'>❌ Error creando tabla escritores</div>";
        }
    }

    // 5. Verificar otras tablas necesarias
    echo "<h2>5. VERIFICACIÓN DE TABLAS RELACIONADAS:</h2>";
    $tablas_necesarias = ['libros', 'ventas', 'royalties', 'notificaciones'];
    
    foreach ($tablas_necesarias as $tabla) {
        $stmt = $conn->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Tabla '$tabla' existe</div>";
            
            // Contar registros
            $stmt = $conn->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $stmt->fetch()['total'];
            echo "<div class='info'>📊 Registros en $tabla: $count</div>";
        } else {
            echo "<div class='warning'>⚠️ Tabla '$tabla' no existe</div>";
        }
    }

    // 6. Probar consulta del dashboard
    if (isset($_SESSION['user_id']) && $_SESSION['user_rol'] === 'escritor') {
        echo "<h2>6. PRUEBA DE CONSULTA DASHBOARD:</h2>";
        
        try {
            // Simular la consulta que hace el dashboard
            $autor_id = $_SESSION['user_id'];
            
            // Verificar/crear registro en escritores
            $stmt = $conn->prepare("SELECT id FROM escritores WHERE usuario_id = ?");
            $stmt->execute([$autor_id]);
            $escritor_info = $stmt->fetch();
            
            if (!$escritor_info) {
                echo "<div class='info'>📝 Creando registro de escritor...</div>";
                $stmt = $conn->prepare("INSERT INTO escritores (usuario_id, estado) VALUES (?, 'activo')");
                $stmt->execute([$autor_id]);
                $escritor_id = $conn->lastInsertId();
                echo "<div class='success'>✅ Registro de escritor creado con ID: $escritor_id</div>";
            } else {
                $escritor_id = $escritor_info['id'];
                echo "<div class='success'>✅ Escritor ID: $escritor_id</div>";
            }
            
            // Consulta básica de información del escritor
            $stmt = $conn->prepare("
                SELECT 
                    e.id as escritor_id,
                    u.id as usuario_id,
                    u.nombre,
                    u.email,
                    u.fecha_registro,
                    u.estado as cuenta_activa
                FROM escritores e
                JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.id = ?
            ");
            $stmt->execute([$escritor_id]);
            $escritor = $stmt->fetch();
            
            if ($escritor) {
                echo "<div class='success'>✅ Consulta de escritor exitosa</div>";
                echo "<pre>" . print_r($escritor, true) . "</pre>";
            } else {
                echo "<div class='error'>❌ Error en consulta de escritor</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error en prueba de consulta: " . $e->getMessage() . "</div>";
        }
    }

    echo "<h2>7. RECOMENDACIONES:</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Para solucionar el problema:</strong></p>";
    echo "<ol>";
    echo "<li>Asegúrate de hacer login como escritor primero</li>";
    echo "<li>Verifica que el rol sea 'escritor' en la sesión</li>";
    echo "<li>Si faltan tablas, se crearán automáticamente</li>";
    echo "<li>El registro en 'escritores' se crea automáticamente si no existe</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR GENERAL: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Archivo: " . $e->getFile() . "</div>";
    echo "<div class='info'>Línea: " . $e->getLine() . "</div>";
}

echo "</div></body></html>";
?>
