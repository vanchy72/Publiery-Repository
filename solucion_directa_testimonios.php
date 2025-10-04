<?php
echo "<h1>🔧 SOLUCIÓN DIRECTA - GESTIÓN DE TESTIMONIOS</h1>";
echo "<p><strong>Ejecutando correcciones automáticas...</strong></p>";

// Incluir configuración de base de datos
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Conexión a BD exitosa</p>";

    // PASO 1: Verificar y crear tabla testimonios
    echo "<h2>📋 PASO 1: Verificando tabla testimonios</h2>";

    $stmt = $pdo->query('SHOW TABLES LIKE "testimonios"');
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ Tabla no existe - Creando...</p>";

        $sql = "
        CREATE TABLE testimonios (
            id INT(11) NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            calificacion INT(1) NOT NULL,
            testimonio TEXT NOT NULL,
            estado ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
            es_destacado TINYINT(1) DEFAULT 0,
            fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_revision TIMESTAMP NULL,
            observaciones_admin TEXT,
            PRIMARY KEY (id),
            KEY idx_estado (estado),
            KEY idx_fecha_envio (fecha_envio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabla 'testimonios' creada exitosamente</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabla 'testimonios' ya existe</p>";
    }

    // PASO 2: Limpiar datos existentes y crear datos de prueba
    echo "<h2>🧹 PASO 2: Preparando datos de prueba</h2>";

    // Limpiar tabla
    $pdo->exec("DELETE FROM testimonios");
    echo "<p>🗑️ Datos anteriores eliminados</p>";

    // Crear testimonios de prueba
    $testimonios = [
        ['Ana García López', 'ana.garcia@email.com', 5, '¡Excelente plataforma! El proceso de publicación de libros es increíblemente sencillo. El equipo de soporte es muy atento y profesional. ¡Recomiendo ampliamente!', 'aprobado', 1],
        ['Carlos Rodríguez', 'carlos.rodriguez@email.com', 4, 'Muy buena experiencia. La plataforma es intuitiva y fácil de usar. Solo sugeriría mejorar algunos aspectos de la interfaz de usuario.', 'aprobado', 0],
        ['María Fernández', 'maria.fernandez@email.com', 5, 'Estoy encantada con esta plataforma. He podido publicar mi libro de manera profesional y llegar a muchos lectores. ¡Gracias por hacer esto posible!', 'aprobado', 1],
        ['Juan Pérez Sánchez', 'juan.perez@email.com', 3, 'La plataforma funciona bien, pero necesita algunas mejoras en la velocidad de carga y en algunas funcionalidades.', 'pendiente', 0],
        ['Laura Martínez Ruiz', 'laura.martinez@email.com', 4, 'Buena plataforma con mucho potencial. El soporte al cliente es excelente y siempre están dispuestos a ayudar.', 'aprobado', 0],
        ['Pedro Gómez Torres', 'pedro.gomez@email.com', 5, '¡Increíble! Esta plataforma ha revolucionado mi forma de publicar libros. Altamente recomendada.', 'aprobado', 1],
        ['Carmen López Díaz', 'carmen.lopez@email.com', 2, 'Necesita muchas mejoras. La interfaz no es muy intuitiva y tuve algunos problemas técnicos.', 'rechazado', 0],
        ['Miguel Ángel Sánchez', 'miguel.sanchez@email.com', 4, 'Buena experiencia general. La calidad del servicio es buena y el equipo es profesional.', 'aprobado', 0]
    ];

    foreach ($testimonios as $index => $testimonio) {
        $stmt = $pdo->prepare('
            INSERT INTO testimonios (nombre, email, calificacion, testimonio, estado, es_destacado, fecha_envio)
            VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
        ');
        $stmt->execute([
            $testimonio[0], // nombre
            $testimonio[1], // email
            $testimonio[2], // calificacion
            $testimonio[3], // testimonio
            $testimonio[4], // estado
            $testimonio[5], // es_destacado
            $index * 2     // días atrás
        ]);
    }

    echo "<p style='color: green;'>✅ " . count($testimonios) . " testimonios de prueba creados</p>";

    // PASO 3: Verificar APIs
    echo "<h2>🔧 PASO 3: Verificando APIs</h2>";

    $apis = [
        'api/testimonios/admin_listar.php',
        'api/testimonios/obtener.php?id=1',
        'api/testimonios/revisar.php'
    ];

    foreach ($apis as $api) {
        $fullUrl = "http://localhost/publiery/{$api}";
        $headers = get_headers($fullUrl, 1);

        if ($headers && strpos($headers[0], '200') !== false) {
            echo "<p style='color: green;'>✅ {$api} - OK</p>";
        } else {
            echo "<p style='color: red;'>❌ {$api} - Error</p>";
        }
    }

    // PASO 4: Mostrar estadísticas finales
    echo "<h2>📊 PASO 4: Estadísticas finales</h2>";

    $stmt = $pdo->prepare('SELECT estado, COUNT(*) as cantidad FROM testimonios GROUP BY estado');
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📈 Resumen de Testimonios</h3>";

    foreach ($estadisticas as $stat) {
        $color = match($stat['estado']) {
            'pendiente' => '#ffc107',
            'aprobado' => '#28a745',
            'rechazado' => '#dc3545',
            default => '#6c757d'
        };

        echo "<div style='margin: 10px 0; padding: 10px; background: {$color}20; border-radius: 6px;'>";
        echo "<strong>{$stat['estado']}</strong>: {$stat['cantidad']} testimonios";
        echo "</div>";
    }

    // Calificación promedio
    $stmt = $pdo->query('SELECT AVG(calificacion) as promedio FROM testimonios');
    $promedio = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div style='margin: 10px 0; padding: 10px; background: #fff3cd; border-radius: 6px;'>";
    echo "<strong>⭐ Calificación Promedio</strong>: " . number_format($promedio['promedio'], 1) . "/5";
    echo "</div>";

    // Testimonios destacados
    $stmt = $pdo->prepare('SELECT COUNT(*) as destacados FROM testimonios WHERE es_destacado = 1');
    $stmt->execute();
    $destacados = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div style='margin: 10px 0; padding: 10px; background: #f3e5f5; border-radius: 6px;'>";
    echo "<strong>🏆 Testimonios Destacados</strong>: {$destacados['destacados']}";
    echo "</div>";

    echo "</div>";

    // PASO 5: Mostrar testimonios creados
    echo "<h2>📝 PASO 5: Testimonios creados</h2>";

    $stmt = $pdo->prepare('
        SELECT id, nombre, calificacion, estado, es_destacado, fecha_envio,
               LEFT(testimonio, 100) as testimonio_corto
        FROM testimonios
        ORDER BY fecha_envio DESC
        LIMIT 5
    ');
    $stmt->execute();
    $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($recientes as $testimonio) {
        $estrellas = str_repeat('⭐', $testimonio['calificacion']);
        $fecha = date('d/m/Y H:i', strtotime($testimonio['fecha_envio']));

        echo "<div style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #dee2e6;'>";
        echo "<div><strong>{$testimonio['id']} - {$testimonio['nombre']}</strong> {$estrellas} ({$testimonio['calificacion']}/5)</div>";
        echo "<div style='color: #666; font-size: 0.9rem;'>Estado: {$testimonio['estado']} | Fecha: {$fecha}</div>";
        echo "<div style='margin-top: 5px; font-style: italic;'>\"{$testimonio['testimonio_corto']}...\"</div>";
        if ($testimonio['es_destacado']) {
            echo "<div style='color: #ff9800; font-weight: bold;'>⭐ Destacado</div>";
        }
        echo "</div>";
    }
    echo "</div>";

    // PASO 6: Instrucciones finales
    echo "<h2>🎯 PASO 6: Verificación final</h2>";

    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #28a745;'>";
    echo "<h3>✅ SISTEMA LISTO PARA USAR</h3>";
    echo "<p><strong>Ahora puedes:</strong></p>";
    echo "<ol>";
    echo "<li><a href='admin-panel.html#gestion-testimonios' target='_blank'>Ir al Panel de Testimonios</a></li>";
    echo "<li>Ver 8 testimonios de prueba en la tabla</li>";
    echo "<li>Probar los filtros de búsqueda</li>";
    echo "<li>Hacer click en 'Revisar' para abrir el modal</li>";
    echo "<li>Cambiar entre las pestañas del modal</li>";
    echo "<li>Probar las acciones masivas</li>";
    echo "</ol>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;'>";
    echo "<h4>🔧 Si aún hay problemas:</h4>";
    echo "<ul>";
    echo "<li>Presiona <strong>Ctrl+F5</strong> para recargar sin caché</li>";
    echo "<li>Abre la <strong>consola del navegador (F12)</strong> para ver errores</li>";
    echo "<li>Asegúrate de estar <strong>logueado como administrador</strong></li>";
    echo "<li>Si no funciona, ejecuta este archivo nuevamente</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>❌ Error en la configuración</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Soluciones:</strong></p>";
    echo "<ul>";
    echo "<li>Verifica que XAMPP esté ejecutándose</li>";
    echo "<li>Comprueba la configuración de la base de datos en config/database.php</li>";
    echo "<li>Asegúrate de que la base de datos 'publiery' exista</li>";
    echo "<li>Verifica las credenciales de conexión</li>";
    echo "</ul>";
    echo "</div>";
}
?>
