<?php
require_once 'config/database.php';

echo "<h1>🔧 ASIGNAR IMÁGENES EXISTENTES A LIBROS</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background-color: #d4edda; padding: 10px; margin: 10px 0; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; padding: 10px; margin: 10px 0; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; padding: 10px; margin: 10px 0; border-left: 5px solid #17a2b8; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

try {
    $pdo = getDBConnection();
    
    // Imágenes disponibles en la carpeta
    $imagenes_disponibles = [
        'ventas_sin_miedo.jpg',
        'mentalidad_emprendedora.jpg',
        'default-book.jpg' // Como última opción
    ];
    
    // Verificar que las imágenes existen
    echo "<div class='info'>";
    echo "<h2>📁 Verificando imágenes disponibles:</h2>";
    foreach ($imagenes_disponibles as $img) {
        $existe = file_exists("images/" . $img);
        echo "<p>" . ($existe ? "✅" : "❌") . " " . $img . "</p>";
    }
    echo "</div>";
    
    // Obtener libros que necesitan imágenes
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $pdo->query($query);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='warning'>";
    echo "<h2>🔄 Asignando imágenes a libros:</h2>";
    echo "</div>";
    
    $actualizaciones = [];
    
    foreach ($libros as $index => $libro) {
        // Asignar imagen según el índice (rotando entre las disponibles)
        $imagen_a_asignar = $imagenes_disponibles[$index % count($imagenes_disponibles)];
        
        // Solo actualizar si la imagen actual no existe
        $imagen_actual_path = $libro['imagen_portada'] ? "images/" . $libro['imagen_portada'] : null;
        $necesita_actualizacion = !$imagen_actual_path || !file_exists($imagen_actual_path);
        
        if ($necesita_actualizacion) {
            $actualizaciones[] = [
                'id' => $libro['id'],
                'titulo' => $libro['titulo'],
                'imagen_anterior' => $libro['imagen_portada'],
                'imagen_nueva' => $imagen_a_asignar
            ];
        }
    }
    
    // Mostrar plan de actualización
    if (!empty($actualizaciones)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Título</th><th>Imagen Anterior</th><th>Imagen Nueva</th></tr>";
        
        foreach ($actualizaciones as $update) {
            echo "<tr>";
            echo "<td>#{$update['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($update['titulo'], 0, 30)) . "...</td>";
            echo "<td>" . ($update['imagen_anterior'] ?: '<em>NULL</em>') . "</td>";
            echo "<td><strong>" . $update['imagen_nueva'] . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='warning'>";
        echo "<h3>⚠️ ANTES DE CONTINUAR:</h3>";
        echo "<p>Esto actualizará " . count($actualizaciones) . " libros con imágenes existentes.</p>";
        echo "<p><strong>¿Quieres proceder?</strong></p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='ejecutar' value='si' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>✅ SÍ, ACTUALIZAR</button>";
        echo "<button type='button' onclick='history.back()' style='background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>❌ CANCELAR</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Todos los libros ya tienen imágenes válidas</h3>";
        echo "</div>";
    }
    
    // Si se confirmó la ejecución
    if ($_POST['ejecutar'] === 'si') {
        echo "<div class='info'>";
        echo "<h2>🚀 EJECUTANDO ACTUALIZACIONES...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        
        $update_stmt = $pdo->prepare("UPDATE libros SET imagen_portada = ? WHERE id = ?");
        $exitosas = 0;
        
        foreach ($actualizaciones as $update) {
            try {
                $update_stmt->execute([$update['imagen_nueva'], $update['id']]);
                echo "<p>✅ Libro #{$update['id']}: " . htmlspecialchars($update['titulo']) . " → " . $update['imagen_nueva'] . "</p>";
                $exitosas++;
            } catch (Exception $e) {
                echo "<p>❌ Error en libro #{$update['id']}: " . $e->getMessage() . "</p>";
            }
        }
        
        $pdo->commit();
        
        echo "<div class='success'>";
        echo "<h3>🎉 ACTUALIZACIÓN COMPLETADA</h3>";
        echo "<p><strong>$exitosas de " . count($actualizaciones) . " libros actualizados exitosamente.</strong></p>";
        echo "<p>Ahora cada libro debería mostrar una imagen diferente en la tienda.</p>";
        echo "<p><a href='dashboard-afiliado.html' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Ver Tienda Actualizada</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 5px solid #dc3545;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>