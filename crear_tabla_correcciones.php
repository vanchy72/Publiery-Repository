<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS correcciones_libros (
        id INT(11) NOT NULL AUTO_INCREMENT,
        libro_id INT(11) NOT NULL,
        archivo_original VARCHAR(255) DEFAULT NULL,
        archivo_correccion VARCHAR(255) NOT NULL,
        comentarios TEXT,
        admin_id INT(11) DEFAULT NULL,
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_libro_id (libro_id),
        KEY idx_fecha_subida (fecha_subida),
        CONSTRAINT fk_correcciones_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
        CONSTRAINT fk_correcciones_admin FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ Tabla 'correcciones_libros' creada exitosamente\n";

    // Crear directorio para correcciones
    $uploadDir = 'uploads/correcciones/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ Directorio 'uploads/correcciones/' creado\n";
    } else {
        echo "ℹ️ Directorio 'uploads/correcciones/' ya existe\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
