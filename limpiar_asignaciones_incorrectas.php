<?php
/**
 * Limpiar asignaciones automáticas incorrectas de imágenes
 * Solo eliminar imagen_portada que apunte a archivos que no existen
 */

require_once 'config/database.php';

echo "<h2>🧹 Limpieza de Asignaciones Automáticas Incorrectas</h2>";

try {
    $conn = getDBConnection();
    
    // Obtener todos los libros con imagen_portada
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE imagen_portada IS NOT NULL AND imagen_portada != ''";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Verificando libros con imagen_portada asignada:</h3>";
    
    $problemas = 0;
    $corregidos = 0;
    
    foreach ($libros as $libro) {
        $imagen = $libro['imagen_portada'];
        $existe_uploads = file_exists("uploads/portadas/$imagen");
        $existe_images = file_exists("images/$imagen");
        
        if (!$existe_uploads && !$existe_images) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "❌ <strong>Libro ID {$libro['id']}:</strong> '{$libro['titulo']}'<br>";
            echo "   → Imagen asignada: '$imagen' (NO EXISTE)<br>";
            echo "   → Eliminando asignación automática...";
            
            // Limpiar la asignación incorrecta
            $updateQuery = "UPDATE libros SET imagen_portada = NULL WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$libro['id']]);
            
            if ($updateStmt->rowCount() > 0) {
                echo " ✅ Corregido";
                $corregidos++;
            } else {
                echo " ❌ Error";
            }
            echo "</div>";
            $problemas++;
        } else {
            $ubicacion = $existe_uploads ? 'uploads/portadas/' : 'images/';
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "✅ <strong>Libro ID {$libro['id']}:</strong> '{$libro['titulo']}'<br>";
            echo "   → Imagen: '$imagen' (EXISTE en $ubicacion)";
            echo "</div>";
        }
    }
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>📋 Resumen:</strong><br>";
    echo "• Total libros verificados: " . count($libros) . "<br>";
    echo "• Problemas encontrados: $problemas<br>";
    echo "• Asignaciones incorrectas corregidas: $corregidos<br>";
    
    if ($problemas == 0) {
        echo "• ✅ <strong>Todas las asignaciones son válidas</strong>";
    } elseif ($corregidos == $problemas) {
        echo "• ✅ <strong>Todos los problemas fueron corregidos</strong>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='tienda-lectores.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Ver Tienda</a> ";
echo "<a href='diagnostico_imagenes_real.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Diagnóstico</a>";
echo "</div>";
?>

<style>
h3 { color: #333; margin-top: 30px; }
</style>