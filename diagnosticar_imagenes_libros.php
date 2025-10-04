<?php
require_once 'config/database.php';

echo "<h1>🔍 DIAGNÓSTICO: IMÁGENES DE LIBROS</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; border-left: 5px solid #17a2b8; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.img-preview { max-width: 100px; max-height: 100px; border: 1px solid #ddd; }
.img-missing { background: #ffcccc; }
.img-exists { background: #ccffcc; }
</style>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar datos en la base de datos
    echo "<div class='panel info'>";
    echo "<h2>📊 1. DATOS DE IMÁGENES EN BASE DE DATOS</h2>";
    
    $query = "SELECT id, titulo, imagen_portada, estado FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $pdo->query($query);
    $libros_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Imagen en BD</th><th>Archivo Existe</th><th>Preview</th></tr>";
    
    foreach ($libros_bd as $libro) {
        $imagen_nombre = $libro['imagen_portada'];
        $imagen_path = "images/" . ($imagen_nombre ?: 'default-book.jpg');
        $archivo_existe = file_exists($imagen_path);
        
        echo "<tr>";
        echo "<td>#{$libro['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($libro['titulo'], 0, 30)) . "...</td>";
        echo "<td>" . ($imagen_nombre ?: '<em>NULL/Vacío</em>') . "</td>";
        echo "<td class='" . ($archivo_existe ? 'img-exists' : 'img-missing') . "'>" . ($archivo_existe ? '✅ SÍ' : '❌ NO') . "</td>";
        echo "<td>";
        if ($archivo_existe) {
            echo "<img src='$imagen_path' class='img-preview' alt='Preview'>";
        } else {
            echo "🚫 No disponible";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 2. Verificar qué devuelve la API
    echo "<div class='panel warning'>";
    echo "<h2>🌐 2. DATOS DE LA API disponibles.php</h2>";
    
    $api_url = 'http://localhost/publiery/api/libros/disponibles.php';
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    if ($api_response) {
        $api_data = json_decode($api_response, true);
        if ($api_data && $api_data['success'] && !empty($api_data['libros'])) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Título</th><th>Imagen API</th><th>¿Coincide BD?</th></tr>";
            
            foreach ($api_data['libros'] as $api_libro) {
                // Buscar el libro correspondiente en BD
                $libro_bd = null;
                foreach ($libros_bd as $bd_libro) {
                    if ($bd_libro['id'] == $api_libro['id']) {
                        $libro_bd = $bd_libro;
                        break;
                    }
                }
                
                $imagen_api = $api_libro['imagen_portada'] ?? 'N/A';
                $imagen_bd = $libro_bd ? ($libro_bd['imagen_portada'] ?: 'NULL') : 'N/A';
                $coincide = ($imagen_api === $imagen_bd) || ($imagen_api === 'default-book.jpg' && !$imagen_bd);
                
                echo "<tr>";
                echo "<td>#{$api_libro['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($api_libro['titulo'], 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($imagen_api) . "</td>";
                echo "<td" . ($coincide ? " class='img-exists'>✅ SÍ" : " class='img-missing'>❌ NO") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Error en API o no hay libros</div>";
        }
    } else {
        echo "<div class='error'>❌ No se pudo conectar a la API</div>";
    }
    echo "</div>";
    
    // 3. Verificar estructura de carpeta images
    echo "<div class='panel info'>";
    echo "<h2>📁 3. ARCHIVOS EN CARPETA images/</h2>";
    
    $images_dir = 'images/';
    if (is_dir($images_dir)) {
        $files = scandir($images_dir);
        $image_files = array_filter($files, function($file) {
            return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
        });
        
        echo "<p><strong>Total de imágenes encontradas:</strong> " . count($image_files) . "</p>";
        
        if (count($image_files) > 0) {
            echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin: 20px 0;'>";
            foreach (array_slice($image_files, 0, 20) as $file) { // Solo mostrar las primeras 20
                echo "<div style='text-align: center; border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
                echo "<img src='images/$file' style='max-width: 100px; max-height: 100px; display: block; margin: 0 auto 5px;' alt='$file'>";
                echo "<small>" . htmlspecialchars($file) . "</small>";
                echo "</div>";
            }
            echo "</div>";
            
            if (count($image_files) > 20) {
                echo "<p><em>... y " . (count($image_files) - 20) . " imágenes más</em></p>";
            }
        } else {
            echo "<div class='warning'>⚠️ No se encontraron archivos de imagen en la carpeta</div>";
        }
    } else {
        echo "<div class='error'>❌ La carpeta 'images/' no existe</div>";
    }
    echo "</div>";
    
    // 4. Análisis y recomendaciones
    echo "<div class='panel warning'>";
    echo "<h2>🎯 4. ANÁLISIS Y POSIBLES PROBLEMAS</h2>";
    
    $libros_sin_imagen = 0;
    $libros_imagen_no_existe = 0;
    
    foreach ($libros_bd as $libro) {
        if (!$libro['imagen_portada']) {
            $libros_sin_imagen++;
        } else {
            $imagen_path = "images/" . $libro['imagen_portada'];
            if (!file_exists($imagen_path)) {
                $libros_imagen_no_existe++;
            }
        }
    }
    
    echo "<h3>📊 Estadísticas:</h3>";
    echo "<ul>";
    echo "<li><strong>Total de libros:</strong> " . count($libros_bd) . "</li>";
    echo "<li><strong>Libros sin imagen en BD:</strong> $libros_sin_imagen</li>";
    echo "<li><strong>Libros con imagen que no existe:</strong> $libros_imagen_no_existe</li>";
    echo "<li><strong>Libros que usan default-book.jpg:</strong> " . ($libros_sin_imagen + $libros_imagen_no_existe) . "</li>";
    echo "</ul>";
    
    echo "<h3>🔍 Posibles causas del problema:</h3>";
    echo "<ul>";
    echo "<li>📁 <strong>Nombres de archivo incorrectos:</strong> La BD contiene nombres que no coinciden con archivos reales</li>";
    echo "<li>🖼️ <strong>Falta de imágenes únicas:</strong> Muchos libros no tienen imagen propia</li>";
    echo "<li>🔄 <strong>Cache del navegador:</strong> Las imágenes pueden estar en cache</li>";
    echo "<li>📂 <strong>Ruta incorrecta:</strong> El path a las imágenes puede estar mal configurado</li>";
    echo "</ul>";
    
    if ($libros_sin_imagen > count($libros_bd) * 0.5) {
        echo "<div class='warning'><strong>⚠️ PROBLEMA PRINCIPAL:</strong> Más del 50% de los libros no tienen imagen propia, por lo que todos muestran default-book.jpg</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='panel error'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><em>Diagnóstico completado: " . date('Y-m-d H:i:s') . "</em></p>";
?>