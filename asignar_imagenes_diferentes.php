<?php
/**
 * Solución final: Asignar imágenes diferentes de images/ a libros sin portada propia
 */

require_once 'config/database.php';

echo "<h2>🎨 Asignación de Imágenes Diferentes</h2>";

try {
    $conn = getDBConnection();
    
    // 1. Obtener libros publicados
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Obtener imágenes disponibles en images/
    $imagenes = [];
    $extensiones = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($extensiones as $ext) {
        $archivos = glob("images/*.$ext");
        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $imagenes[] = $nombre;
        }
    }
    
    echo "<h3>📁 Imágenes disponibles en images/:</h3>";
    echo "<ul>";
    foreach ($imagenes as $img) {
        echo "<li>$img</li>";
    }
    echo "</ul>";
    
    echo "<h3>📚 Asignando imágenes a libros:</h3>";
    
    $contador = 0;
    foreach ($libros as $libro) {
        // Solo asignar a libros sin imagen o con default-book.jpg
        if (empty($libro['imagen_portada']) || $libro['imagen_portada'] === 'default-book.jpg') {
            
            // Asignar imagen de forma rotativa
            $imagen_asignada = $imagenes[$contador % count($imagenes)];
            
            $updateQuery = "UPDATE libros SET imagen_portada = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([$imagen_asignada, $libro['id']]);
            
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "✅ <strong>Libro {$libro['id']}:</strong> '{$libro['titulo']}' → $imagen_asignada";
            echo "</div>";
            
            $contador++;
        } else {
            echo "<div style='background: #e2e3e5; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "⏭️ <strong>Libro {$libro['id']}:</strong> '{$libro['titulo']}' → Ya tiene portada: {$libro['imagen_portada']}";
            echo "</div>";
        }
    }
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>✅ Proceso completado!</strong><br>";
    echo "Ahora cada libro tendrá una imagen diferente de la carpeta images/";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='tienda-lectores.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Ver Tienda</a>";
echo "</div>";
?>

<style>
h3 { color: #333; margin-top: 30px; }
</style>