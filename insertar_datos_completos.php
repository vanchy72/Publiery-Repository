<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    // Limpiar datos existentes
    $pdo->exec("DELETE FROM testimonios");

    // Datos de prueba más completos y realistas
    $testimonios = [
        [
            'nombre' => 'María González Rodríguez',
            'email' => 'maria.gonzalez@email.com',
            'calificacion' => 5,
            'testimonio' => '¡Excelente plataforma! He publicado mi primer libro de manera profesional. El proceso es muy intuitivo y el soporte técnico es excepcional. ¡Altamente recomendado!',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ],
        [
            'nombre' => 'Carlos Martínez López',
            'email' => 'carlos.martinez@email.com',
            'calificacion' => 4,
            'testimonio' => 'Muy buena experiencia general. La plataforma funciona bien y es fácil de usar. Solo sugeriría mejorar algunos aspectos de la interfaz de usuario para hacerla aún más intuitiva.',
            'estado' => 'aprobado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Ana Torres García',
            'email' => 'ana.torres@email.com',
            'calificacion' => 5,
            'testimonio' => 'Estoy encantada con esta plataforma. He podido publicar mi libro de poesía de manera profesional y llegar a muchos lectores. El equipo es muy atento y profesional.',
            'estado' => 'aprobado',
            'es_destacado' => 1
        ],
        [
            'nombre' => 'David Sánchez Morales',
            'email' => 'david.sanchez@email.com',
            'calificacion' => 3,
            'testimonio' => 'La plataforma funciona correctamente, pero necesita algunas mejoras en la velocidad de carga y algunas funcionalidades adicionales. El servicio al cliente es bueno.',
            'estado' => 'pendiente',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Laura Jiménez Ruiz',
            'email' => 'laura.jimenez@email.com',
            'calificacion' => 4,
            'testimonio' => 'Buena plataforma con mucho potencial. El soporte al cliente es excelente y siempre están dispuestos a ayudar. La calidad del servicio es muy buena.',
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
            'testimonio' => 'Necesita muchas mejoras. La interfaz no es muy intuitiva y tuve algunos problemas técnicos durante el proceso de publicación. El soporte tardó en responder.',
            'estado' => 'rechazado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Miguel Ángel Castro',
            'email' => 'miguel.castro@email.com',
            'calificacion' => 4,
            'testimonio' => 'Buena experiencia general. La calidad del servicio es buena y el equipo es profesional. Cumple con lo prometido y es confiable.',
            'estado' => 'aprobado',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Carmen Rodríguez Soto',
            'email' => 'carmen.rodriguez@email.com',
            'calificacion' => 3,
            'testimonio' => 'Es funcional pero le faltan algunas características importantes. El precio es razonable pero necesita mejoras en la usabilidad.',
            'estado' => 'pendiente',
            'es_destacado' => 0
        ],
        [
            'nombre' => 'Fernando Gutiérrez',
            'email' => 'fernando.gutierrez@email.com',
            'calificacion' => 5,
            'testimonio' => '¡Fantástica experiencia! He publicado varios libros aquí y cada vez el proceso es mejor. El equipo mejora constantemente la plataforma.',
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
            $index * 3 // Días atrás para distribuir las fechas
        ]);

        $insertados++;
    }

    // Obtener estadísticas finales
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
        'message' => $insertados . ' testimonios de prueba insertados correctamente',
        'total' => $insertados,
        'estadisticas' => $stats,
        'promedio_calificacion' => round($promedio['promedio'], 1),
        'destacados' => array_sum(array_column($testimonios, 'es_destacado'))
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error insertando datos: ' . $e->getMessage()
    ]);
}
?>
