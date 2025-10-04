<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar autenticación simple
function isAdminAuth() {
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Obtener ID de la venta
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de venta requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener datos de la venta con información completa
    $sql = "SELECT v.id, v.fecha_venta, v.estado,
                   u.nombre as comprador_nombre, u.email as comprador_email,
                   l.titulo as libro_titulo, l.precio as libro_precio,
                   ua.nombre as autor_nombre, ua.email as autor_email,
                   af.nombre as afiliado_nombre, af.email as afiliado_email
            FROM ventas v 
            LEFT JOIN usuarios u ON v.usuario_id = u.id 
            LEFT JOIN libros l ON v.libro_id = l.id 
            LEFT JOIN usuarios ua ON l.autor_id = ua.id
            LEFT JOIN usuarios af ON v.afiliado_id = af.id
            WHERE v.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        sendResponse(['success' => false, 'error' => 'Venta no encontrada'], 404);
    }
    
    sendResponse([
        'success' => true,
        'venta' => $venta
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo venta: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener venta: ' . $e->getMessage()
    ], 500);
}
?>