<?php
echo "<h1>🔧 SOLUCIÓN: Error de Memoria PHP</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; border-left: 5px solid #17a2b8; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
.btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn:hover { opacity: 0.8; }
</style>";

echo "<div class='panel error'>";
echo "<h2>🚨 Problema Identificado</h2>";
echo "<p><strong>Error:</strong> 'Allowed memory size of 536870912 bytes exhausted'</p>";
echo "<p><strong>Causa:</strong> PHP se queda sin memoria (512MB) al procesar archivos grandes</p>";
echo "<p><strong>Impacto:</strong> Los scripts fallan, incluyendo la subida de libros</p>";
echo "</div>";

// Verificar configuración actual
echo "<div class='panel info'>";
echo "<h3>📊 Configuración Actual de PHP</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Configuración</th><th>Valor Actual</th><th>Recomendado</th><th>Estado</th></tr>";

$configs = [
    'memory_limit' => ['actual' => ini_get('memory_limit'), 'recomendado' => '1024M'],
    'upload_max_filesize' => ['actual' => ini_get('upload_max_filesize'), 'recomendado' => '50M'],
    'post_max_size' => ['actual' => ini_get('post_max_size'), 'recomendado' => '60M'],
    'max_execution_time' => ['actual' => ini_get('max_execution_time'), 'recomendado' => '300'],
    'max_input_time' => ['actual' => ini_get('max_input_time'), 'recomendado' => '300']
];

