<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación de admin (permitir localhost para testing)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isAuthenticated = isset($_SESSION['email']) && $_SESSION['rol'] === 'admin';

if (!$isAuthenticated && !$isLocalhost) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

$libroId = $input['libro_id'] ?? '';
$nuevoEstado = $input['nuevo_estado'] ?? '';
$comentarios = $input['comentarios'] ?? '';
$notificarAutor = $input['notificar_autor'] ?? false;

if (empty($libroId) || empty($nuevoEstado)) {
    echo json_encode(['success' => false, 'error' => 'ID de libro y nuevo estado requeridos']);
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Obtener estado actual del libro
    $stmt = $pdo->prepare("SELECT * FROM libros WHERE id = ?");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        throw new Exception('Libro no encontrado');
    }
    
    $estadoAnterior = $libro['estado'];
    
    // Validar transición de estado
    $transicionesValidas = [
        'pendiente_revision' => ['en_revision', 'rechazado'],
        'en_revision' => ['aprobado_autor', 'correccion_autor', 'rechazado'],
        'correccion_autor' => ['aprobado_autor', 'rechazado', 'en_revision'],
        'aprobado_autor' => ['publicado', 'rechazado'],
        'publicado' => ['aprobado_autor'], // Para despublicar
        'rechazado' => ['pendiente_revision', 'en_revision'] // Para revisar nuevamente
    ];
    
    if (!isset($transicionesValidas[$estadoAnterior]) || 
        !in_array($nuevoEstado, $transicionesValidas[$estadoAnterior])) {
        throw new Exception("Transición de estado no válida: $estadoAnterior -> $nuevoEstado");
    }
    
    // Actualizar libro
    $updateFields = ['estado = ?', 'comentarios_editorial = ?'];
    $updateParams = [$nuevoEstado, $comentarios];
    
    // Actualizar fechas según el nuevo estado
    switch ($nuevoEstado) {
        case 'en_revision':
            $updateFields[] = 'fecha_revision = NOW()';
            break;
        case 'aprobado_autor':
            $updateFields[] = 'fecha_aprobacion_autor = NOW()';
            break;
        case 'publicado':
            $updateFields[] = 'fecha_publicacion = NOW()';
            break;
    }
    
    $updateSql = "UPDATE libros SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateParams[] = $libroId;
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);
    
    // Registrar cambio en el historial (si existe la tabla)
    try {
        $historialSql = "
            INSERT INTO system_logs (
                libro_id, 
                estado_anterior, 
                estado_nuevo, 
                comentario, 
                admin_id, 
                fecha_cambio
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ";
        
        $adminId = $_SESSION['user_id'] ?? 1;
        $historialStmt = $pdo->prepare($historialSql);
        $historialStmt->execute([
            $libroId,
            $estadoAnterior,
            $nuevoEstado,
            $comentarios,
            $adminId
        ]);
    } catch (Exception $e) {
        // Si no existe la tabla system_logs, continuar sin registrar
        error_log("No se pudo registrar en system_logs: " . $e->getMessage());
    }
    
    // Enviar notificación al autor si se solicita
    if ($notificarAutor) {
        $mensajeNotificacion = generarMensajeNotificacion($nuevoEstado, $libro['titulo'], $comentarios);
        enviarNotificacionAutor($libro['autor_id'], $mensajeNotificacion, $pdo);
    }
    
    // Si el libro se publica, generar ISBN si no lo tiene
    if ($nuevoEstado === 'publicado' && empty($libro['isbn'])) {
        $isbn = generarISBN($libroId);
        $pdo->prepare("UPDATE libros SET isbn = ? WHERE id = ?")->execute([$isbn, $libroId]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Estado del libro cambiado exitosamente a: $nuevoEstado",
        'estado_anterior' => $estadoAnterior,
        'estado_nuevo' => $nuevoEstado
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function generarMensajeNotificacion($estado, $titulo, $comentarios) {
    $mensajes = [
        'en_revision' => "Tu libro '$titulo' está ahora en revisión editorial.",
        'aprobado_autor' => "¡Excelente! Tu libro '$titulo' ha sido aprobado por el equipo editorial.",
        'correccion_autor' => "Tu libro '$titulo' necesita algunas correcciones antes de ser publicado.",
        'publicado' => "¡Felicitaciones! Tu libro '$titulo' ha sido publicado y está disponible para la venta.",
        'rechazado' => "Lamentamos informarte que tu libro '$titulo' no ha sido aprobado para publicación."
    ];
    
    $mensaje = $mensajes[$estado] ?? "El estado de tu libro '$titulo' ha cambiado.";
    
    if (!empty($comentarios)) {
        $mensaje .= "\n\nComentarios del editor: $comentarios";
    }
    
    return $mensaje;
}

function enviarNotificacionAutor($autorId, $mensaje, $pdo) {
    try {
        // Insertar notificación en la tabla notificaciones
        $sql = "
            INSERT INTO notificaciones (
                usuario_id, 
                tipo, 
                titulo, 
                mensaje, 
                fecha_creacion
            ) VALUES (?, ?, ?, ?, NOW())
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $autorId,
            'revision_editorial',
            'Actualización sobre tu libro',
            $mensaje
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error enviando notificación: " . $e->getMessage());
        return false;
    }
}

function generarISBN($libroId) {
    // Generar ISBN-13 simple (formato: 978-XXXXXXX-XX-X)
    // En un entorno real, esto debería conectarse con una autoridad ISBN
    $prefix = '978';
    $publisherCode = '84'; // Código para España, usar el apropiado
    $bookNumber = str_pad($libroId, 5, '0', STR_PAD_LEFT);
    $checkDigit = rand(0, 9); // En un entorno real, calcular el dígito de control
    
    return "$prefix-$publisherCode-$bookNumber-$checkDigit";
}
?>
