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
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar campañas y notificaciones'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, nombre, tipo, fecha_programada, estado FROM campanas ORDER BY fecha_programada DESC");
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'campanas' => $campanas
    ]);
} catch (Exception $e) {
    error_log('Error listando campañas y notificaciones: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener campañas y notificaciones: ' . $e->getMessage()
    ], 500);
}
?>
