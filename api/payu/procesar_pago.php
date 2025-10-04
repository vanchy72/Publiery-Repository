<?php
/**
 * API para procesar pagos con PayU
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once '../../config/payu.php';

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
$user = getCurrentUser();

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }
    
    $libroId = $input['libro_id'] ?? null;
    $afiliadoId = $input['afiliado_id'] ?? null;
    $metodoPago = $input['metodo_pago'] ?? 'credit_card';
    
    if (!$libroId || !$afiliadoId) {
        throw new Exception('Faltan datos requeridos');
    }
    
    // Solo admin puede procesar pagos para otros afiliados
    if ($user['rol'] !== 'admin') {
        $conn = getDBConnection();
        $stmt = $conn->prepare('SELECT id FROM afiliados WHERE usuario_id = ?');
        $stmt->execute([$user['id']]);
        $afiliado = $stmt->fetch();
        if (!$afiliado || $afiliado['id'] != $afiliadoId) {
            throw new Exception('No autorizado para usar ese afiliado_id');
        }
    }
    
    $conn = getDBConnection();
    
    // Obtener información del libro
    $stmt = $conn->prepare("
        SELECT l.*, u.nombre as autor_nombre 
        FROM libros l 
        JOIN usuarios u ON l.autor_id = u.id 
        WHERE l.id = ? AND l.estado = 'publicado'
    ");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch();
    
    if (!$libro) {
        throw new Exception('Libro no encontrado');
    }
    
    // Obtener información del afiliado
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'afiliado'");
    $stmt->execute([$afiliadoId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        throw new Exception('Afiliado no encontrado');
    }
    
    // Calcular comisión (ejemplo: 10% del precio de afiliado)
    $precioPagado = $libro['precio_afiliado'];
    $comisionAfiliado = $precioPagado * 0.10; // 10% de comisión
    
    // Crear registro de venta
    $stmt = $conn->prepare("
        INSERT INTO ventas (afiliado_id, libro_id, precio_pagado, comision_afiliado, metodo_pago, ip_compra, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $afiliadoId,
        $libroId,
        $precioPagado,
        $comisionAfiliado,
        $metodoPago,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    $ventaId = $conn->lastInsertId();
    
    // Generar referencia única para PayU
    $referenciaPago = 'PUBLIERY_' . $ventaId . '_' . time();
    
    // Actualizar referencia en la venta
    $stmt = $conn->prepare("UPDATE ventas SET referencia_pago = ? WHERE id = ?");
    $stmt->execute([$referenciaPago, $ventaId]);
    
    // Configurar datos para PayU
    $payuData = [
        'merchantId' => PAYU_MERCHANT_ID,
        'accountId' => PAYU_ACCOUNT_ID,
        'description' => "Compra: {$libro['titulo']}",
        'referenceCode' => $referenciaPago,
        'amount' => $precioPagado,
        'tax' => 0,
        'taxReturnBase' => $precioPagado,
        'currency' => 'COP',
        'signature' => '', // Se calculará después
        'test' => PAYU_TEST_MODE ? '1' : '0',
        'buyerEmail' => $afiliado['email'],
        'buyerFullName' => $afiliado['nombre'],
        'responseUrl' => APP_URL . '/api/payu/response.php',
        'confirmationUrl' => APP_URL . '/api/payu/confirmation.php',
        'extra1' => $ventaId, // ID de la venta
        'extra2' => $libroId, // ID del libro
        'extra3' => $afiliadoId // ID del afiliado
    ];
    
    // Calcular firma
    $signatureString = PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenciaPago . '~' . $precioPagado . '~' . 'COP';
    $payuData['signature'] = md5($signatureString);
    
    // URL de PayU
    $payuUrl = PAYU_TEST_MODE ? PAYU_TEST_URL : PAYU_PRODUCTION_URL;
    
    echo json_encode([
        'success' => true,
        'venta_id' => $ventaId,
        'referencia_pago' => $referenciaPago,
        'payu_data' => $payuData,
        'payu_url' => $payuUrl,
        'libro' => [
            'titulo' => $libro['titulo'],
            'precio' => $precioPagado,
            'comision' => $comisionAfiliado
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 