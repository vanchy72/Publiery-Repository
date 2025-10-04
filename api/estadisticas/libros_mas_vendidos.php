<?php
// Incluir dependencias.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Establecer cabeceras después de iniciar la sesión.
//header('Content-Type: application/json'); // Las cabeceras ya se manejan en obtener_estadisticas.php
//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Methods: GET, OPTIONS');
//header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden ver esta estadística'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT l.titulo, COUNT(v.id) as total_ventas FROM ventas v JOIN libros l ON v.libro_id = l.id GROUP BY l.id, l.titulo ORDER BY total_ventas DESC LIMIT 5");
    $librosMasVendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'libros_mas_vendidos' => $librosMasVendidos
    ]);
} catch (Exception $e) {
    error_log('Error al obtener libros más vendidos: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener libros más vendidos: ' . $e->getMessage()
    ], 500);
}
?>
