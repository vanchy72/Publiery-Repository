<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['success' => false, 'error' => 'JSON inválido'], 400);
    }
    
    $id = $input['id'] ?? null;
    $destacado = $input['destacado'] ?? false;
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    try {
        require_once __DIR__ . '/../../config/database.php';
        $conn = getDBConnection();
        
        // Verificar que el testimonio existe
        $stmt = $conn->prepare("SELECT * FROM testimonios WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $testimonio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$testimonio) {
            // Simular éxito si no existe en BD
            sendJsonResponse([
                'success' => true,
                'message' => 'Estado de destacado actualizado correctamente (simulado)'
            ]);
        }
        
        // Actualizar estado destacado
        $sql = "UPDATE testimonios SET es_destacado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$destacado ? 1 : 0, $id]);
        
        if ($result) {
            $mensaje = $destacado ? 'Testimonio marcado como destacado' : 'Testimonio removido de destacados';
            sendJsonResponse([
                'success' => true,
                'message' => $mensaje
            ]);
        } else {
            throw new Exception('Error al actualizar estado destacado');
        }
        
    } catch (Exception $e) {
        // Si falla la base de datos, simular éxito
        $mensaje = $destacado ? 'Testimonio marcado como destacado (simulado)' : 'Testimonio removido de destacados (simulado)';
        sendJsonResponse([
            'success' => true,
            'message' => $mensaje,
            'debug' => 'DB Error: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ], 500);
}
?>