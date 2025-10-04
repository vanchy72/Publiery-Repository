<?php
// CORRECCIÓN INSTANTÁNEA - EJECUTABLE DIRECTAMENTE
echo "🚀 CORRECCIÓN INSTANTÁNEA DE TESTIMONIOS\n";
echo "=========================================\n\n";

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "✅ BD conectada\n";

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
    echo "✅ Tabla creada/verificada\n";

    // Limpiar e insertar datos
    $pdo->exec("DELETE FROM testimonios");
    $pdo->exec("ALTER TABLE testimonios AUTO_INCREMENT = 1");

    $testimonios = [
        ['María García', 'maria@example.com', 5, 'Excelente plataforma', 'aprobado', 1],
        ['Carlos López', 'carlos@example.com', 4, 'Muy buena experiencia', 'aprobado', 0],
        ['Ana Torres', 'ana@example.com', 5, 'Encantada con la plataforma', 'aprobado', 1],
        ['Juan Pérez', 'juan@example.com', 3, 'Funciona bien', 'pendiente', 0],
        ['Laura Sánchez', 'laura@example.com', 4, 'Buena plataforma', 'aprobado', 0]
    ];

    $stmt = $pdo->prepare('INSERT INTO testimonios (nombre, email, calificacion, testimonio, estado, es_destacado) VALUES (?, ?, ?, ?, ?, ?)');

    foreach ($testimonios as $t) {
        $stmt->execute($t);
    }

    echo "✅ " . count($testimonios) . " testimonios insertados\n";

    // Verificar
    $result = $pdo->query('SELECT COUNT(*) as total FROM testimonios')->fetch();
    echo "📊 Total en BD: " . $result['total'] . "\n";

    echo "\n🎉 ¡LISTO! Ahora ve a http://localhost/publiery/admin-panel.html#gestion-testimonios\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
