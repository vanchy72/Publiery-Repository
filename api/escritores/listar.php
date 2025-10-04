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
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar escritores'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, nombre, email, estado, fecha_registro, (SELECT COUNT(*) FROM libros WHERE autor_id = usuarios.id) as publicaciones FROM usuarios WHERE rol = 'escritor' ORDER BY id DESC");
    $escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'escritores' => $escritores
    ]);
} catch (Exception $e) {
    error_log('Error listando escritores: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener escritores: ' . $e->getMessage()
    ], 500);
}
?>
