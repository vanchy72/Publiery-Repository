<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    // Limpiar tabla existente
    $pdo->exec("DELETE FROM testimonios");

    // Datos de prueba realistas
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

    $creados = 0;
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
            $index * 2     // días atrás para variar las fechas
        ]);

        $creados++;
    }

    echo json_encode([
        'success' => true,
        'message' => $creados,
        'detalle' => $creados . ' testimonios de prueba insertados correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error insertando datos: ' . $e->getMessage()
    ]);
}
?>
