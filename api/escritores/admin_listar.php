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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar escritores'], 403);
    exit;
}

try {
    // Consulta completa de escritores con estadísticas
    $sql = "
        SELECT 
            u.id,
            u.nombre,
            u.email,
            u.estado,
            u.biografia,
            u.fecha_registro,
            u.fecha_ultimo_login,
            COUNT(l.id) as libros_publicados,
            COALESCE(SUM(v.total), 0) as total_ventas
        FROM usuarios u
        LEFT JOIN libros l ON u.id = l.autor_id AND l.estado = 'publicado'
        LEFT JOIN ventas v ON l.id = v.libro_id
        WHERE u.rol = 'escritor'
        GROUP BY u.id
        ORDER BY u.id DESC
    ";
    
    $stmt = $db->query($sql);
    $escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los datos para el frontend
    $escritoresFormateados = array_map(function($escritor) {
        return [
            'id' => (int)$escritor['id'],
            'nombre' => $escritor['nombre'],
            'email' => $escritor['email'],
            'estado' => $escritor['estado'],
            'biografia' => $escritor['biografia'],
            'fecha_registro' => $escritor['fecha_registro'],
            'fecha_ultimo_login' => $escritor['fecha_ultimo_login'],
            'libros_publicados' => (int)$escritor['libros_publicados'],
            'total_ventas' => (float)$escritor['total_ventas']
        ];
    }, $escritores);
    
    jsonResponse([
        'success' => true,
        'escritores' => $escritoresFormateados,
        'total' => count($escritoresFormateados)
    ]);
    
} catch (Exception $e) {
    error_log('Error listando escritores para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener escritores: ' . $e->getMessage()
    ], 500);
}
?>
