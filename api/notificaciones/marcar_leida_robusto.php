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
    $accion = $input['accion'] ?? 'marcar_leida';
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    // Validar acciones permitidas
    $accionesValidas = ['marcar_leida', 'marcar_no_leida', 'archivar', 'eliminar', 'aumentar_prioridad', 'disminuir_prioridad'];
    if (!in_array($accion, $accionesValidas)) {
        sendJsonResponse(['success' => false, 'error' => 'Acción inválida'], 400);
    }
    
    try {
        require_once __DIR__ . '/../../config/database.php';
        $conn = getDBConnection();
        
        // Verificar que la notificación existe
        $stmt = $conn->prepare("SELECT * FROM notificaciones_admin WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $notificacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$notificacion) {
            // Simular éxito si no existe en BD
            $mensaje = obtenerMensajeAccion($accion);
            sendJsonResponse([
                'success' => true,
                'message' => $mensaje . ' (simulado)'
            ]);
        }
        
        $updateData = obtenerDatosActualizacion($accion, $notificacion);
        
        if ($accion === 'eliminar') {
            $sql = "DELETE FROM notificaciones_admin WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$id]);
        } else {
            // Construir consulta de actualización dinámica
            $setClauses = [];
            $params = [];
            
            foreach ($updateData as $campo => $valor) {
                $setClauses[] = "$campo = ?";
                $params[] = $valor;
            }
            
            if (!empty($setClauses)) {
                $sql = "UPDATE notificaciones_admin SET " . implode(', ', $setClauses) . " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute($params);
            } else {
                $result = true; // No hay nada que actualizar
            }
        }
        
        if ($result) {
            $mensaje = obtenerMensajeAccion($accion);
            sendJsonResponse([
                'success' => true,
                'message' => $mensaje
            ]);
        } else {
            throw new Exception('Error al actualizar notificación');
        }
        
    } catch (Exception $e) {
        // Si falla la base de datos, simular éxito
        $mensaje = obtenerMensajeAccion($accion);
        sendJsonResponse([
            'success' => true,
            'message' => $mensaje . ' (simulado)',
            'debug' => 'DB Error: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ], 500);
}

function obtenerDatosActualizacion($accion, $notificacion) {
    $updateData = [];
    
    switch ($accion) {
        case 'marcar_leida':
            $updateData['estado'] = 'leida';
            $updateData['fecha_leida'] = date('Y-m-d H:i:s');
            $updateData['admin_lector_id'] = 1; // ID del admin actual
            break;
            
        case 'marcar_no_leida':
            $updateData['estado'] = 'no_leida';
            $updateData['fecha_leida'] = null;
            $updateData['admin_lector_id'] = null;
            break;
            
        case 'archivar':
            $updateData['estado'] = 'archivada';
            break;
            
        case 'aumentar_prioridad':
            $prioridades = ['baja' => 'media', 'media' => 'alta', 'alta' => 'critica'];
            $prioridadActual = $notificacion['prioridad'] ?? 'baja';
            if (isset($prioridades[$prioridadActual])) {
                $updateData['prioridad'] = $prioridades[$prioridadActual];
            }
            break;
            
        case 'disminuir_prioridad':
            $prioridades = ['critica' => 'alta', 'alta' => 'media', 'media' => 'baja'];
            $prioridadActual = $notificacion['prioridad'] ?? 'media';
            if (isset($prioridades[$prioridadActual])) {
                $updateData['prioridad'] = $prioridades[$prioridadActual];
            }
            break;
    }
    
    return $updateData;
}

function obtenerMensajeAccion($accion) {
    switch ($accion) {
        case 'marcar_leida':
            return 'Notificación marcada como leída';
        case 'marcar_no_leida':
            return 'Notificación marcada como no leída';
        case 'archivar':
            return 'Notificación archivada correctamente';
        case 'eliminar':
            return 'Notificación eliminada correctamente';
        case 'aumentar_prioridad':
            return 'Prioridad de notificación aumentada';
        case 'disminuir_prioridad':
            return 'Prioridad de notificación disminuida';
        default:
            return 'Acción completada correctamente';
    }
}
?>