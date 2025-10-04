<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    // Limpiar tabla completamente
    $pdo->exec("DELETE FROM testimonios");
    $pdo->exec("ALTER TABLE testimonios AUTO_INCREMENT = 1");

    // Datos de prueba garantizados
    $testimonios = [
        [
            'nombre' => 'María González López',
            'email' => 'maria.gonzalez@email.com',
            'calificacion' => 5,
            'testimonio' => '¡Excelente plataforma! El proceso de publicación de libros es increíblemente sencillo. El equipo de soporte es muy atento y profesional. ¡Recomiendo ampliamente!',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ],
        [
            'nombre' => 'Carlos Rodríguez Martínez',
            'email' => 'carlos.rodriguez@email.com',
            'calificacion' => 4,
            'testimonio' => 'Muy buena experiencia general. La plataforma es intuitiva y fácil de usar. Solo sugeriría mejorar algunos aspectos de la interfaz de usuario.',
            'estado' => 'aprobado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Ana Torres García',
            'email' => 'ana.torres@email.com',
            'calificacion' => 5,
            'testimonio' => 'Estoy encantada con esta plataforma. He podido publicar mi libro de poesía de manera profesional y llegar a muchos lectores. ¡Gracias por hacer esto posible!',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ],
        [
            'nombre' => 'David Sánchez Morales',
            'email' => 'david.sanchez@email.com',
            'calificacion' => 3,
            'testimonio' => 'La plataforma funciona correctamente, pero necesita algunas mejoras en la velocidad de carga y algunas funcionalidades adicionales.',
            'estado' => 'pendiente',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Laura Jiménez Ruiz',
            'email' => 'laura.jimenez@email.com',
            'calificacion' => 4,
            'testimonio' => 'Buena plataforma con mucho potencial. El soporte al cliente es excelente y siempre están dispuestos a ayudar.',
            'estado' => 'aprobado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Roberto Díaz Fernández',
            'email' => 'roberto.diaz@email.com',
            'calificacion' => 5,
            'testimonio' => '¡Increíble! Esta plataforma ha revolucionado mi forma de publicar libros. La facilidad de uso y la calidad del resultado final son excepcionales.',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ],
        [
            'nombre' => 'Patricia López Vega',
            'email' => 'patricia.lopez@email.com',
            'calificacion' => 2,
            'testimonio' => 'Necesita muchas mejoras. La interfaz no es muy intuitiva y tuve algunos problemas técnicos durante el proceso.',
            'estado' => 'rechazado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Miguel Ángel Castro',
            'email' => 'miguel.castro@email.com',
            'calificacion' => 4,
            'testimonio' => 'Buena experiencia general. La calidad del servicio es buena y el equipo es profesional. Cumple con lo prometido.',
            'estado' => 'aprobado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Carmen Rodríguez Soto',
            'email' => 'carmen.rodriguez@email.com',
            'calificacion' => 3,
            'testimonio' => 'Es funcional pero le faltan algunas características importantes. El precio es razonable pero necesita mejoras.',
            'estado' => 'pendiente',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Fernando Gutiérrez',
            'email' => 'fernando.gutierrez@email.com',
            'calificacion' => 5,
            'testimonio' => '¡Fantástica experiencia! He publicado varios libros aquí y cada vez el proceso es mejor. El equipo mejora constantemente.',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ]
    ];

    $insertados = 0;
    foreach ($testimonios as $index => $testimonio) {
        $stmt = $pdo->prepare('
            INSERT INTO testimonios (
                nombre, email, calificacion, testimonio, estado, es_destacado, fecha_envio
            ) VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
        ');

        $stmt->execute([
            $testimonio['nombre'],
            $testimonio['email'],
            $testimonio['calificacion'],
            $testimonio['testimonio'],
            $testimonio['estado'],
            $testimonio['es_destacado'],
            $index * 3 // Días atrás para variar las fechas
        ]);

        $insertados++;
    }

    // Verificar que se insertaron correctamente
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM testimonios');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalInsertados = $result['total'];

    // Calcular estadísticas
    $stmt = $pdo->prepare('SELECT estado, COUNT(*) as cantidad FROM testimonios GROUP BY estado');
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [];
    foreach ($estadisticas as $stat) {
        $stats[$stat['estado']] = (int)$stat['cantidad'];
    }

    // Calificación promedio
    $stmt = $pdo->query('SELECT AVG(calificacion) as promedio FROM testimonios');
    $promedio = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => $insertados . ' testimonios insertados correctamente',
        'details' => "Total en BD: {$totalInsertados} | Aprobados: " . ($stats['aprobado'] ?? 0) . " | Pendientes: " . ($stats['pendiente'] ?? 0),
        'total_insertados' => $insertados,
        'total_verificado' => $totalInsertados,
        'estadisticas' => $stats,
        'promedio_calificacion' => round($promedio['promedio'], 1)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error insertando datos: ' . $e->getMessage()
    ]);
}
?>
