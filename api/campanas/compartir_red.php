<?php
/**
 * API para compartir campañas del admin con toda la red de afiliados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$userId = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }
    
    // Verificar que es administrador
    if ($user['rol'] !== 'admin') {
        jsonResponse(['error' => 'Solo los administradores pueden compartir campañas con la red'], 403);
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Método no permitido'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['campana_id'])) {
        jsonResponse(['error' => 'ID de campaña requerido'], 400);
    }
    
    $campanaId = $data['campana_id'];
    
    // Verificar que la campaña existe
    $stmt = $conn->prepare("SELECT * FROM campanas WHERE id = ?");
    $stmt->execute([$campanaId]);
    $campana = $stmt->fetch();
    
    if (!$campana) {
        jsonResponse(['error' => 'Campaña no encontrada'], 404);
    }
    
    // Obtener todos los afiliados activos
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.nombre, u.email, a.id as afiliado_id 
        FROM usuarios u
        INNER JOIN afiliados a ON u.id = a.usuario_id
        WHERE u.estado = 'activo' AND u.rol = 'afiliado'
    ");
    $stmt->execute();
    $afiliados = $stmt->fetchAll();
    
    if (empty($afiliados)) {
        jsonResponse(['error' => 'No hay afiliados activos para compartir'], 400);
    }
    
    // Crear notificaciones para cada afiliado
    $notificacionesCreadas = 0;
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, fecha_creacion)
        VALUES (?, 'campana_admin', ?, ?, ?, NOW())
    ");
    
    $titulo = "Nueva campaña del administrador: " . $campana['nombre'];
    $mensaje = "El administrador ha compartido una nueva campaña promocional. ¡Revisa tu dashboard para más detalles!";
    
    foreach ($afiliados as $afiliado) {
        $datosAdicionales = json_encode([
            'campana_id' => $campanaId,
            'afiliado_id' => $afiliado['afiliado_id'],
            'nombre_campana' => $campana['nombre'],
            'tipo_campana' => $campana['tipo'] ?? 'promocion'
        ]);
        
        $stmt->execute([
            $afiliado['id'],
            $titulo,
            $mensaje,
            $datosAdicionales
        ]);
        $notificacionesCreadas++;
    }
    
    // Registrar que la campaña fue compartida
    $stmt = $conn->prepare("
        UPDATE campanas 
        SET compartida_red = 1, fecha_compartida = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$campanaId]);
    
    // Registrar en historial de compartidas (si existe la tabla)
    try {
        $stmt = $conn->prepare("
            INSERT INTO campanas_compartidas_admin (campana_id, admin_id, afiliados_notificados, fecha_compartido)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$campanaId, $userId, $notificacionesCreadas]);
    } catch (Exception $e) {
        // Si la tabla no existe, no es crítico, continuar
        error_log("Tabla campanas_compartidas_admin no existe: " . $e->getMessage());
    }
    
    jsonResponse([
        'success' => true,
        'mensaje' => "Campaña compartida exitosamente con {$notificacionesCreadas} afiliados",
        'afiliados_notificados' => $notificacionesCreadas,
        'campana' => $campana['nombre']
    ]);
    
} catch (Exception $e) {
    error_log("Error compartiendo campaña admin: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?>