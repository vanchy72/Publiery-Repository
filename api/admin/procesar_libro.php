<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $libro_id = (int)($input['libro_id'] ?? 0);
    $accion = $input['accion'] ?? '';
    $comentarios = $input['comentarios'] ?? '';
    
    if (!$libro_id || !$accion) {
        throw new Exception('ID del libro y acción son requeridos');
    }
    
    // Verificar que el libro existe
    $stmt = $pdo->prepare("SELECT * FROM libros WHERE id = ?");
    $stmt->execute([$libro_id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        throw new Exception('Libro no encontrado');
    }
    
    switch ($accion) {
        case 'aprobar':
            // Cambiar estado a publicado
            $stmt = $pdo->prepare("
                UPDATE libros 
                SET estado = 'publicado', 
                    fecha_publicacion = NOW(),
                    comentarios_editorial = ?
                WHERE id = ?
            ");
            $stmt->execute([$comentarios, $libro_id]);
            
            $mensaje = "Libro #{$libro_id} aprobado y publicado exitosamente";
            break;
            
        case 'rechazar':
            // Cambiar estado a rechazado
            $stmt = $pdo->prepare("
                UPDATE libros 
                SET estado = 'rechazado', 
                    fecha_revision = NOW(),
                    comentarios_editorial = ?
                WHERE id = ?
            ");
            $stmt->execute([$comentarios, $libro_id]);
            
            $mensaje = "Libro #{$libro_id} rechazado";
            break;
            
        case 'revision':
            // Cambiar estado a en revisión
            $stmt = $pdo->prepare("
                UPDATE libros 
                SET estado = 'en_revision', 
                    fecha_revision = NOW(),
                    comentarios_editorial = ?
                WHERE id = ?
            ");
            $stmt->execute([$comentarios, $libro_id]);
            
            $mensaje = "Libro #{$libro_id} marcado como en revisión";
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    // Obtener datos actualizados del libro
    $stmt = $pdo->prepare("
        SELECT l.*, u.nombre as autor_nombre, u.email as autor_email 
        FROM libros l 
        LEFT JOIN usuarios u ON l.autor_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$libro_id]);
    $libro_actualizado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'libro' => $libro_actualizado,
        'nueva_estado' => $libro_actualizado['estado']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>