<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    // Verificar si la tabla ya existe
    $stmt = $pdo->query('SHOW TABLES LIKE "testimonios"');
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tabla ya existe'
        ]);
        exit;
    }

    // Crear tabla testimonios
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

    echo json_encode([
        'success' => true,
        'message' => 'Tabla testimonios creada exitosamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error creando tabla: ' . $e->getMessage()
    ]);
}
?>
