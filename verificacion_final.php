<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    $resultados = [];

    // 1. Verificar que hay testimonios en la BD
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM testimonios');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalTestimonios = $result['total'];
    $resultados['testimonios_total'] = $totalTestimonios;

    // 2. Verificar estadísticas
    $stmt = $pdo->prepare('SELECT estado, COUNT(*) as cantidad FROM testimonios GROUP BY estado');
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [];
    foreach ($estadisticas as $stat) {
        $stats[$stat['estado']] = (int)$stat['cantidad'];
    }
    $resultados['estadisticas'] = $stats;

    // 3. Verificar promedio de calificación
    $stmt = $pdo->query('SELECT AVG(calificacion) as promedio FROM testimonios');
    $promedio = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultados['promedio_calificacion'] = round($promedio['promedio'], 1);

    // 4. Verificar testimonios destacados
    $stmt = $pdo->prepare('SELECT COUNT(*) as destacados FROM testimonios WHERE es_destacado = 1');
    $stmt->execute();
    $destacados = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultados['destacados'] = (int)$destacados['destacados'];

    // 5. Verificar API principal
    $apiUrl = "http://localhost/publiery/api/testimonios/admin_listar.php";
    $apiResponse = @file_get_contents($apiUrl);
    $apiWorking = false;

    if ($apiResponse !== false) {
        $apiData = json_decode($apiResponse, true);
        $apiWorking = $apiData && isset($apiData['success']) && $apiData['success'];
    }

    $resultados['api_working'] = $apiWorking;

    // 6. Verificar distribución de estados
    $aprobados = $stats['aprobado'] ?? 0;
    $pendientes = $stats['pendiente'] ?? 0;
    $rechazados = $stats['rechazado'] ?? 0;

    $resultados['distribucion'] = [
        'aprobados' => $aprobados,
        'pendientes' => $pendientes,
        'rechazados' => $rechazados
    ];

    // Determinar estado general
    $estadoGeneral = 'error';
    $mensajeEstado = 'Sistema con problemas';

    if ($totalTestimonios > 0 && $apiWorking && ($aprobados + $pendientes + $rechazados) === $totalTestimonios) {
        $estadoGeneral = 'success';
        $mensajeEstado = 'Sistema funcionando correctamente';
    } elseif ($totalTestimonios > 0) {
        $estadoGeneral = 'warning';
        $mensajeEstado = 'Testimonios presentes, pero hay problemas menores';
    }

    echo json_encode([
        'success' => $estadoGeneral === 'success',
        'estado' => $estadoGeneral,
        'message' => $mensajeEstado,
        'resultados' => $resultados,
        'resumen' => [
            'testimonios' => $totalTestimonios,
            'api_funcionando' => $apiWorking,
            'promedio' => $resultados['promedio_calificacion'],
            'destacados' => $resultados['destacados']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'estado' => 'error',
        'message' => 'Error en verificación final: ' . $e->getMessage(),
        'resultados' => []
    ]);
}
?>
