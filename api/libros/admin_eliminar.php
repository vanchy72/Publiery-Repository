<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden eliminar libros'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'ID de libro requerido'], 400);
    exit;
}

$id = (int)$input['id'];

try {
    $db->beginTransaction();
    
    // Verificar que el libro existe y obtener información
    $stmt = $db->prepare("SELECT l.id, l.titulo, u.nombre as autor_nombre FROM libros l INNER JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ?");
    $stmt->execute([$id]);
    $libro = $stmt->fetch();
    
    if (!$libro) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
        exit;
    }
    
    // Verificar si tiene ventas asociadas
    $stmt = $db->prepare("SELECT COUNT(*) as ventas FROM ventas WHERE libro_id = ?");
    $stmt->execute([$id]);
    $ventasCount = $stmt->fetch()['ventas'];
    
    if ($ventasCount > 0) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'No se puede eliminar un libro que tiene ventas asociadas'], 400);
        exit;
    }
    
    // Eliminar el libro
    $stmt = $db->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        jsonResponse([
            'success' => true, 
            'message' => "Libro '{$libro['titulo']}' eliminado correctamente"
        ]);
    } else {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'No se pudo eliminar el libro']);
    }
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Error eliminando libro: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
