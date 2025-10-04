<?php
/**
 * API: admin_panel_gestionar.php (testimonios)
 * Propósito: CRUD operations para testimonios en panel de administración
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
                // Obtener testimonio específico
                $stmt = $db->prepare("SELECT * FROM testimonios WHERE id = ?");
                $stmt->execute([$id]);
                $testimonio = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($testimonio) {
                    echo json_encode([
                        'success' => true,
                        'testimonio' => $testimonio,
                        'message' => 'Testimonio obtenido correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Testimonio no encontrado'
                    ]);
                }
            }
            break;
            
        case 'PUT':
            // Actualizar estado de testimonio
            if ($id && isset($input['accion'])) {
                $nuevo_estado = '';
                $admin_id = 1; // ID del admin que hace la acción
                
                if ($input['accion'] === 'aprobar') {
                    $nuevo_estado = 'aprobado';
                } elseif ($input['accion'] === 'rechazar') {
                    $nuevo_estado = 'rechazado';
                } elseif ($input['accion'] === 'destacar') {
                    // Toggle destacado
                    $stmt = $db->prepare("UPDATE testimonios SET es_destacado = NOT es_destacado WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Estado destacado actualizado'
                        ]);
                        exit;
                    }
                }
                
                if ($nuevo_estado) {
                    $stmt = $db->prepare("
                        UPDATE testimonios 
                        SET estado = ?, fecha_revision = NOW(), admin_revisor_id = ? 
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([$nuevo_estado, $admin_id, $id]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Testimonio {$nuevo_estado} correctamente"
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Error al actualizar estado'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Acción no válida'
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
            // Eliminar testimonio (soft delete - rechazar)
            if ($id) {
                $stmt = $db->prepare("
                    UPDATE testimonios 
                    SET estado = 'rechazado', fecha_revision = NOW(), admin_revisor_id = 1 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Testimonio rechazado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Error al rechazar testimonio'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de testimonio requerido'
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