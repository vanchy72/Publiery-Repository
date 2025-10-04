<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    // Obtener datos del formulario
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input ?? $_POST;
    
    // Validar campos requeridos
    $camposRequeridos = ['nombre', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (empty($data[$campo])) {
            sendResponse(['success' => false, 'error' => "Campo requerido: $campo"], 400);
        }
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que el email no existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'El email ya está en uso'], 400);
    }

    // Crear nuevo afiliado
    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, 'afiliado', ?, NOW())";
    
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $estado = $data['estado'] ?? 'activo';
    
    $stmt = $conn->prepare($sql);
    $resultado = $stmt->execute([
        $data['nombre'], 
        $data['email'], 
        $password_hash, 
        $estado
    ]);
    
    if ($resultado) {
        $nuevoId = $conn->lastInsertId();
        sendResponse([
            'success' => true,
            'mensaje' => 'Afiliado creado correctamente',
            'id' => $nuevoId
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al crear afiliado'], 500);
    }

} catch (Exception $e) {
    error_log('Error creando afiliado: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al crear afiliado: ' . $e->getMessage()
    ], 500);
}
?>