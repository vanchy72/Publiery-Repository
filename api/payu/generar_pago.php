<?php
/**
 * Endpoint para generar formulario de pago PayU
 * Recibe datos de la compra y genera el formulario que se envía a PayU
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/payu.php';
require_once '../../config/database.php';

// --- AUTENTICACIÓN POR TOKEN O SESIÓN ---
function authenticateByToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
        if ($token) {
            $conn = getDBConnection();
            $stmt = $conn->prepare('SELECT usuario_id, fecha_expiracion, activa FROM sesiones WHERE token = ? LIMIT 1');
            $stmt->execute([$token]);
            $sesion = $stmt->fetch();
            if ($sesion && $sesion['activa'] && strtotime($sesion['fecha_expiracion']) > time()) {
                // Establecer la sesión PHP para compatibilidad
                $_SESSION['user_id'] = $sesion['usuario_id'];
                return true;
            }
        }
    }
    return false;
}

// Log temporal para depuración de sesión
define('PAYU_LOG', __DIR__ . '/log_sesion_payu.txt');
file_put_contents(PAYU_LOG, date('Y-m-d H:i:s') . " - SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Autenticación: primero por sesión, luego por token
if (!isAuthenticated()) {
    if (!authenticateByToken()) {
        file_put_contents(PAYU_LOG, date('Y-m-d H:i:s') . " - No autenticado\n", FILE_APPEND);
        jsonResponse(['error' => 'No autorizado'], 401);
    }
}
$user = getCurrentUser();
file_put_contents(PAYU_LOG, date('Y-m-d H:i:s') . " - Usuario detectado: " . print_r($user, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validar datos de entrada
    $libroId = intval($input['libro_id'] ?? 0);
    $userId = intval($input['user_id'] ?? 0);
    $afiliadoId = intval($input['afiliado_id'] ?? 0);
    $cantidad = intval($input['cantidad'] ?? 1);
    
    if (!$libroId || !$userId) {
        jsonResponse(['error' => 'Datos de compra incompletos'], 400);
    }
    
    // Solo admin puede generar pagos para otros
    if ($user['rol'] !== 'admin' && $userId !== $user['id']) {
        jsonResponse(['error' => 'No autorizado para generar pagos para otros usuarios'], 403);
    }
    // Validar afiliado_id: solo el propio o nulo (excepto admin)
    if ($user['rol'] !== 'admin' && $afiliadoId) {
        $conn = getDBConnection();
        $stmt = $conn->prepare('SELECT id FROM afiliados WHERE usuario_id = ?');
        $stmt->execute([$user['id']]);
        $afiliado = $stmt->fetch();
        if (!$afiliado || $afiliado['id'] != $afiliadoId) {
            jsonResponse(['error' => 'No autorizado para usar ese afiliado_id'], 403);
        }
    }
    
    $conn = getDBConnection();
    
    // Obtener información del libro
    $stmt = $conn->prepare("SELECT l.*, u.nombre as autor_nombre FROM libros l JOIN usuarios u ON l.autor_id = u.id WHERE l.id = ? AND l.estado = 'publicado'");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch();
    if (!$libro) {
        jsonResponse(['error' => 'Libro no encontrado o no disponible'], 404);
    }
    
    // Obtener información del comprador
    $stmt = $conn->prepare("SELECT id, nombre, email, documento FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $comprador = $stmt->fetch();
    if (!$comprador) {
        jsonResponse(['error' => 'Comprador no encontrado'], 404);
    }
    
    // Calcular total
    $total = $libro['precio'] * $cantidad;
    
    // Crear datos de la orden
    $orderData = [
        'user_id' => $userId,
        'libro_id' => $libroId,
        'afiliado_id' => $afiliadoId ?: null,
        'total' => $total,
        'description' => "Compra: {$libro['titulo']} - {$libro['autor_nombre']}",
        'buyer_email' => $comprador['email'],
        'buyer_name' => $comprador['nombre'],
        'buyer_document' => $comprador['documento']
    ];
    
    // Generar objeto de pago PayU
    $paymentData = createPayUPayment($orderData);
    
    // RESPUESTA JSON para el frontend
    jsonResponse([
        'success' => true,
        'payu_url' => PAYU_PAYMENT_URL,
        'payu_data' => $paymentData
    ]);
    
} catch (Exception $e) {
    error_log('Error generando pago PayU: ' . $e->getMessage());
    echo '<pre>Error generando pago PayU: ' . $e->getMessage() . '</pre>';
    exit;
} 