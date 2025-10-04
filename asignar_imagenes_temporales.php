<?php
/**
 * Solución: Asignar imágenes temporales a libros sin portada
 * Solo para libros que no tienen imagen_portada (NULL)
 * NO afecta los libros que ya tienen portadas subidas por escritores
 */

require_once 'config/database.php';

echo "<h2>🖼️ Asignación de Imágenes Temporales</h2>";
echo "<p><strong>Objetivo:</strong> Asignar imágenes diferentes a libros que NO tienen portada subida por el escritor.</p>";

try {
    $conn = getDBConnection();
    
    // 1. Verificar estado actual
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Estado Actual:</h3>";
    $sin_portada = 0;
    $con_portada = 0;
    
    foreach ($libros as $libro) {
        if (empty($libro['imagen_portada'])) {
            $sin_portada++;
        } else {
            $con_portada++;
        }
    }
    
    echo "<ul>";
    echo "<li>📖 <strong>Libros sin portada (NULL):</strong> $sin_portada</li>";
    echo "<li>🖼️ <strong>Libros con portada:</strong> $con_portada</li>";
    echo "</ul>";
    
    if ($sin_portada == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>¡Perfecto!</strong> Todos los libros tienen portada asignada.";
        echo "</div>";
        return;
    }
    
    // 2. Buscar imágenes disponibles en images/
    $imagenes_disponibles = [];
    $extensiones = ['jpg', 'jpeg', 'png', 'gif'];
    
    foreach ($extensiones as $ext) {
        $archivos = glob("images/*.$ext");
        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            if ($nombre !== 'default-book.jpg' && $nombre !== 'default-author.jpg') {
                $imagenes_disponibles[] = $nombre;
            }
        }
    }
    
    echo "<h3>🗂️ Imágenes disponibles en images/:</h3>";
    if (empty($imagenes_disponibles)) {
        echo "<p style='color: orange;'>⚠️ No hay imágenes adicionales en la carpeta images/</p>";
        echo "<p>Solo se usará default-book.jpg para libros sin portada.</p>";
        return;
    }
    
    echo "<ul>";
    foreach ($imagenes_disponibles as $imagen) {
        echo "<li>🖼️ $imagen</li>";
    }
    echo "</ul>";
    
    // 3. Asignar imágenes solo a libros SIN portada
    echo "<h3>🔄 Asignando imágenes temporales:</h3>";
    
    $libros_sin_portada = array_filter($libros, function($libro) {
        return empty($libro['imagen_portada']);
    });
    
    $contador = 0;
    foreach ($libros_sin_portada as $libro) {
        // Usar imagen rotatoria
        $imagen_asignada = $imagenes_disponibles[$contador % count($imagenes_disponibles)];
        
        $updateQuery = "UPDATE libros SET imagen_portada = ? WHERE id = ? AND imagen_portada IS NULL";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$imagen_asignada, $libro['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo "<div style='background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "✅ <strong>Libro ID {$libro['id']}:</strong> '{$libro['titulo']}' → $imagen_asignada";
            echo "</div>";
        }
        
        $contador++;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>✅ Proceso completado!</strong><br>";
    echo "• Libros procesados: " . count($libros_sin_portada) . "<br>";
    echo "• Solo se modificaron libros SIN portada propia<br>";
    echo "• Los libros con portadas subidas por escritores NO se tocaron";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='tienda.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Ver Tienda</a> ";
echo "<a href='diagnostico_imagenes_real.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Ver Diagnóstico</a>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<strong>⚠️ Importante:</strong><br>";
echo "• Esta solución NO interfiere con la subida de portadas por escritores<br>";
echo "• Solo asigna imágenes temporales a libros que no tienen portada<br>";
echo "• Los escritores pueden seguir subiendo sus propias portadas normalmente";
echo "</div>";
?>

<style>
h3 { color: #333; margin-top: 30px; }
</style>