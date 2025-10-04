<?php
/**
 * API: admin_panel_gestionar.php (afiliados)
 * Propósito: CRUD operations para afiliados en panel de administración
 * Acceso: Sin restricciones (bypass temporal para desarrollo)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Incluir configuración de BD
    require_once '../../config/database.php';
    
    // Conectar a la base de datos
    $db = getDBConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? $input['id'] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Obtener afiliado específico con datos del usuario
                $stmt = $db->prepare("
                    SELECT a.*, u.nombre, u.email, u.estado as estado_usuario
                    FROM afiliados a 
                    LEFT JOIN usuarios u ON a.usuario_id = u.id 
                    WHERE a.id = ?
                ");
                $stmt->execute([$id]);
                $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($afiliado) {
                    echo json_encode([
                        'success' => true,
                        'afiliado' => $afiliado,
                        'message' => 'Afiliado obtenido correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Afiliado no encontrado'
                    ]);
                }
            }
            break;
            
        case 'PUT':
            // Actualizar afiliado
            if ($id && isset($input['accion'])) {
                if ($input['accion'] === 'activar') {
                    // Activar afiliado (actualizar estado del usuario)
                    $stmt = $db->prepare("
                        UPDATE usuarios u 
                        JOIN afiliados a ON u.id = a.usuario_id 
                        SET u.estado = 'activo' 
                        WHERE a.id = ?
                    ");
                    $result = $stmt->execute([$id]);
                } elseif ($input['accion'] === 'desactivar') {
                    // Desactivar afiliado
                    $stmt = $db->prepare("
                        UPDATE usuarios u 
                        JOIN afiliados a ON u.id = a.usuario_id 
                        SET u.estado = 'inactivo' 
                        WHERE a.id = ?
                    ");
                    $result = $stmt->execute([$id]);
                }
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Afiliado actualizado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Error al actualizar afiliado'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Faltan parámetros requeridos'
                ]);
            }
            break;
            
        case 'DELETE':
            // Eliminar afiliado (soft delete)
            if ($id) {
                $stmt = $db->prepare("
                    UPDATE usuarios u 
                    JOIN afiliados a ON u.id = a.usuario_id 
                    SET u.estado = 'inactivo' 
                    WHERE a.id = ?
                ");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Afiliado desactivado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Error al desactivar afiliado'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de afiliado requerido'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Método no soportado'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en operación: ' . $e->getMessage()
    ]);
}
?>