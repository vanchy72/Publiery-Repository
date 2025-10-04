<?php
require_once 'config/database.php';

echo "<h1>🎨 GENERAR IMÁGENES ÚNICAS PARA LIBROS</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background-color: #d4edda; padding: 10px; margin: 10px 0; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; padding: 10px; margin: 10px 0; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; padding: 10px; margin: 10px 0; border-left: 5px solid #17a2b8; }
.error { background-color: #f8d7da; padding: 10px; margin: 10px 0; border-left: 5px solid #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.preview { max-width: 100px; max-height: 120px; margin: 5px; }
</style>";

function generarImagenLibro($titulo, $id, $ancho = 300, $alto = 400) {
    // Crear imagen
    $imagen = imagecreate($ancho, $alto);
    
    // Definir colores basados en el ID del libro para variedad
    $colores_fondo = [
        [64, 81, 181],   // Indigo
        [156, 39, 176],  // Purple
        [63, 81, 181],   // Deep Purple
        [33, 150, 243],  // Blue
        [0, 150, 136],   // Teal
        [76, 175, 80],   // Green
        [255, 152, 0],   // Orange
        [244, 67, 54],   // Red
        [121, 85, 72],   // Brown
        [96, 125, 139]   // Blue Grey
    ];
    
    $color_index = $id % count($colores_fondo);
    $color_fondo = $colores_fondo[$color_index];
    
    // Asignar colores
    $fondo = imagecolorallocate($imagen, $color_fondo[0], $color_fondo[1], $color_fondo[2]);
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $negro = imagecolorallocate($imagen, 0, 0, 0);
    $gris_claro = imagecolorallocate($imagen, 240, 240, 240);
    
    // Rellenar fondo
    imagefill($imagen, 0, 0, $fondo);
    
    // Añadir degradado simple (rectángulos con opacidad)
    for ($i = 0; $i < 50; $i++) {
        $alpha = imagecolorallocatealpha($imagen, 255, 255, 255, 100 + $i);
        imagefilledrectangle($imagen, 0, $i * 2, $ancho, ($i * 2) + 2, $alpha);
    }
    
    // Añadir marco
    imagerectangle($imagen, 5, 5, $ancho - 6, $alto - 6, $blanco);
    imagerectangle($imagen, 10, 10, $ancho - 11, $alto - 11, $blanco);
    
    // Preparar texto del título
    $titulo_lineas = wordwrap($titulo, 20, "\n", true);
    $lineas = explode("\n", $titulo_lineas);
    
    // Añadir título (simulando fuente con imagestring)
    $y_start = 80;
    foreach ($lineas as $i => $linea) {
        $x = (imagesx($imagen) - (strlen($linea) * 10)) / 2; // Centrar aproximadamente
        imagestring($imagen, 4, $x, $y_start + ($i * 25), $linea, $blanco);
    }
    
    // Añadir "ID" en la esquina
    imagestring($imagen, 2, 15, 25, "ID: $id", $blanco);
    
    // Añadir decoración simple
    imagestring($imagen, 3, $ancho - 80, $alto - 30, "PUBLIERY", $gris_claro);
    
    return $imagen;
}

try {
    $pdo = getDBConnection();
    
    // Verificar si GD está disponible
    if (!extension_loaded('gd')) {
        echo "<div class='error'>";
        echo "<h3>❌ Extensión GD no disponible</h3>";
        echo "<p>Para generar imágenes automáticamente, necesitas la extensión GD de PHP.</p>";
        echo "<p><strong>Alternativa:</strong> Usa el script anterior para asignar imágenes existentes.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<div class='success'>";
    echo "<h2>✅ Extensión GD disponible - Procediendo...</h2>";
    echo "</div>";
    
    // Obtener libros
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $pdo->query($query);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h2>🎨 Generando imágenes únicas...</h2>";
    echo "</div>";
    
    $imagenes_generadas = [];
    
    foreach ($libros as $libro) {
        $nombre_archivo = "libro_" . $libro['id'] . "_" . time() . ".png";
        $ruta_completa = "images/" . $nombre_archivo;
        
        // Generar imagen
        $imagen = generarImagenLibro($libro['titulo'], $libro['id']);
        
        // Guardar imagen
        if (imagepng($imagen, $ruta_completa)) {
            $imagenes_generadas[] = [
                'id' => $libro['id'],
                'titulo' => $libro['titulo'],
                'archivo' => $nombre_archivo,
                'ruta' => $ruta_completa
            ];
            echo "<p>✅ Generada: " . htmlspecialchars($libro['titulo']) . " → $nombre_archivo</p>";
        } else {
            echo "<p>❌ Error generando imagen para: " . htmlspecialchars($libro['titulo']) . "</p>";
        }
        
        // Liberar memoria
        imagedestroy($imagen);
    }
    
    if (!empty($imagenes_generadas)) {
        echo "<div class='warning'>";
        echo "<h3>🖼️ Previsualización de imágenes generadas:</h3>";
        echo "<div style='display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0;'>";
        
        foreach ($imagenes_generadas as $img) {
            echo "<div style='text-align: center; border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
            echo "<img src='{$img['ruta']}' class='preview' alt='{$img['titulo']}'>";
            echo "<br><small>ID: {$img['id']}</small>";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<h3>💾 ¿Actualizar base de datos?</h3>";
        echo "<p>Se generaron " . count($imagenes_generadas) . " imágenes únicas.</p>";
        echo "<p><strong>¿Quieres actualizar la base de datos para usar estas nuevas imágenes?</strong></p>";
        echo "<form method='POST'>";
        foreach ($imagenes_generadas as $img) {
            echo "<input type='hidden' name='imagenes[{$img['id']}]' value='{$img['archivo']}'>";
        }
        echo "<button type='submit' name='actualizar_bd' value='si' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>✅ SÍ, ACTUALIZAR BD</button>";
        echo "<button type='button' onclick='history.back()' style='background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>❌ CANCELAR</button>";
        echo "</form>";
        echo "</div>";
    }
    
    // Actualizar base de datos si se confirmó
    if ($_POST['actualizar_bd'] === 'si' && !empty($_POST['imagenes'])) {
        echo "<div class='info'>";
        echo "<h2>💾 Actualizando base de datos...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        $update_stmt = $pdo->prepare("UPDATE libros SET imagen_portada = ? WHERE id = ?");
        $actualizados = 0;
        
        foreach ($_POST['imagenes'] as $libro_id => $nombre_archivo) {
            try {
                $update_stmt->execute([$nombre_archivo, $libro_id]);
                echo "<p>✅ Libro ID $libro_id actualizado con imagen: $nombre_archivo</p>";
                $actualizados++;
            } catch (Exception $e) {
                echo "<p>❌ Error actualizando libro ID $libro_id: " . $e->getMessage() . "</p>";
            }
        }
        
        $pdo->commit();
        
        echo "<div class='success'>";
        echo "<h3>🎉 ¡PROCESO COMPLETADO!</h3>";
        echo "<p><strong>$actualizados libros actualizados con imágenes únicas.</strong></p>";
        echo "<p>Cada libro ahora tiene su propia imagen generada automáticamente.</p>";
        echo "<p><a href='dashboard-afiliado.html' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Ver Tienda con Imágenes Únicas</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div class='error'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>