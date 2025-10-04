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
    $stmt = $db->query("SELECT estado, COUNT(id) as total FROM pagos GROUP BY estado");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pagosPorEstado = [];
    foreach ($results as $row) {
        $pagosPorEstado[$row['estado']] = (int)$row['total'];
    }
    
    jsonResponse([
        'success' => true,
        'pagos_por_estado' => $pagosPorEstado
    ]);
} catch (Exception $e) {
    error_log('Error al obtener pagos por estado: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener pagos por estado: ' . $e->getMessage()
    ], 500);
}
?>
