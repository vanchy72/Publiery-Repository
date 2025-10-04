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

    // Obtener ID del escritor
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de escritor requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener datos del escritor con información completa
    $sql = "SELECT e.id, u.nombre, u.email, e.estado, u.fecha_registro,
                   e.estado_activacion, e.fecha_activacion,
                   COUNT(DISTINCT l.id) as publicaciones,
                   COUNT(DISTINCT v.id) as ventas_totales,
                   0 as royalties_totales,
                   NULL as ultimo_pago
            FROM escritores e 
            JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN libros l ON u.id = l.autor_id 
            LEFT JOIN ventas v ON l.id = v.libro_id
            WHERE e.id = ?
            GROUP BY e.id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escritor) {
        sendResponse(['success' => false, 'error' => 'Escritor no encontrado'], 404);
    }
    
    sendResponse([
        'success' => true,
        'escritor' => $escritor
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo escritor: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener escritor: ' . $e->getMessage()
    ], 500);
}
?>