<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Verificar si existe tabla testimonios
    $stmt = $conn->prepare("SHOW TABLES LIKE 'testimonios'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Si existe la tabla, obtener testimonios
        $stmt = $conn->prepare("SELECT * FROM testimonios WHERE estado = 'publicado' ORDER BY fecha_creacion DESC LIMIT 3");
        $stmt->execute();
        $testimonios = $stmt->fetchAll();
    } else {
        // Si no existe, usar testimonios de ejemplo
        $testimonios = [];
    }
    
    // Si no hay testimonios en BD, usar ejemplos
    if (empty($testimonios)) {
        $testimonios = [
            [
                'nombre_autor' => 'Ana Torres',
                'testimonio' => 'Publiery me ayudó a alcanzar miles de lectores con mi primer libro. La plataforma es increíble.',
                'imagen_url' => 'images/ana_torres.jpeg'
            ],
            [
                'nombre_autor' => 'Carlos Mendez',
                'testimonio' => 'Como afiliado, he generado ingresos consistentes promoviendo los libros de esta plataforma.',
                'imagen_url' => 'images/default-author.jpg'
            ],
            [
                'nombre_autor' => 'Lucía Rivera',
                'testimonio' => 'La mejor inversión que he hecho. Mi libro se ha vendido más de lo que esperaba.',
                'imagen_url' => 'images/lucia_rivera.jpg'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'testimonios' => $testimonios
    ]);
    
} catch (Exception $e) {
    // En caso de error, devolver testimonios de ejemplo
    echo json_encode([
        'success' => true,
        'testimonios' => [
            [
                'nombre_autor' => 'Ana Torres',
                'testimonio' => 'Publiery me ayudó a alcanzar miles de lectores con mi primer libro. La plataforma es increíble.',
                'imagen_url' => 'images/ana_torres.jpeg'
            ],
            [
                'nombre_autor' => 'Carlos Mendez',
                'testimonio' => 'Como afiliado, he generado ingresos consistentes promoviendo los libros de esta plataforma.',
                'imagen_url' => 'images/default-author.jpg'
            ]
        ]
    ]);
}
?>
