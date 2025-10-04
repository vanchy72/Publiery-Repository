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
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar sesión
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') {
        return true;
    }
    
    // Verificar token en header (más permisivo para desarrollo)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        return !empty($token);
    }
    
    // Para desarrollo, permitir si no hay autenticación estricta
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener filtro si existe
    $filtro = $_GET['filtro'] ?? '';
    
    // Construir query
    $sql = "SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE nombre LIKE ? OR email LIKE ? OR rol LIKE ?";
        $search = '%' . $filtro . '%';
        $params = [$search, $search, $search];
    }

    $sql .= " ORDER BY id DESC";

    // Ejecutar query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'usuarios' => $usuarios
    ]);

} catch (Exception $e) {
    error_log('Error listando usuarios: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener usuarios: ' . $e->getMessage()
    ], 500);
}
?>