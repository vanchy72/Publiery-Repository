<?php
/**
 * API para manejar la respuesta de PayU
 */

require_once '../../config/database.php';
require_once '../../config/payu.php';

// Log de la respuesta
error_log('PayU Response: ' . print_r($_POST, true));

try {
    // Verificar que vengan los datos necesarios
    if (!isset($_POST['referenceCode']) || !isset($_POST['lapTransactionState'])) {
        throw new Exception('Datos incompletos de PayU');
    }
    
    $referenciaPago = $_POST['referenceCode'];
    $estadoTransaccion = $_POST['lapTransactionState'];
    $valorPagado = $_POST['TX_VALUE'] ?? 0;
    $moneda = $_POST['currency'] ?? 'COP';
    $firma = $_POST['signature'] ?? '';
    $ventaId = $_POST['extra1'] ?? null;
    
    $conn = getDBConnection();
    
    // Buscar la venta
    $stmt = $conn->prepare("SELECT * FROM ventas WHERE referencia_pago = ?");
    $stmt->execute([$referenciaPago]);
    $venta = $stmt->fetch();
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
    // Verificar firma
    $signatureString = PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenciaPago . '~' . $valorPagado . '~' . $moneda;
    $firmaCalculada = md5($signatureString);
    
    if ($firma !== $firmaCalculada) {
        throw new Exception('Firma inválida');
    }
    
    // Actualizar estado de la venta según la respuesta de PayU
    $nuevoEstado = 'pendiente';
    $fechaPago = null;
    
    switch ($estadoTransaccion) {
        case 'APPROVED':
            $nuevoEstado = 'pagado';
            $fechaPago = date('Y-m-d H:i:s');
            
            // Activar cuenta del afiliado si no está activa
            $stmt = $conn->prepare("SELECT estado FROM usuarios WHERE id = ?");
            $stmt->execute([$venta['afiliado_id']]);
            $afiliado = $stmt->fetch();
            
            if ($afiliado && $afiliado['estado'] !== 'activo') {
                // Activar cuenta
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ?");
                $stmt->execute([$venta['afiliado_id']]);
                
                // Registrar activación
                $stmt = $conn->prepare("
                    INSERT INTO activaciones (afiliado_id, tipo_activacion, comentario) 
                    VALUES (?, 'compra', 'Activación por compra de libro')
                ");
                $stmt->execute([$venta['afiliado_id']]);
            }
            break;
            
        case 'DECLINED':
            $nuevoEstado = 'cancelado';
            break;
            
        case 'PENDING':
            $nuevoEstado = 'pendiente';
            break;
            
        default:
            $nuevoEstado = 'cancelado';
            break;
    }
    
    // Actualizar venta
    $stmt = $conn->prepare("
        UPDATE ventas 
        SET estado = ?, fecha_pago = ?, metodo_pago = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $nuevoEstado,
        $fechaPago,
        $_POST['lapPaymentMethod'] ?? 'unknown',
        $venta['id']
    ]);
    
    // Redirigir según el estado
    if ($estadoTransaccion === 'APPROVED') {
        $redirectUrl = APP_URL . "/descarga.html?venta={$venta['id']}";
    } else {
        $redirectUrl = APP_URL . "/pago.html?libro={$venta['libro_id']}&error=1";
    }
    
    header("Location: $redirectUrl");
    exit;
    
} catch (Exception $e) {
    error_log('Error en PayU Response: ' . $e->getMessage());
    
    // Redirigir a página de error
    header("Location: " . APP_URL . "/pago.html?error=1");
    exit;
}
?> 