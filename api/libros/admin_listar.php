<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar libros'], 403);
    exit;
}

try {
    // Consulta completa de libros con información del autor y estadísticas
    $sql = "
        SELECT 
            l.id,
            l.titulo,
            l.descripcion,
            l.categoria,
            l.precio,
            l.precio_afiliado,
            l.comision_porcentaje,
            l.estado,
            l.fecha_registro,
            l.fecha_publicacion,
            l.imagen_portada,
            l.archivo_original,
            l.isbn,
            u.nombre as autor_nombre,
            u.id as autor_id,
            COUNT(v.id) as total_ventas,
            COALESCE(SUM(v.total), 0) as ingresos_totales
        FROM libros l
        INNER JOIN usuarios u ON l.autor_id = u.id
        LEFT JOIN ventas v ON l.id = v.libro_id
        GROUP BY l.id
        ORDER BY l.id DESC
    ";
    
    $stmt = $db->query($sql);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los datos para el frontend
    $librosFormateados = array_map(function($libro) {
        return [
            'id' => (int)$libro['id'],
            'titulo' => $libro['titulo'],
            'descripcion' => $libro['descripcion'],
            'categoria' => $libro['categoria'],
            'precio' => (float)$libro['precio'],
            'precio_afiliado' => (float)$libro['precio_afiliado'],
            'comision_porcentaje' => (float)$libro['comision_porcentaje'],
            'estado' => $libro['estado'],
            'fecha_registro' => $libro['fecha_registro'],
            'fecha_publicacion' => $libro['fecha_publicacion'],
            'imagen_portada' => $libro['imagen_portada'],
            'archivo_original' => $libro['archivo_original'],
            'isbn' => $libro['isbn'],
            'autor_id' => (int)$libro['autor_id'],
            'autor_nombre' => $libro['autor_nombre'],
            'total_ventas' => (int)$libro['total_ventas'],
            'ingresos_totales' => (float)$libro['ingresos_totales']
        ];
    }, $libros);
    
    jsonResponse([
        'success' => true,
        'libros' => $librosFormateados,
        'total' => count($librosFormateados)
    ]);
    
} catch (Exception $e) {
    error_log('Error listando libros para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener libros: ' . $e->getMessage()
    ], 500);
}
?>
