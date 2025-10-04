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
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar pagos y comisiones'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT p.id, p.afiliado_id, u.nombre as afiliado_nombre, p.monto, p.tipo, p.fecha, p.estado, p.notas FROM pagos p JOIN usuarios u ON p.afiliado_id = u.id ORDER BY p.fecha DESC");
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'pagos' => $pagos
    ]);
} catch (Exception $e) {
    error_log('Error listando pagos y comisiones: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener pagos y comisiones: ' . $e->getMessage()
    ], 500);
}
?>
