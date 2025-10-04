<?php
echo "<h1>Prueba de APIs Corregidos</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .section{margin:20px 0;padding:15px;border:1px solid #ddd;background:#f9f9f9;} pre{background:#f0f0f0;padding:10px;overflow:auto;}</style>";

// Función para probar API
function probarAPI($url, $nombre) {
    echo "<div class='section'>";
    echo "<h2>Probando: $nombre</h2>";
    echo "<p><strong>URL:</strong> $url</p>";
    
    try {
        $response = file_get_contents("http://localhost/publiery/$url");
        $data = json_decode($response, true);
        
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "<div class='success'>✅ API funcionando correctamente</div>";
                if (isset($data['ventas'])) {
                    echo "<p>Ventas encontradas: " . count($data['ventas']) . "</p>";
                }
                if (isset($data['pagos'])) {
                    echo "<p>Pagos encontrados: " . count($data['pagos']) . "</p>";
                }
                echo "<details><summary>Ver respuesta completa</summary><pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></details>";
            } else {
                echo "<div class='error'>❌ Error en API: " . ($data['error'] ?? 'Error desconocido') . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Respuesta inválida del API</div>";
            echo "<pre>$response</pre>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// Probar APIs problemáticos
probarAPI('api/ventas/listar_simple.php', 'API de Ventas');
probarAPI('api/pagos/listar_simple.php', 'API de Pagos/Comisiones');

// Probar con filtros
probarAPI('api/ventas/listar_simple.php?filtro=test', 'API de Ventas con Filtro');
probarAPI('api/pagos/listar_simple.php?filtro=test', 'API de Pagos con Filtro');

echo "<div class='section'>";
echo "<h2>Verificación de Estructura de Base de Datos</h2>";
echo "<p><a href='verificar_estructura_db.php' target='_blank'>Ver estructura completa de la base de datos</a></p>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>Acceso al Panel</h2>";
echo "<p><a href='admin-panel.html' target='_blank'>Abrir Panel de Administración</a></p>";
echo "</div>";
?>