<?php
/**
 * DIAGNÓSTICO DE ERRORES EN EL REGISTRO
 * Para identificar qué está causando el error 500
 */

echo "<!DOCTYPE html><html><head><title>Diagnóstico de Registro</title>";
echo "<style>
    body { font-family: monospace; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    .error { color: #f44336; background: #fde8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .success { color: #4CAF50; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #ff9800; background: #fff3e0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 DIAGNÓSTICO DE ERRORES EN EL REGISTRO</h1>";

// 1. Verificar archivos requeridos
echo "<h2>1. VERIFICANDO ARCHIVOS REQUERIDOS:</h2>";

$archivos_requeridos = [
    'config/database.php',
    'config/email.php', 
    'config/auth_functions.php',
    'api/auth/register.php'
];

foreach ($archivos_requeridos as $archivo) {
    if (file_exists($archivo)) {
        echo "<div class='success'>✅ $archivo - EXISTE</div>";
    } else {
        echo "<div class='error'>❌ $archivo - NO EXISTE</div>";
    }
}

// 2. Verificar funciones necesarias
echo "<h2>2. VERIFICANDO FUNCIONES NECESARIAS:</h2>";

try {
    require_once 'config/database.php';
    echo "<div class='success'>✅ database.php cargado correctamente</div>";
    
    // Verificar conexión a BD
    try {
        $pdo = getDBConnection();
        echo "<div class='success'>✅ Conexión a BD exitosa</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error de conexión a BD: " . $e->getMessage() . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando database.php: " . $e->getMessage() . "</div>";
}

// Verificar config/auth_functions.php
try {
    if (file_exists('config/auth_functions.php')) {
        require_once 'config/auth_functions.php';
        echo "<div class='success'>✅ auth_functions.php cargado correctamente</div>";
        
        // Verificar funciones específicas
        $funciones_necesarias = ['sanitizeInput', 'validateEmail', 'validatePassword', 'jsonResponse', 'generateSecureToken', 'logActivity'];
        
        foreach ($funciones_necesarias as $funcion) {
            if (function_exists($funcion)) {
                echo "<div class='success'>✅ Función $funcion() - EXISTE</div>";
            } else {
                echo "<div class='error'>❌ Función $funcion() - NO EXISTE</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ auth_functions.php no existe</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando auth_functions.php: " . $e->getMessage() . "</div>";
}

// 3. Verificar estructura de BD
echo "<h2>3. VERIFICANDO ESTRUCTURA DE BASE DE DATOS:</h2>";

try {
    $pdo = getDBConnection();
    
    $tablas_necesarias = ['usuarios', 'afiliados', 'escritores', 'lectores'];
    
    foreach ($tablas_necesarias as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Tabla '$tabla' - EXISTE</div>";
            
            // Verificar columnas de la tabla usuarios
            if ($tabla === 'usuarios') {
                $stmt = $pdo->query("DESCRIBE usuarios");
                $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<div class='info'>Columnas en usuarios: " . implode(', ', $columnas) . "</div>";
                
                $columnas_necesarias = ['id', 'nombre', 'email', 'documento', 'password', 'rol', 'estado'];
                foreach ($columnas_necesarias as $columna) {
                    if (in_array($columna, $columnas)) {
                        echo "<div class='success'>✅ Columna '$columna' - EXISTE</div>";
                    } else {
                        echo "<div class='error'>❌ Columna '$columna' - NO EXISTE</div>";
                    }
                }
            }
        } else {
            echo "<div class='error'>❌ Tabla '$tabla' - NO EXISTE</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error verificando BD: " . $e->getMessage() . "</div>";
}

// 4. Probar registro manualmente
echo "<h2>4. PRUEBA MANUAL DE REGISTRO:</h2>";

if ($_POST && isset($_POST['test_registro'])) {
    echo "<h3>Ejecutando prueba de registro...</h3>";
    
    try {
        // Simular datos de registro
        $_POST = [
            'nombre' => 'Usuario Prueba',
            'email' => 'test@example.com',
            'documento' => 'TEST001',
            'password' => 'Test123456',
            'rol' => 'lector'
        ];
        
        // Capturar output
        ob_start();
        
        // Simular llamada a register.php
        include 'api/auth/register.php';
        
        $output = ob_get_clean();
        echo "<div class='info'>Salida del registro:</div>";
        echo "<pre>$output</pre>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error en prueba de registro: " . $e->getMessage() . "</div>";
        echo "<div class='info'>Archivo: " . $e->getFile() . "</div>";
        echo "<div class='info'>Línea: " . $e->getLine() . "</div>";
    }
}

// Mostrar logs de errores recientes
echo "<h2>5. LOGS DE ERRORES PHP:</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $logs = tail($error_log, 20);
    echo "<pre>$logs</pre>";
} else {
    echo "<div class='info'>No se encontró archivo de log de errores</div>";
}

echo "<h2>6. PROBAR REGISTRO:</h2>";
echo "<form method='POST'>";
echo "<button type='submit' name='test_registro'>🧪 Probar Registro</button>";
echo "</form>";

echo "</div></body></html>";

function tail($file, $lines = 10) {
    $handle = fopen($file, "r");
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines-$linecounter-1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    return implode("", array_reverse($text));
}
?>
