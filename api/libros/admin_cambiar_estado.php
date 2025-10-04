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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden cambiar estados de libros'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['estado'])) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    exit;
}

$id = (int)$input['id'];
$estado = $input['estado'];

$estadosValidos = ['pendiente_revision', 'en_revision', 'correccion_autor', 'aprobado_autor', 'publicado', 'rechazado'];

if (!in_array($estado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    // Verificar que el libro existe
    $stmt = $db->prepare("SELECT l.id, l.titulo, u.nombre as autor_nombre FROM libros l INNER JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ?");
    $stmt->execute([$id]);
    $libro = $stmt->fetch();
    
    if (!$libro) {
        jsonResponse(['success' => false, 'error' => 'Libro no encontrado'], 404);
        exit;
    }
    
    // Actualizar estado y fecha de publicación si corresponde
    if ($estado === 'publicado') {
        $stmt = $db->prepare("UPDATE libros SET estado = ?, fecha_publicacion = NOW() WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE libros SET estado = ? WHERE id = ?");
    }
    $stmt->execute([$estado, $id]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse([
            'success' => true, 
            'message' => "Estado del libro '{$libro['titulo']}' cambiado a '{$estado}'"
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'No se realizaron cambios']);
    }
    
} catch (Exception $e) {
    error_log('Error cambiando estado de libro: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
