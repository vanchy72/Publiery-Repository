<?php
/**
 * Configuración de PayU para Publiery
 * Credenciales de prueba (Sandbox)
 */

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/publiery');
}

// Configuración de entorno
define('PAYU_ENVIRONMENT', 'sandbox'); // Cambiar a 'production' para producción

// Credenciales de PayU (Sandbox)
if (PAYU_ENVIRONMENT === 'sandbox') {
    // Credenciales de prueba - REEMPLAZAR CON LAS TUS
    define('PAYU_API_KEY', '4Vj8eK4rloUd272L48hsrarnUA');
    define('PAYU_MERCHANT_ID', '508029');
    define('PAYU_ACCOUNT_ID', '512321');
    define('PAYU_API_LOGIN', 'pRRXKOl8ikMmt9u');
    define('PAYU_PUBLIC_KEY', 'PKaC6H4cEDJD919n705L544kSU');
    
    // URLs de Sandbox
    define('PAYU_BASE_URL', 'https://sandbox.api.payulatam.com');
    define('PAYU_PAYMENT_URL', 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/');
} else {
    // Credenciales de producción - NO USAR EN DESARROLLO
    define('PAYU_API_KEY', 'TU_API_KEY_PRODUCCION');
    define('PAYU_MERCHANT_ID', 'TU_MERCHANT_ID_PRODUCCION');
    define('PAYU_ACCOUNT_ID', 'TU_ACCOUNT_ID_PRODUCCION');
    define('PAYU_API_LOGIN', 'TU_API_LOGIN_PRODUCCION');
    
    // URLs de Producción
    define('PAYU_BASE_URL', 'https://api.payulatam.com');
    define('PAYU_PAYMENT_URL', 'https://checkout.payulatam.com/ppp-web-gateway-payu/');
}
// Eliminada la definición de APP_URL para evitar redefinición
// Configuración de la aplicación
define('PAYU_CURRENCY', 'COP'); // Peso Colombiano
define('PAYU_LANGUAGE', 'es'); // Español
define('PAYU_RESPONSE_URL', APP_URL . '/api/payu/response.php'); // URL de respuesta
define('PAYU_CONFIRMATION_URL', APP_URL . '/api/payu/confirmation.php'); // URL de confirmación

/**
 * Generar firma para PayU
 */
function generatePayUSignature($referenceCode, $amount, $currency) {
    $signature = PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenceCode . '~' . $amount . '~' . $currency;
    return md5($signature);
}

/**
 * Crear objeto de pago para PayU
 */
function createPayUPayment($orderData) {
    $referenceCode = 'PUBLIERY_' . time() . '_' . $orderData['user_id'];
    $amount = $orderData['total'];

    $payment = [
        'merchantId' => PAYU_MERCHANT_ID,
        'accountId' => PAYU_ACCOUNT_ID,
        'description' => $orderData['description'] ?? 'Compra en Publiery',
        'referenceCode' => $referenceCode,
        'amount' => $amount,
        'tax' => 0,
        'taxReturnBase' => 0,
        'currency' => PAYU_CURRENCY,
        'signature' => generatePayUSignature($referenceCode, $amount, PAYU_CURRENCY),
        'buyerEmail' => $orderData['buyer_email'],
        'responseUrl' => PAYU_RESPONSE_URL,
        'confirmationUrl' => PAYU_CONFIRMATION_URL,
        'test' => (PAYU_ENVIRONMENT === 'sandbox') ? 1 : 0,
        'extra1' => $orderData['user_id'] . '-' . $orderData['libro_id'] . '-' . ($orderData['afiliado_id'] ?? ''),
        'extra2' => ($orderData['referidor_id'] ?? '') . '-' . ($orderData['campana_id'] ?? ''), // referidor-campaña
    ];

    return $payment;
}

/**
 * Verificar respuesta de PayU
 */
function verifyPayUResponse($data) {
    $referenceCode = $data['referenceCode'] ?? '';
    $amount = $data['amount'] ?? '';
    $currency = $data['currency'] ?? '';
    $signature = $data['signature'] ?? '';
    
    $expectedSignature = generatePayUSignature($referenceCode, $amount, $currency);
    
    return $signature === $expectedSignature;
}

/**
 * Obtener estado de transacción
 */
function getPayUTransactionStatus($transactionId) {
    $url = PAYU_BASE_URL . '/reports-api/4.0/service.cgi';
    
    $data = [
        'merchantId' => PAYU_MERCHANT_ID,
        'accountId' => PAYU_ACCOUNT_ID,
        'transactionId' => $transactionId,
        'signature' => md5(PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $transactionId)
    ];
    
    // Aquí implementarías la llamada a la API de PayU
    // Por ahora retornamos un estado simulado
    return 'APPROVED';
} 