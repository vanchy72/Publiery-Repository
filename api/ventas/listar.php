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
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar ventas'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT v.id, v.libro_id, l.titulo as libro_titulo, v.comprador_id, uc.nombre as comprador_nombre, v.precio_venta, v.fecha_venta, v.estado, ua.nombre as libro_autor_nombre FROM ventas v JOIN libros l ON v.libro_id = l.id JOIN usuarios uc ON v.comprador_id = uc.id JOIN usuarios ua ON l.autor_id = ua.id ORDER BY v.fecha_venta DESC");
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'ventas' => $ventas
    ]);
} catch (Exception $e) {
    error_log('Error listando ventas: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener ventas: ' . $e->getMessage()
    ], 500);
}
?>
