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

    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $nuevo_estado = $input['nuevo_estado'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de comisión requerido'], 400);
    }

    if (!$nuevo_estado) {
        sendResponse(['success' => false, 'error' => 'Nuevo estado requerido'], 400);
    }

    // Validar estado
    $estados_validos = ['pendiente', 'pagada', 'cancelada'];
    if (!in_array($nuevo_estado, $estados_validos)) {
        sendResponse(['success' => false, 'error' => 'Estado no válido. Debe ser: ' . implode(', ', $estados_validos)], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Verificar que la comisión existe
    $stmt = $conn->prepare("SELECT id, monto, tipo, estado, usuario_id FROM comisiones WHERE id = ?");
    $stmt->execute([$id]);
    $comision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comision) {
        sendResponse(['success' => false, 'error' => 'Comisión no encontrada'], 404);
    }

    // Preparar la actualización
    if ($nuevo_estado === 'pagada') {
        // Si se está marcando como pagada, establecer fecha de pago
        $stmt = $conn->prepare("UPDATE comisiones SET estado = ?, fecha_pago = NOW() WHERE id = ?");
        $resultado = $stmt->execute([$nuevo_estado, $id]);
    } else {
        // Si se está cambiando a otro estado, mantener o limpiar fecha_pago según sea necesario
        $fecha_pago = ($nuevo_estado === 'cancelada') ? null : 'fecha_pago';
        if ($nuevo_estado === 'cancelada') {
            $stmt = $conn->prepare("UPDATE comisiones SET estado = ?, fecha_pago = NULL WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE comisiones SET estado = ? WHERE id = ?");
        }
        $resultado = $stmt->execute([$nuevo_estado, $id]);
    }
    
    if ($resultado) {
        $mensaje_accion = '';
        switch($nuevo_estado) {
            case 'pagada':
                $mensaje_accion = 'marcada como pagada. Fecha de pago actualizada.';
                break;
            case 'pendiente':
                $mensaje_accion = 'marcada como pendiente';
                break;
            case 'cancelada':
                $mensaje_accion = 'cancelada';
                break;
        }
        
        sendResponse([
            'success' => true,
            'mensaje' => "Comisión {$mensaje_accion}",
            'nuevo_estado' => $nuevo_estado,
            'comision_info' => [
                'id' => $id,
                'monto' => $comision['monto'],
                'tipo' => $comision['tipo']
            ]
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Error al cambiar estado de la comisión'], 500);
    }

} catch (Exception $e) {
    error_log('Error cambiando estado comisión: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al cambiar estado: ' . $e->getMessage()
    ], 500);
}
?>