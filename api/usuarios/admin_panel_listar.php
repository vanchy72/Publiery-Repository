<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependencias
require_once __DIR__ . '/../../config/database.php';

// NOTA: Esta versión NO requiere autenticación - SOLO PARA DESARROLLO DEL PANEL ADMIN
try {
    $db = getDBConnection();
    $filtro = $_GET['filtro'] ?? '';
    
    $sql = "SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE nombre LIKE ? OR email LIKE ? OR rol LIKE ?";
        $params = ["%$filtro%", "%$filtro%", "%$filtro%"];
    }
    
    $sql .= " ORDER BY fecha_registro DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Solo datos reales de la base de datos - SIN datos de ejemplo
    
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios,
        'total' => count($usuarios),
        'mensaje' => 'Usuarios cargados correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en admin_panel_usuarios: " . $e->getMessage());
    
    // En caso de error, devolver respuesta vacía - SIN datos de ejemplo
    echo json_encode([
        'success' => false,
        'usuarios' => [],
        'total' => 0,
        'mensaje' => 'Error al cargar usuarios',
        'error_detalle' => $e->getMessage()
    ]);
}
?>