<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>📊 ANÁLISIS DETALLADO: IMÁGENES DE LIBROS</h1>";
    echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .table-container { margin: 20px 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .missing { background-color: #ffebee; color: #c62828; }
    .exists { background-color: #e8f5e8; color: #2e7d32; }
    .default { background-color: #fff3e0; color: #ef6c00; }
    </style>";
    
    $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $pdo->query($query);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='table-container'>";
    echo "<h2>📚 Estado de Imágenes por Libro</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Imagen en BD</th><th>Archivo</th><th>Estado</th></tr>";
    
    foreach ($libros as $libro) {
        $imagen = $libro['imagen_portada'];
        $imagen_path = $imagen ? "images/" . $imagen : null;
        
        echo "<tr>";
        echo "<td>#{$libro['id']}</td>";
        echo "<td>" . htmlspecialchars($libro['titulo']) . "</td>";
        echo "<td>" . ($imagen ?: '<em>NULL</em>') . "</td>";
        
        if (!$imagen) {
            echo "<td class='default'>default-book.jpg</td>";
            echo "<td class='default'>🔄 Usa imagen por defecto</td>";
        } else {
            echo "<td>" . htmlspecialchars($imagen) . "</td>";
            if (file_exists($imagen_path)) {
                echo "<td class='exists'>✅ Existe</td>";
            } else {
                echo "<td class='missing'>❌ No existe → default-book.jpg</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Contar estadísticas
    $sin_imagen = 0;
    $con_imagen_valida = 0;
    $con_imagen_faltante = 0;
    
    foreach ($libros as $libro) {
        if (!$libro['imagen_portada']) {
            $sin_imagen++;
        } else {
            $imagen_path = "images/" . $libro['imagen_portada'];
            if (file_exists($imagen_path)) {
                $con_imagen_valida++;
            } else {
                $con_imagen_faltante++;
            }
        }
    }
    
    echo "<div class='table-container'>";
    echo "<h2>📊 Resumen</h2>";
    echo "<table>";
    echo "<tr><th>Categoría</th><th>Cantidad</th><th>Porcentaje</th></tr>";
    echo "<tr><td class='default'>Sin imagen en BD</td><td>$sin_imagen</td><td>" . round($sin_imagen/count($libros)*100, 1) . "%</td></tr>";
    echo "<tr><td class='exists'>Con imagen válida</td><td>$con_imagen_valida</td><td>" . round($con_imagen_valida/count($libros)*100, 1) . "%</td></tr>";
    echo "<tr><td class='missing'>Con imagen faltante</td><td>$con_imagen_faltante</td><td>" . round($con_imagen_faltante/count($libros)*100, 1) . "%</td></tr>";
    echo "</table>";
    echo "</div>";
    
    $problema_principal = ($sin_imagen + $con_imagen_faltante) / count($libros);
    
    if ($problema_principal > 0.7) {
        echo "<div style='background: #ffebee; padding: 15px; margin: 20px 0; border-left: 5px solid #f44336;'>";
        echo "<h3>🚨 PROBLEMA IDENTIFICADO</h3>";
        echo "<p><strong>El " . round($problema_principal * 100, 1) . "% de los libros no tienen imagen propia.</strong></p>";
        echo "<p>Esto explica por qué ves las mismas imágenes repetidas - todos están usando <code>default-book.jpg</code></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>