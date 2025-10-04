<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_user.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Solo escritores y admins pueden eliminar libros
if ($_SESSION['user_rol'] !== 'escritor' && $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $libro_id = (int)($input['libro_id'] ?? 0);
    
    if (!$libro_id) {
        throw new Exception('ID del libro es requerido');
    }
    
    // Verificar que el libro existe y obtener información
    $stmt = $pdo->prepare("
        SELECT l.*, u.nombre as autor_nombre 
        FROM libros l 
        LEFT JOIN usuarios u ON l.autor_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$libro_id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        throw new Exception('Libro no encontrado');
    }
    
    // Verificar permisos: escritores solo pueden eliminar sus propios libros
    if ($_SESSION['user_rol'] === 'escritor' && $libro['autor_id'] != $_SESSION['user_id']) {
        throw new Exception('No tienes permisos para eliminar este libro');
    }
    
    // No permitir eliminar libros con ventas (opcional - puedes comentar esto si quieres permitirlo)
    $stmt = $pdo->prepare("SELECT COUNT(*) as ventas FROM ventas WHERE libro_id = ?");
    $stmt->execute([$libro_id]);
    $ventas_count = $stmt->fetch()['ventas'];
    
    if ($ventas_count > 0 && $_SESSION['user_rol'] !== 'admin') {
        throw new Exception('No se puede eliminar un libro que tiene ventas registradas. Contacte al administrador.');
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Eliminar registros relacionados (en orden de dependencias)
        
        // 1. Eliminar de ventas
        $stmt = $pdo->prepare("DELETE FROM ventas WHERE libro_id = ?");
        $stmt->execute([$libro_id]);
        $ventas_eliminadas = $stmt->rowCount();
        
        // 2. Eliminar de campañas_libros si existe esa tabla
        try {
            // Verificar si la tabla existe
            $stmt_check = $pdo->query("SHOW TABLES LIKE 'campanas_libros'");
            if ($stmt_check->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM campanas_libros WHERE libro_id = ?");
                $stmt->execute([$libro_id]);
                $campanas_eliminadas = $stmt->rowCount();
            } else {
                $campanas_eliminadas = 0; // Tabla no existe, no hay nada que eliminar
            }
        } catch (Exception $e) {
            // Si hay error con campañas_libros, logeamos pero continuamos
            error_log("Advertencia eliminando de campañas_libros: " . $e->getMessage());
            $campanas_eliminadas = 0;
        }
        
        // 3. Eliminar archivos físicos antes de eliminar el registro
        $archivos_eliminados = [];
        
        // Eliminar archivo PDF
        if (!empty($libro['archivo_original'])) {
            $pdf_path = __DIR__ . '/../../uploads/libros/' . $libro['archivo_original'];
            if (file_exists($pdf_path)) {
                if (unlink($pdf_path)) {
                    $archivos_eliminados[] = 'PDF: ' . $libro['archivo_original'];
                }
            }
        }
        
        // Eliminar archivo editado si existe
        if (!empty($libro['archivo_editado'])) {
            $editado_path = __DIR__ . '/../../uploads/libros/' . $libro['archivo_editado'];
            if (file_exists($editado_path)) {
                if (unlink($editado_path)) {
                    $archivos_eliminados[] = 'PDF Editado: ' . $libro['archivo_editado'];
                }
            }
        }
        
        // Eliminar portada
        if (!empty($libro['imagen_portada'])) {
            $portada_path = __DIR__ . '/../../uploads/portadas/' . $libro['imagen_portada'];
            if (file_exists($portada_path)) {
                if (unlink($portada_path)) {
                    $archivos_eliminados[] = 'Portada: ' . $libro['imagen_portada'];
                }
            }
        }
        
        // 4. Finalmente, eliminar el libro
        $stmt = $pdo->prepare("DELETE FROM libros WHERE id = ?");
        $stmt->execute([$libro_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo eliminar el libro de la base de datos');
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        // Log de la acción (opcional)
        error_log("Libro eliminado - ID: $libro_id, Título: {$libro['titulo']}, Usuario: {$_SESSION['user_id']}");
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => "Libro '{$libro['titulo']}' eliminado exitosamente",
            'detalles' => [
                'libro_id' => $libro_id,
                'titulo' => $libro['titulo'],
                'ventas_eliminadas' => $ventas_eliminadas,
                'campanas_eliminadas' => $campanas_eliminadas ?? 0,
                'archivos_eliminados' => $archivos_eliminados
            ]
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>