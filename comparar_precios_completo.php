<?php
require_once 'config/database.php';

echo "<h1>🎯 COMPARACIÓN EXHAUSTIVA DE PRECIOS</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.admin { background-color: #f8f9fa; border-left: 5px solid #007bff; }
.writer { background-color: #f1f8ff; border-left: 5px solid #28a745; }
.affiliate { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
.match { background-color: #d4edda; }
.mismatch { background-color: #f8d7da; font-weight: bold; }
.precio-bd { font-weight: bold; color: #333; }
.precio-admin { color: #007bff; }
.precio-writer { color: #28a745; }
.precio-affiliate { color: #ffc107; }
</style>";

try {
    $pdo = getDBConnection();
    
    // Obtener datos de la base de datos
    $query = "SELECT id, titulo, precio, precio_afiliado, estado FROM libros WHERE estado = 'publicado' ORDER BY id";
    $stmt = $pdo->query($query);
    $libros_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='panel admin'>";
    echo "<h2>📊 COMPARACIÓN COMPLETA: BASE DE DATOS vs APIs</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th rowspan='2'>Libro</th>";
    echo "<th colspan='2'>Base de Datos</th>";
    echo "<th colspan='2'>API Admin</th>";
    echo "<th>API Escritor</th>";
    echo "<th>API Tienda</th>";
    echo "<th>Estado</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>Precio Regular</th>";
    echo "<th>Precio Afiliado</th>";
    echo "<th>Precio Regular</th>";
    echo "<th>Precio Afiliado</th>";
    echo "<th>Precio Regular</th>";
    echo "<th>Precio Afiliado</th>";
    echo "<th>Verificación</th>";
    echo "</tr>";
    
    // Para cada libro, obtener datos de todas las APIs
    foreach ($libros_bd as $libro_bd) {
        echo "<tr>";
        echo "<td><strong>#{$libro_bd['id']}</strong><br>" . htmlspecialchars(substr($libro_bd['titulo'], 0, 30)) . "...</td>";
        
        // Datos de BD
        $precio_bd_regular = (float)$libro_bd['precio'];
        $precio_bd_afiliado = (float)$libro_bd['precio_afiliado'];
        echo "<td class='precio-bd'>$" . number_format($precio_bd_regular, 0) . "</td>";
        echo "<td class='precio-bd'>$" . number_format($precio_bd_afiliado, 0) . "</td>";
        
        // API Admin
        $api_admin_response = @file_get_contents("http://localhost/publiery/api/libros/admin_panel_listar.php");
        $admin_data = json_decode($api_admin_response, true);
        $libro_admin = null;
        if ($admin_data && $admin_data['success']) {
            foreach ($admin_data['libros'] as $la) {
                if ($la['id'] == $libro_bd['id']) {
                    $libro_admin = $la;
                    break;
                }
            }
        }
        
        if ($libro_admin) {
            $precio_admin_regular = (float)($libro_admin['precio'] ?? 0);
            $precio_admin_afiliado = (float)($libro_admin['precio_afiliado'] ?? 0);
            
            $match_admin_regular = ($precio_admin_regular == $precio_bd_regular);
            $match_admin_afiliado = ($precio_admin_afiliado == $precio_bd_afiliado);
            
            echo "<td class='precio-admin " . ($match_admin_regular ? 'match' : 'mismatch') . "'>$" . number_format($precio_admin_regular, 0) . "</td>";
            echo "<td class='precio-admin " . ($match_admin_afiliado ? 'match' : 'mismatch') . "'>$" . number_format($precio_admin_afiliado, 0) . "</td>";
        } else {
            echo "<td class='mismatch'>No encontrado</td>";
            echo "<td class='mismatch'>No encontrado</td>";
        }
        
        // API Escritor
        $api_writer_response = @file_get_contents("http://localhost/publiery/api/escritores/dashboard.php");
        $writer_data = json_decode($api_writer_response, true);
        $libro_writer = null;
        if ($writer_data && $writer_data['success']) {
            foreach ($writer_data['libros'] ?? [] as $lw) {
                if ($lw['id'] == $libro_bd['id']) {
                    $libro_writer = $lw;
                    break;
                }
            }
        }
        
        if ($libro_writer) {
            $precio_writer = (float)($libro_writer['precio'] ?? 0);
            $match_writer = ($precio_writer == $precio_bd_regular);
            echo "<td class='precio-writer " . ($match_writer ? 'match' : 'mismatch') . "'>$" . number_format($precio_writer, 0) . "</td>";
        } else {
            echo "<td class='mismatch'>No encontrado</td>";
        }
        
        // API Tienda
        $api_store_response = @file_get_contents("http://localhost/publiery/api/libros/disponibles.php");
        $store_data = json_decode($api_store_response, true);
        $libro_store = null;
        if ($store_data && $store_data['success']) {
            foreach ($store_data['libros'] as $ls) {
                if ($ls['id'] == $libro_bd['id']) {
                    $libro_store = $ls;
                    break;
                }
            }
        }
        
        if ($libro_store) {
            $precio_store_afiliado = (float)($libro_store['precio_afiliado'] ?? 0);
            $match_store = ($precio_store_afiliado == $precio_bd_afiliado);
            echo "<td class='precio-affiliate " . ($match_store ? 'match' : 'mismatch') . "'>$" . number_format($precio_store_afiliado, 0) . "</td>";
        } else {
            echo "<td class='mismatch'>No encontrado</td>";
        }
        
        // Estado general
        $todo_correcto = true;
        if ($libro_admin) {
            $todo_correcto = $todo_correcto && ($precio_admin_regular == $precio_bd_regular) && ($precio_admin_afiliado == $precio_bd_afiliado);
        }
        if ($libro_writer) {
            $todo_correcto = $todo_correcto && ($precio_writer == $precio_bd_regular);
        }
        if ($libro_store) {
            $todo_correcto = $todo_correcto && ($precio_store_afiliado == $precio_bd_afiliado);
        }
        
        if ($todo_correcto) {
            echo "<td class='match'>✅ CORRECTO</td>";
        } else {
            echo "<td class='mismatch'>❌ INCORRECTO</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // Resumen de URLs para verificación manual
    echo "<div class='panel warning'>";
    echo "<h2>🔗 URLS PARA VERIFICACIÓN MANUAL</h2>";
    echo "<ul>";
    echo "<li><a href='http://localhost/publiery/api/libros/admin_panel_listar.php' target='_blank'>API Admin Panel</a></li>";
    echo "<li><a href='http://localhost/publiery/api/escritores/dashboard.php' target='_blank'>API Dashboard Escritor</a></li>";
    echo "<li><a href='http://localhost/publiery/api/libros/disponibles.php' target='_blank'>API Tienda Afiliados</a></li>";
    echo "</ul>";
    echo "</div>";
    
    // Instrucciones para el usuario
    echo "<div class='panel admin'>";
    echo "<h2>🎯 INSTRUCCIONES PARA EL USUARIO</h2>";
    echo "<p><strong>Por favor, verifica lo siguiente:</strong></p>";
    echo "<ol>";
    echo "<li>🔍 <strong>Identifica qué libro específico tiene precios incorrectos</strong></li>";
    echo "<li>📱 <strong>Ve a la tienda de afiliados</strong> y dime qué precio ves</li>";
    echo "<li>📊 <strong>Compáralo con esta tabla</strong> para ver si coincide con 'Precio Afiliado' de BD</li>";
    echo "<li>🧹 <strong>Si no coincide, prueba refrescar</strong> con Ctrl+F5 (hard refresh)</li>";
    echo "<li>💬 <strong>Dime el resultado</strong> exacto que ves vs lo que debería ser</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='panel error'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><em>Comparación completada: " . date('Y-m-d H:i:s') . "</em></p>";
?>