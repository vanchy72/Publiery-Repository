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
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden ver los libros para revisión.'], 403);
}

try {
    $pdo = getDBConnection();
    
    // Definir los estados que se consideran pendientes de revisión
    $estadosRevision = ['pendiente_revision', 'en_revision', 'correccion_autor'];
    $placeholders = implode(',', array_fill(0, count($estadosRevision), '?'));

    $stmt = $pdo->prepare("
        SELECT 
            l.id, 
            l.titulo, 
            u.nombre AS autor_nombre, 
            l.estado, 
            l.fecha_publicacion, 
            l.fecha_revision
        FROM 
            libros l
        JOIN 
            usuarios u ON l.autor_id = u.id
        WHERE 
            l.estado IN ({$placeholders})
        ORDER BY 
            l.fecha_publicacion ASC
    ");
    $stmt->execute($estadosRevision);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'libros' => $libros], 200);

} catch (PDOException $e) {
    error_log("Error al obtener libros pendientes de revisión: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al obtener libros para revisión', 'error' => $e->getMessage()], 500);
}
?>
