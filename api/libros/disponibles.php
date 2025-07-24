<?php
/**
 * Endpoint para obtener libros disponibles
 * Retorna todos los libros publicados con información del autor
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    $conn = getDBConnection();
    
    // Obtener libros publicados con información del autor
    $query = "
        SELECT 
            l.id,
            l.titulo,
            l.descripcion,
            l.precio,
            l.imagen_portada,
            l.fecha_publicacion,
            u.nombre as autor_nombre,
            u.id as autor_id,
            u.foto as autor_foto,
            u.biografia as autor_bio
        FROM libros l
        JOIN usuarios u ON l.autor_id = u.id
        WHERE l.estado = 'publicado'
        ORDER BY l.fecha_publicacion DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    foreach ($libros as &$libro) {
        $libro['precio'] = floatval($libro['precio']);
        $libro['autor_id'] = intval($libro['autor_id']);
        // Si no hay imagen de portada, usar una por defecto
        if (empty($libro['imagen_portada'])) {
            $libro['imagen_portada'] = 'default-book.jpg';
        }
        // Si no hay foto de autor, usar una por defecto
        if (empty($libro['autor_foto'])) {
            $libro['autor_foto'] = 'default-author.jpg';
        }
        // Si no hay biografía, poner texto vacío
        if (empty($libro['autor_bio'])) {
            $libro['autor_bio'] = '';
        }
    }
    
    jsonResponse([
        'success' => true,
        'libros' => $libros,
        'total' => count($libros)
    ], 200);
    
} catch (Exception $e) {
    error_log('Error obteniendo libros disponibles: ' . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?> 