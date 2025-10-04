<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden ver los detalles de libros para revisión.'], 403);
}

// Validar que el ID del libro esté presente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(['message' => 'ID de libro no proporcionado o inválido.'], 400);
}

$libroId = $_GET['id'];

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            l.id, 
            l.titulo, 
            l.categoria, 
            l.precio, 
            l.descripcion, 
            l.portada_url, 
            l.archivo_original, 
            l.archivo_editado, 
            l.estado, 
            l.comentarios_editorial, 
            l.fecha_publicacion, 
            l.fecha_revision, 
            u.nombre AS autor_nombre,
            u.email AS autor_email
        FROM 
            libros l
        JOIN 
            usuarios u ON l.autor_id = u.id
        WHERE 
            l.id = ?
    ");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        jsonResponse(['success' => true, 'libro' => $libro], 200);
    } else {
        jsonResponse(['message' => 'Libro no encontrado.'], 404);
    }

} catch (PDOException $e) {
    error_log("Error al obtener detalles del libro para revisión: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al obtener detalles del libro', 'error' => $e->getMessage()], 500);
}
?>
