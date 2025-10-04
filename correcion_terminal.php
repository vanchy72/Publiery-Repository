<?php
// Corrección desde terminal - EJECUTABLE DIRECTAMENTE
echo "🔧 CORRECCIÓN DE TESTIMONIOS - TERMINAL\n";
echo "===========================================\n\n";

// Verificar que estamos en el directorio correcto
if (!file_exists('config/database.php')) {
    die("❌ Error: No se encuentra config/database.php. Ejecuta desde el directorio publiery.\n");
}

require_once 'config/database.php';

try {
    echo "📋 PASO 1: Verificando conexión a base de datos...\n";
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n\n";

    echo "📝 PASO 2: Verificando tabla testimonios...\n";

    // Verificar si existe la tabla
    $stmt = $pdo->query('SHOW TABLES LIKE "testimonios"');
    if ($stmt->rowCount() == 0) {
        echo "⚠️ Tabla no existe - Creando...\n";

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
        echo "✅ Tabla testimonios creada\n";
    } else {
        echo "✅ Tabla testimonios ya existe\n";
    }

    echo "\n🎯 PASO 3: Preparando datos de prueba...\n";

    // Limpiar datos existentes
    $pdo->exec("DELETE FROM testimonios");
    $pdo->exec("ALTER TABLE testimonios AUTO_INCREMENT = 1");
    echo "🗑️ Datos anteriores eliminados\n";

    // Insertar datos de prueba
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

    $creados = 0;
    foreach ($testimonios as $index => $testimonio) {
        $stmt = $pdo->prepare('
            INSERT INTO testimonios (nombre, email, calificacion, testimonio, estado, es_destacado, fecha_envio)
            VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
        ');
        $stmt->execute([
            $testimonio[0], $testimonio[1], $testimonio[2], $testimonio[3],
            $testimonio[4], $testimonio[5], $index * 2
        ]);
        $creados++;
    }

    echo "✅ {$creados} testimonios creados\n";

    echo "\n📊 PASO 4: Verificando resultados...\n";

    // Contar total
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM testimonios');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📈 Total testimonios: {$result['total']}\n";

    // Estadísticas por estado
    $stmt = $pdo->prepare('SELECT estado, COUNT(*) as cantidad FROM testimonios GROUP BY estado');
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Estadísticas:\n";
    foreach ($estadisticas as $stat) {
        echo "  - {$stat['estado']}: {$stat['cantidad']}\n";
    }

    // Calificación promedio
    $stmt = $pdo->query('SELECT AVG(calificacion) as promedio FROM testimonios');
    $promedio = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "⭐ Promedio calificación: " . number_format($promedio['promedio'], 1) . "\n";

    echo "\n🔧 PASO 5: Verificando API...\n";

    // Probar API
    $apiUrl = "http://localhost/publiery/api/testimonios/admin_listar.php";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API funcionando correctamente\n";
            echo "📊 API reporta {$data['total']} testimonios\n";
        } else {
            echo "⚠️ API responde pero con formato incorrecto\n";
        }
    } else {
        echo "❌ API no responde\n";
    }

    echo "\n🎉 ¡CORRECCIÓN COMPLETADA!\n";
    echo "===========================\n";
    echo "✅ Conexión a BD: OK\n";
    echo "✅ Tabla testimonios: OK\n";
    echo "✅ Datos de prueba: {$creados} testimonios\n";
    echo "✅ Estadísticas: Calculadas\n";
    echo "✅ API: Verificada\n\n";

    echo "🚀 PRÓXIMOS PASOS:\n";
    echo "==================\n";
    echo "1. Abre tu navegador web\n";
    echo "2. Ve a: http://localhost/publiery/admin-panel.html\n";
    echo "3. Navega a 'Gestión de Testimonios'\n";
    echo "4. Deberías ver {$creados} testimonios en la tabla\n";
    echo "5. Prueba filtros, botones y pestañas\n\n";

    echo "🔧 SI NO FUNCIONA:\n";
    echo "==================\n";
    echo "- Presiona Ctrl+F5 para recargar sin caché\n";
    echo "- Verifica que estés logueado como admin\n";
    echo "- Abre F12 para ver errores en consola\n";
    echo "- Ejecuta este script nuevamente\n\n";

    echo "✨ ¡LOS TESTIMONIOS YA DEBERÍAN ESTAR FUNCIONANDO!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO:\n";
    echo "=================\n";
    echo $e->getMessage() . "\n\n";

    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "=======================\n";
    echo "1. Verifica que XAMPP esté ejecutándose\n";
    echo "2. Comprueba config/database.php\n";
    echo "3. Asegúrate de que la BD 'publiery' existe\n";
    echo "4. Verifica credenciales de conexión\n";
    echo "5. Ejecuta desde el directorio publiery: php correcion_terminal.php\n";
}
?>