foreach ($configs as $config => $values) {
    $status = "⚠️ Revisar";
    if ($config === 'memory_limit') {
        $current_bytes = parse_size($values['actual']);
        $recommended_bytes = parse_size($values['recomendado']);
        $status = $current_bytes >= $recommended_bytes ? "✅ OK" : "❌ Insuficiente";
    }
    
    echo "<tr>";
    echo "<td>$config</td>";
    echo "<td>{$values['actual']}</td>";
    echo "<td>{$values['recomendado']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

echo "</table>";
echo "</div>";

echo "<div class='panel success'>";
echo "<h3>🔧 SOLUCIÓN 1: Configurar PHP.ini (Recomendado)</h3>";
echo "<p><strong>Pasos para aumentar la memoria:</strong></p>";
echo "<ol>";
echo "<li><strong>Abrir archivo php.ini:</strong>";
echo "<div class='code'>C:\\xampp\\php\\php.ini</div></li>";
echo "<li><strong>Buscar y cambiar estas líneas:</strong>";
echo "<div class='code'>";
echo "memory_limit = 1024M\n";
echo "upload_max_filesize = 50M\n";
echo "post_max_size = 60M\n";
echo "max_execution_time = 300\n";
echo "max_input_time = 300";
echo "</div></li>";
echo "<li><strong>Guardar el archivo</strong></li>";
echo "<li><strong>Reiniciar Apache en XAMPP</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div class='panel warning'>";
echo "<h3>⚡ SOLUCIÓN 2: Script de Auto-Configuración</h3>";
echo "<p>Ejecutar script automático para ajustar PHP:</p>";

if (isset($_POST['auto_config'])) {
    echo "<div class='info'>";
    echo "<h4>🔄 Aplicando configuración automática...</h4>";
    
    $php_ini_path = 'C:\\xampp\\php\\php.ini';
    
    if (file_exists($php_ini_path)) {
        $php_ini_content = file_get_contents($php_ini_path);
        
        // Crear backup
        $backup_path = $php_ini_path . '.backup.' . date('Ymd_His');
        file_put_contents($backup_path, $php_ini_content);
        echo "<p>✅ Backup creado: $backup_path</p>";
        
        // Aplicar cambios
        $changes = [
            '/memory_limit\s*=\s*[^\r\n]+/' => 'memory_limit = 1024M',
            '/upload_max_filesize\s*=\s*[^\r\n]+/' => 'upload_max_filesize = 50M',
            '/post_max_size\s*=\s*[^\r\n]+/' => 'post_max_size = 60M',
            '/max_execution_time\s*=\s*[^\r\n]+/' => 'max_execution_time = 300',
            '/max_input_time\s*=\s*[^\r\n]+/' => 'max_input_time = 300'
        ];
        
        foreach ($changes as $pattern => $replacement) {
            $php_ini_content = preg_replace($pattern, $replacement, $php_ini_content);
        }
        
        if (file_put_contents($php_ini_path, $php_ini_content)) {
            echo "<p>✅ Configuración aplicada exitosamente</p>";
            echo "<div class='success'>";
            echo "<h4>🎉 ¡Configuración Completada!</h4>";
            echo "<p><strong>AHORA DEBES:</strong></p>";
            echo "<ol>";
            echo "<li>🔄 <strong>Reiniciar Apache</strong> en el panel de XAMPP</li>";
            echo "<li>✅ <strong>Verificar</strong> que Apache se reinicie correctamente</li>";
            echo "<li>🧪 <strong>Probar</strong> la subida de archivos nuevamente</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<p>❌ Error al escribir el archivo php.ini</p>";
        }
    } else {
        echo "<p>❌ No se encontró el archivo php.ini en: $php_ini_path</p>";
    }
    echo "</div>";
}

if (!isset($_POST['auto_config'])) {
    echo "<form method='POST'>";
    echo "<button type='submit' name='auto_config' value='1' class='btn btn-warning'>⚙️ Aplicar Auto-Configuración</button>";
    echo "</form>";
    echo "<p><small>⚠️ Se creará un backup automático del php.ini actual</small></p>";
}
echo "</div>";

echo "<div class='panel'>";
echo "<h3>🧪 SOLUCIÓN 3: Script de Subida Optimizado</h3>";
echo "<p>Mientras tanto, un script ligero que use menos memoria:</p>";

echo "<form method='POST' enctype='multipart/form-data' style='border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📤 Subida Ligera de Libro</h4>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Título:</label><br>";
echo "<input type='text' name='titulo' required style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Descripción:</label><br>";
echo "<textarea name='descripcion' required style='width: 100%; padding: 5px; height: 60px;'></textarea>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Precio (COP):</label><br>";
echo "<input type='number' name='precio' min='1000' required style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>PDF (máximo 10MB):</label><br>";
echo "<input type='file' name='archivo_pdf' accept='.pdf' required style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Portada JPG (opcional, máximo 2MB):</label><br>";
echo "<input type='file' name='portada' accept='image/*' style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<button type='submit' name='subir_libro_ligero' value='1' class='btn btn-success'>📚 Subir Libro (Versión Ligera)</button>";
echo "</form>";

// Procesar subida ligera
if (isset($_POST['subir_libro_ligero'])) {
    echo "<div class='info'>";
    echo "<h4>📚 Procesando subida ligera...</h4>";
    
    try {
        // Configurar límites temporales más conservadores
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 120);
        
        require_once 'config/database.php';
        
        // Validaciones básicas
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        
        if (empty($titulo) || empty($descripcion) || $precio <= 0) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        // Procesar PDF
        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Archivo PDF es obligatorio");
        }
        
        $pdf_file = $_FILES['archivo_pdf'];
        if ($pdf_file['size'] > 10 * 1024 * 1024) {
            throw new Exception("El PDF no puede superar 10MB");
        }
        
        // Crear directorio si no existe
        if (!is_dir('uploads/libros/')) {
            mkdir('uploads/libros/', 0777, true);
        }
        
        // Mover PDF
        $pdf_name = 'libro_' . uniqid() . '_' . time() . '.pdf';
        $pdf_path = 'uploads/libros/' . $pdf_name;
        
        if (!move_uploaded_file($pdf_file['tmp_name'], $pdf_path)) {
            throw new Exception("Error al subir el PDF");
        }
        
        // Procesar portada (opcional)
        $portada_name = null;
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $portada_file = $_FILES['portada'];
            if ($portada_file['size'] <= 2 * 1024 * 1024) {
                if (!is_dir('uploads/portadas/')) {
                    mkdir('uploads/portadas/', 0777, true);
                }
                $portada_name = 'portada_' . uniqid() . '_' . time() . '.jpg';
                move_uploaded_file($portada_file['tmp_name'], 'uploads/portadas/' . $portada_name);
            }
        }
        
        // Insertar en BD
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO libros (autor_id, titulo, descripcion, precio, precio_afiliado, comision_porcentaje, imagen_portada, archivo_original, estado, fecha_registro) 
            VALUES (101, ?, ?, ?, ?, 30, ?, ?, 'pendiente_revision', NOW())
        ");
        
        $precio_afiliado = $precio * 0.7;
        $stmt->execute([$titulo, $descripcion, $precio, $precio_afiliado, $portada_name, $pdf_name]);
        
        $libro_id = $pdo->lastInsertId();
        
        echo "<div class='success'>";
        echo "<h4>🎉 ¡Libro Subido Exitosamente!</h4>";
        echo "<p><strong>ID del libro:</strong> #$libro_id</p>";
        echo "<p><strong>Título:</strong> $titulo</p>";
        echo "<p><strong>Archivo PDF:</strong> $pdf_name</p>";
        if ($portada_name) echo "<p><strong>Portada:</strong> $portada_name</p>";
        echo "<p><strong>Estado:</strong> Pendiente de revisión</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h4>❌ Error en Subida Ligera</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<div class='panel success'>";
echo "<h3>🎯 Resumen de Soluciones</h3>";
echo "<ol>";
echo "<li><strong>🔧 Configurar PHP.ini</strong> - Aumentar memory_limit a 1024M (RECOMENDADO)</li>";
echo "<li><strong>⚡ Auto-configuración</strong> - Script automático para cambiar php.ini</li>";
echo "<li><strong>🧪 Subida ligera</strong> - Script optimizado que usa menos memoria</li>";
echo "</ol>";
echo "<p><strong>Después de cualquier cambio:</strong> Reinicia Apache en XAMPP</p>";
echo "</div>";
?>