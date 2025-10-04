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

    // Obtener ID del afiliado
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de afiliado requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener datos del afiliado con información básica
    $sql = "SELECT u.id, u.nombre, u.email, u.estado, u.fecha_registro,
                   COUNT(v.id) as ventas_realizadas
            FROM usuarios u 
            LEFT JOIN ventas v ON u.id = v.afiliado_id 
            WHERE u.rol = 'afiliado' AND u.id = ?
            GROUP BY u.id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$afiliado) {
        sendResponse(['success' => false, 'error' => 'Afiliado no encontrado'], 404);
    }
    
    sendResponse([
        'success' => true,
        'afiliado' => $afiliado
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo afiliado: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener afiliado: ' . $e->getMessage()
    ], 500);
}
?>