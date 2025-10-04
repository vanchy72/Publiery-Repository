<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();

    // Limpiar testimonios existentes
    $pdo->exec("DELETE FROM testimonios");
    $pdo->exec("ALTER TABLE testimonios AUTO_INCREMENT = 1");

    // Crear testimonios de prueba
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

    echo json_encode([
        'success' => true,
        'message' => count($testimonios) . ' testimonios de prueba creados exitosamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
