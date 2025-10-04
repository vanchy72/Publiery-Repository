<?php
// EJECUCIÓN DIRECTA DESDE NAVEGADOR - SIN JAVASCRIPT
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>🚀 CORRECCIÓN DIRECTA DE TESTIMONIOS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f8ff; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .completed { border-left-color: #28a745; background: #d4edda; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 CORRECCIÓN DIRECTA DE TESTIMONIOS</h1>
        <p class='info'>Ejecutando corrección automáticamente...</p>";

// EJECUCIÓN DIRECTA
try {
    echo "<div class='step'>🔍 PASO 1: Verificando conexión a base de datos...</div>";
    $pdo = getDBConnection();
    echo "<div class='step completed'>✅ Conexión exitosa</div>";

    echo "<div class='step'>📝 PASO 2: Verificando tabla testimonios...</div>";

    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS testimonios (
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
    ");
    echo "<div class='step completed'>✅ Tabla testimonios creada/verificada</div>";

    echo "<div class='step'>🗑️ PASO 3: Limpiando datos anteriores...</div>";
    $pdo->exec("DELETE FROM testimonios");
    $pdo->exec("ALTER TABLE testimonios AUTO_INCREMENT = 1");
    echo "<div class='step completed'>✅ Datos anteriores eliminados</div>";

    echo "<div class='step'>🎯 PASO 4: Insertando datos de prueba...</div>";

    $testimonios = [
        ['María García', 'maria@example.com', 5, 'Excelente plataforma. El proceso de publicación es muy sencillo y el soporte es excepcional.', 'aprobado', 1],
        ['Carlos López', 'carlos@example.com', 4, 'Muy buena experiencia general. Podrían mejorar algunos detalles de la interfaz.', 'aprobado', 0],
        ['Ana Torres', 'ana@example.com', 5, 'Encantada con la plataforma. Ha revolucionado mi forma de publicar libros.', 'aprobado', 1],
        ['Juan Pérez', 'juan@example.com', 3, 'Funciona bien pero necesita algunas mejoras en la usabilidad.', 'pendiente', 0],
        ['Laura Sánchez', 'laura@example.com', 4, 'Buena plataforma con mucho potencial. El equipo de soporte es muy atento.', 'aprobado', 0],
        ['Pedro Gómez', 'pedro@example.com', 5, '¡Increíble! Esta plataforma ha revolucionado mi forma de publicar libros.', 'aprobado', 1],
        ['Carmen Díaz', 'carmen@example.com', 2, 'Necesita mejoras importantes en algunos aspectos.', 'rechazado', 0],
        ['Miguel Ángel', 'miguel@example.com', 4, 'Buena experiencia general. Funciona correctamente.', 'aprobado', 0]
    ];

    $stmt = $pdo->prepare('INSERT INTO testimonios (nombre, email, calificacion, testimonio, estado, es_destacado) VALUES (?, ?, ?, ?, ?, ?)');

    foreach ($testimonios as $t) {
        $stmt->execute($t);
    }

    echo "<div class='step completed'>✅ " . count($testimonios) . " testimonios insertados</div>";

    echo "<div class='step'>📊 PASO 5: Verificando resultados...</div>";
    $result = $pdo->query('SELECT COUNT(*) as total FROM testimonios')->fetch();
    echo "<div class='step completed'>📈 Total testimonios en BD: " . $result['total'] . "</div>";

    // Estadísticas
    $stats = $pdo->query('SELECT estado, COUNT(*) as cantidad FROM testimonios GROUP BY estado')->fetchAll();
    echo "<div class='step completed'>📋 Estadísticas:<br>";
    foreach ($stats as $stat) {
        echo "  - {$stat['estado']}: {$stat['cantidad']}<br>";
    }
    echo "</div>";

    // Promedio
    $avg = $pdo->query('SELECT AVG(calificacion) as promedio FROM testimonios')->fetch();
    echo "<div class='step completed'>⭐ Promedio calificación: " . number_format($avg['promedio'], 1) . "</div>";

    echo "
        <div class='step completed'>
            <h3>🎉 ¡CORRECCIÓN COMPLETADA EXITOSAMENTE!</h3>
            <p><strong>Resultados:</strong></p>
            <ul>
                <li>✅ Conexión a base de datos: OK</li>
                <li>✅ Tabla testimonios: Creada</li>
                <li>✅ Datos de prueba: " . count($testimonios) . " testimonios</li>
                <li>✅ Estadísticas: Calculadas</li>
            </ul>
        </div>

        <div style='text-align: center; margin: 30px 0;'>
            <a href='admin-panel.html#gestion-testimonios' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; display: inline-block;'>
                📊 IR A GESTIÓN DE TESTIMONIOS
            </a>
        </div>

        <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin-top: 20px;'>
            <h4>🔍 Próximos pasos:</h4>
            <ol>
                <li>Haz click en el botón arriba para ir a Gestión de Testimonios</li>
                <li>Deberías ver " . count($testimonios) . " testimonios en la tabla</li>
                <li>Prueba los filtros por estado (aprobado, pendiente, rechazado)</li>
                <li>Haz click en 'Revisar' para ver detalles y pestañas</li>
                <li>Verifica que las pestañas del modal funcionen correctamente</li>
            </ol>
        </div>

        <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h4>⚠️ Si no aparecen los testimonios:</h4>
            <ul>
                <li>Presiona <strong>Ctrl+F5</strong> para recargar sin caché</li>
                <li>Verifica que estés logueado como administrador</li>
                <li>Abre la consola del navegador (F12) y busca errores</li>
                <li>Si hay errores, recarga esta página para ejecutar nuevamente</li>
            </ul>
        </div>
    ";

} catch (Exception $e) {
    echo "
        <div class='step' style='border-left-color: #dc3545; background: #f8d7da;'>
            <h3 class='error'>❌ ERROR CRÍTICO</h3>
            <p><strong>Error:</strong> " . $e->getMessage() . "</p>
            <p><strong>Soluciones:</strong></p>
            <ul>
                <li>Verifica que XAMPP esté ejecutándose</li>
                <li>Comprueba config/database.php</li>
                <li>Asegúrate de que la BD 'publiery' existe</li>
            </ul>
        </div>
    ";
}

echo "
    </div>
</body>
</html>";
?>
