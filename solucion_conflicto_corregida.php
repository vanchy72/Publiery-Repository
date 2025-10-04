<?php
/**
 * SOLUCIÓN: SQL sin conflictos de clave única
 * Eliminar registros existentes o usar INSERT con ON CONFLICT
 */

// Conexión a XAMPP
try {
    $xampp = new PDO('mysql:host=localhost;dbname=publiery_db;charset=utf8mb4', 'root', '');
    $xampp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🔧 SOLUCIÓN: Conflicto de Clave Única</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); }
            .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
            .exito { background: #f0fdf4; border: 3px solid #16a34a; border-radius: 15px; padding: 25px; margin: 20px 0; text-align: center; }
            .problema { background: #fef3c7; border: 3px solid #f59e0b; border-radius: 15px; padding: 25px; margin: 20px 0; }
            .solucion { background: #f3e8ff; border: 2px solid #7c3aed; border-radius: 10px; padding: 20px; margin: 20px 0; }
            textarea { width: 100%; height: 300px; font-family: monospace; padding: 15px; border: 2px solid #7c3aed; border-radius: 8px; background: #faf7ff; font-size: 13px; }
            .copy-btn { background: #7c3aed; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; margin: 15px 0; font-size: 16px; font-weight: bold; }
        </style>
    </head>
    <body>";
    
    echo "<div class='container'>";
    echo "<div class='exito'>";
    echo "<h1>✅ ¡EL SQL FUNCIONÓ!</h1>";
    echo "<p style='font-size: 1.2em;'>Los nombres de columnas están correctos</p>";
    echo "</div>";
    
    echo "<div class='problema'>";
    echo "<h3>⚠️ Problema identificado:</h3>";
    echo "<ul>";
    echo "<li><strong>Campo 'codigo_afiliado'</strong> tiene restricción UNIQUE</li>";
    echo "<li><strong>Ya existe un registro</strong> con valor vacío ('')</li>";
    echo "<li><strong>No podemos insertar</strong> otro registro con el mismo valor vacío</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='solucion'>";
    echo "<h3>🔧 SOLUCIÓN 1: Limpiar tabla primero</h3>";
    echo "<p>Ejecuta este comando ANTES del INSERT:</p>";
    echo "<textarea id='limpiar'>-- LIMPIAR TABLA USUARIOS
DELETE FROM usuarios;

-- REINICIAR SECUENCIA AUTO_INCREMENT
ALTER SEQUENCE usuarios_id_seq RESTART WITH 1;</textarea>";
    echo "<button class='copy-btn' onclick='copiar(\"limpiar\")'>📋 COPIAR LIMPIEZA</button>";
    echo "</div>";
    
    echo "<div class='solucion'>";
    echo "<h3>🔧 SOLUCIÓN 2: SQL con códigos únicos</h3>";
    echo "<p>Asignar códigos únicos a cada usuario:</p>";
    echo "<textarea id='sqlUnicos'>";
    
    // Obtener usuarios
    $stmt = $xampp->query("SELECT * FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuarios as $index => $usuario) {
        // Generar código único para cada usuario
        $codigoAfiliado = 'USER' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        
        $sql = 'INSERT INTO usuarios (';
        $sql .= 'nombre, email, password_hash, rol, ';
        $sql .= 'codigo_afiliado, telefono, direccion, fecha_registro, ';
        $sql .= 'estado, ultimo_acceso, email_verificado';
        $sql .= ') VALUES (';
        $sql .= "'" . addslashes($usuario['nombre']) . "', ";
        $sql .= "'" . addslashes($usuario['email']) . "', ";
        $sql .= "'" . addslashes($usuario['password']) . "', ";
        $sql .= "'" . addslashes($usuario['rol']) . "', ";
        $sql .= "'" . $codigoAfiliado . "', "; // código único
        $sql .= "'', "; // telefono vacío  
        $sql .= "'', "; // direccion vacía
        $sql .= "'" . $usuario['fecha_registro'] . "', ";
        $sql .= "'" . addslashes($usuario['estado']) . "', ";
        $sql .= ($usuario['fecha_ultimo_login'] ? "'" . $usuario['fecha_ultimo_login'] . "'" : "NULL") . ", ";
        $sql .= "FALSE"; // email_verificado = false
        $sql .= ');';
        
        echo $sql . "\n\n";
    }
    
    echo "</textarea>";
    echo "<button class='copy-btn' onclick='copiar(\"sqlUnicos\")'>📋 COPIAR SQL CON CÓDIGOS ÚNICOS</button>";
    echo "</div>";
    
    echo "<div class='exito'>";
    echo "<h2>🎯 RECOMENDACIÓN</h2>";
    echo "<p><strong>Usa la SOLUCIÓN 1</strong>: Limpia la tabla primero</p>";
    echo "<p>Luego usa la SOLUCIÓN 2: SQL con códigos únicos</p>";
    echo "<p><strong>Total usuarios: " . count($usuarios) . "</strong></p>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<script>
    function copiar(id) {
        const textarea = document.getElementById(id);
        textarea.select();
        document.execCommand('copy');
        alert('¡SQL copiado! Ejecuta primero la limpieza, luego el INSERT.');
    }
    </script>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>