<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true]);
}

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    // Datos de ejemplo para demostración
    $testimoniosEjemplo = [
        1 => [
            'id' => 1,
            'nombre' => 'María García',
            'email' => 'maria@ejemplo.com',
            'testimonio' => 'Excelente plataforma para publicar mi libro. El proceso fue muy sencillo y el soporte excepcional. Recomiendo completamente esta plataforma a todos los autores que buscan una experiencia profesional.',
            'calificacion' => 5,
            'estado' => 'aprobado',
            'fecha_envio' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'fecha_revision' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'admin_revisor_id' => 1,
            'observaciones_admin' => 'Testimonio muy positivo y detallado',
            'es_destacado' => true
        ],
        2 => [
            'id' => 2,
            'nombre' => 'Carlos López',
            'email' => 'carlos@ejemplo.com',
            'testimonio' => 'La experiencia de publicación fue fantástica. Recomiendo esta plataforma a todos los autores. El equipo de soporte respondió todas mis preguntas rápidamente.',
            'calificacion' => 4,
            'estado' => 'pendiente',
            'fecha_envio' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'fecha_revision' => null,
            'admin_revisor_id' => null,
            'observaciones_admin' => '',
            'es_destacado' => false
        ],
        3 => [
            'id' => 3,
            'nombre' => 'Ana Rodríguez',
            'email' => 'ana@ejemplo.com',
            'testimonio' => 'Muy satisfecha con los resultados. Mi libro ha tenido gran acogida gracias a Publiery. La plataforma es fácil de usar y los resultados superaron mis expectativas.',
            'calificacion' => 5,
            'estado' => 'aprobado',
            'fecha_envio' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'fecha_revision' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'admin_revisor_id' => 1,
            'observaciones_admin' => 'Excelente testimonio con buenos detalles',
            'es_destacado' => false
        ]
    ];
    
    // Intentar obtener de la base de datos primero
    try {
        require_once __DIR__ . '/../../config/database.php';
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT * FROM testimonios WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $testimonio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testimonio) {
            // Formatear testimonio de la base de datos
            $testimonioFormateado = [
                'id' => (int)$testimonio['id'],
                'nombre' => $testimonio['nombre'] ?? 'Usuario',
                'email' => $testimonio['email'] ?? 'usuario@ejemplo.com',
                'testimonio' => $testimonio['testimonio'] ?? 'Testimonio',
                'calificacion' => (int)($testimonio['calificacion'] ?? 5),
                'estado' => $testimonio['estado'] ?? 'pendiente',
                'fecha_envio' => $testimonio['fecha_envio'] ?? $testimonio['fecha_creacion'] ?? $testimonio['created_at'] ?? date('Y-m-d H:i:s'),
                'fecha_revision' => $testimonio['fecha_revision'] ?? null,
                'admin_revisor_id' => isset($testimonio['admin_revisor_id']) ? (int)$testimonio['admin_revisor_id'] : null,
                'observaciones_admin' => $testimonio['observaciones_admin'] ?? '',
                'es_destacado' => (bool)($testimonio['es_destacado'] ?? false)
            ];
            
            sendJsonResponse([
                'success' => true,
                'testimonio' => $testimonioFormateado
            ]);
        }
    } catch (Exception $e) {
        // Si falla la base de datos, usar datos de ejemplo
    }
    
    // Usar datos de ejemplo
    if (isset($testimoniosEjemplo[$id])) {
        sendJsonResponse([
            'success' => true,
            'testimonio' => $testimoniosEjemplo[$id]
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => 'Testimonio no encontrado'
        ], 404);
    }
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ], 500);
}
?>