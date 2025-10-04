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
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden listar afiliados'], 403);
}

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, nombre, email, estado, fecha_registro FROM usuarios WHERE rol = 'afiliado' ORDER BY id DESC");
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'afiliados' => $afiliados
    ]);
} catch (Exception $e) {
    error_log('Error listando afiliados: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener afiliados: ' . $e->getMessage()
    ], 500);
}
?>
