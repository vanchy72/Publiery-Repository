<?php
/**
 * API: admin_panel_gestionar.php (usuarios)
 * Propósito: CRUD operations para usuarios en panel de administración
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
                // Obtener usuario específico
                $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    echo json_encode([
                        'success' => true,
                        'usuario' => $usuario,
                        'message' => 'Usuario obtenido correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Usuario no encontrado'
                    ]);
                }
            }
            break;
            
        case 'PUT':
            // Actualizar datos del usuario
            if ($id) {
                // Contar cuántos campos se están enviando (excluyendo id)
                $input_fields = array_filter($input, function($value, $key) {
                    return $key !== 'id' && $value !== null && $value !== '';
                }, ARRAY_FILTER_USE_BOTH);
                
                // Si solo se envía 'estado' es un toggle de estado simple
                if (count($input_fields) === 1 && isset($input['estado']) && !isset($input['nombre'])) {
                    // Solo actualizar estado (compatibilidad con función existente)
                    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
                    $result = $stmt->execute([$input['estado'], $id]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Estado de usuario actualizado correctamente'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Error al actualizar estado'
                        ]);
                    }
                } else {
                    // Actualizar datos completos del usuario
                    $updates = [];
                    $params = [];
                    
                    // Campos permitidos para actualizar
                    $allowed_fields = ['nombre', 'email', 'documento', 'rol', 'estado'];
                    
                    foreach ($allowed_fields as $field) {
                        if (isset($input[$field]) && $input[$field] !== '') {
                            $updates[] = "$field = ?";
                            $params[] = trim($input[$field]);
                        }
                    }
                    
                    // Manejar contraseña por separado
                    if (isset($input['password']) && !empty(trim($input['password']))) {
                        $updates[] = "password = ?";
                        $params[] = password_hash(trim($input['password']), PASSWORD_DEFAULT);
                    }
                    
                    if (!empty($updates)) {
                        // Verificar email único si se está actualizando
                        if (isset($input['email'])) {
                            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                            $stmt->execute([$input['email'], $id]);
                            if ($stmt->fetch()) {
                                echo json_encode([
                                    'success' => false,
                                    'error' => 'El email ya está registrado por otro usuario'
                                ]);
                                break;
                            }
                        }
                        
                        // Verificar documento único si se está actualizando
                        if (isset($input['documento'])) {
                            $stmt = $db->prepare("SELECT id FROM usuarios WHERE documento = ? AND id != ?");
                            $stmt->execute([$input['documento'], $id]);
                            if ($stmt->fetch()) {
                                echo json_encode([
                                    'success' => false,
                                    'error' => 'El documento ya está registrado por otro usuario'
                                ]);
                                break;
                            }
                        }
                        
                        $params[] = $id;
                        $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $result = $stmt->execute($params);
                        
                        if ($result) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Usuario actualizado correctamente'
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'error' => 'Error al actualizar usuario'
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'error' => 'No se proporcionaron datos para actualizar'
                        ]);
                    }
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de usuario requerido'
                ]);
            }
            break;
            
        case 'DELETE':
            // Eliminar usuario permanentemente (con cascada)
            if ($id) {
                try {
                    // Iniciar transacción para asegurar integridad
                    $db->beginTransaction();
                    
                    // 1. Eliminar registros de afiliados
                    $stmt = $db->prepare("DELETE FROM afiliados WHERE usuario_id = ?");
                    $stmt->execute([$id]);
                    
                    // 2. Eliminar registros de escritores
                    $stmt = $db->prepare("DELETE FROM escritores WHERE usuario_id = ?");
                    $stmt->execute([$id]);
                    
                    // 3. Eliminar libros del autor (si es escritor)
                    $stmt = $db->prepare("DELETE FROM libros WHERE autor_id = ?");
                    $stmt->execute([$id]);
                    
                    // 4. Eliminar notificaciones del usuario
                    $stmt = $db->prepare("DELETE FROM notificaciones WHERE usuario_id = ?");
                    $stmt->execute([$id]);
                    
                    // 5. Finalmente eliminar el usuario
                    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        // Confirmar transacción
                        $db->commit();
                        echo json_encode([
                            'success' => true,
                            'message' => 'Usuario eliminado permanentemente junto con todos sus datos relacionados'
                        ]);
                    } else {
                        // Revertir transacción
                        $db->rollBack();
                        echo json_encode([
                            'success' => false,
                            'error' => 'Usuario no encontrado o ya fue eliminado'
                        ]);
                    }
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $db->rollBack();
                    throw $e;
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de usuario requerido'
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