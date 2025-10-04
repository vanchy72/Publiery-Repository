<?php
// Incluir dependencias.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Establecer cabeceras después de iniciar la sesión.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar libros'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT l.id, l.titulo, l.categoria, l.precio, l.estado, l.fecha_publicacion, u.nombre as autor FROM libros l JOIN usuarios u ON l.autor_id = u.id ORDER BY l.id DESC");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'libros' => $libros
    ]);
} catch (Exception $e) {
    error_log('Error listando libros: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener libros: ' . $e->getMessage()
    ], 500);
}
?>
