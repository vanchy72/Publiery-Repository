<?php
require_once 'config/database.php';

echo "<h1>🔍 DIAGNÓSTICO ESPECÍFICO: PRECIOS EN TIENDA DE AFILIADOS</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.info { background-color: #d1ecf1; border-left: 5px solid #17a2b8; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.precio-regular { font-weight: bold; color: #dc3545; }
.precio-afiliado { font-weight: bold; color: #28a745; }
.diferencia { background-color: #ffeb3b; padding: 3px 6px; border-radius: 3px; }
</style>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar datos directos de la base de datos
    echo "<div class='panel info'>";
    echo "<h2>📊 1. DATOS DIRECTOS DE BASE DE DATOS</h2>";
    
    $query = "SELECT id, titulo, precio, precio_afiliado, estado FROM libros WHERE estado = 'publicado' ORDER BY id LIMIT 10";
    $stmt = $pdo->query($query);
    $libros_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Precio BD</th><th>Precio Afiliado BD</th><th>Diferencia</th></tr>";
    
    foreach ($libros_bd as $libro) {
        $precio_regular = (float)$libro['precio'];
        $precio_afiliado = (float)$libro['precio_afiliado'];
        $diferencia = $precio_regular - $precio_afiliado;
        $porcentaje = $precio_regular > 0 ? round(($diferencia / $precio_regular) * 100, 1) : 0;
        
        echo "<tr>";
        echo "<td>#{$libro['id']}</td>";
        echo "<td>" . htmlspecialchars($libro['titulo']) . "</td>";
        echo "<td class='precio-regular'>$" . number_format($precio_regular, 0) . "</td>";
        echo "<td class='precio-afiliado'>$" . number_format($precio_afiliado, 0) . "</td>";
        echo "<td class='diferencia'>-$" . number_format($diferencia, 0) . " ({$porcentaje}%)</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 2. Verificar API de tienda de afiliados
    echo "<div class='panel warning'>";
    echo "<h2>🛒 2. API TIENDA AFILIADOS (disponibles.php)</h2>";
    
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
            echo "<p><strong>✅ API respondiendo correctamente</strong></p>";
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Título</th><th>Precio API</th><th>Precio Afiliado API</th><th>¿Coincide con BD?</th></tr>";
            
            foreach ($api_data['libros'] as $api_libro) {
                // Buscar el libro correspondiente en BD
                $libro_bd = null;
                foreach ($libros_bd as $bd_libro) {
                    if ($bd_libro['id'] == $api_libro['id']) {
                        $libro_bd = $bd_libro;
                        break;
                    }
                }
                
                $precio_api_regular = (float)($api_libro['precio'] ?? 0);
                $precio_api_afiliado = (float)($api_libro['precio_afiliado'] ?? 0);
                
                $coincide_regular = $libro_bd ? ((float)$libro_bd['precio'] == $precio_api_regular) : false;
                $coincide_afiliado = $libro_bd ? ((float)$libro_bd['precio_afiliado'] == $precio_api_afiliado) : false;
                
                echo "<tr>";
                echo "<td>#{$api_libro['id']}</td>";
                echo "<td>" . htmlspecialchars($api_libro['titulo']) . "</td>";
                echo "<td class='precio-regular'>$" . number_format($precio_api_regular, 0) . "</td>";
                echo "<td class='precio-afiliado'>$" . number_format($precio_api_afiliado, 0) . "</td>";
                
                if ($coincide_regular && $coincide_afiliado) {
                    echo "<td style='color: green;'>✅ SÍ</td>";
                } else {
                    echo "<td style='color: red;'>❌ NO";
                    if (!$coincide_regular) echo " (precio regular)";
                    if (!$coincide_afiliado) echo " (precio afiliado)";
                    echo "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='panel error'><strong>❌ Error en API:</strong> " . ($api_data['mensaje'] ?? 'Respuesta inválida') . "</div>";
        }
    } else {
        echo "<div class='panel error'><strong>❌ No se pudo conectar a la API de tienda</strong></div>";
    }
    echo "</div>";
    
    // 3. Verificar el JavaScript que muestra los precios
    echo "<div class='panel info'>";
    echo "<h2>💻 3. ANÁLISIS DE CÓDIGO JAVASCRIPT</h2>";
    
    echo "<h3>🔍 Buscar dónde se muestran los precios en el frontend:</h3>";
    
    // Leer el archivo JavaScript
    $js_file = 'js/dashboard-afiliado-unificado.js';
    if (file_exists($js_file)) {
        $js_content = file_get_contents($js_file);
        
        // Buscar líneas relacionadas con precios
        $lines = explode("\n", $js_content);
        $precio_lines = [];
        
        foreach ($lines as $line_num => $line) {
            if (stripos($line, 'precio') !== false && 
                (stripos($line, 'afiliado') !== false || stripos($line, 'precio_afiliado') !== false)) {
                $precio_lines[] = [
                    'line' => $line_num + 1,
                    'content' => trim($line)
                ];
            }
        }
        
        if (!empty($precio_lines)) {
            echo "<table>";
            echo "<tr><th>Línea</th><th>Código</th></tr>";
            foreach ($precio_lines as $precio_line) {
                echo "<tr>";
                echo "<td>{$precio_line['line']}</td>";
                echo "<td><code>" . htmlspecialchars($precio_line['content']) . "</code></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No se encontraron líneas específicas de precio_afiliado en el JavaScript</p>";
        }
    } else {
        echo "<p>❌ No se encontró el archivo JavaScript: $js_file</p>";
    }
    echo "</div>";
    
    // 4. Recomendaciones específicas
    echo "<div class='panel warning'>";
    echo "<h2>🎯 4. DIAGNÓSTICO Y RECOMENDACIONES</h2>";
    
    echo "<h3>🔍 Posibles problemas identificados:</h3>";
    echo "<ul>";
    echo "<li><strong>Cache del navegador:</strong> Los cambios pueden no reflejarse por cache</li>";
    echo "<li><strong>JavaScript cache:</strong> El archivo JS puede estar en cache</li>";
    echo "<li><strong>Sesión de usuario:</strong> Puede que necesites reloguearte</li>";
    echo "<li><strong>API cache:</strong> Algunos servidores cachean respuestas de API</li>";
    echo "</ul>";
    
    echo "<h3>✅ Pasos para verificar:</h3>";
    echo "<ol>";
    echo "<li>Abrir herramientas de desarrollador (F12)</li>";
    echo "<li>Ir a Network tab y marcar 'Disable cache'</li>";
    echo "<li>Recargar la página</li>";
    echo "<li>Verificar qué datos llegan desde la API</li>";
    echo "<li>Verificar qué datos se muestran en pantalla</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='panel error'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<p style='text-align: center; color: #666;'><em>Diagnóstico completado: " . date('Y-m-d H:i:s') . "</em></p>";
?>