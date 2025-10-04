<?php
/**
 * API para compartir campañas con la red de afiliados
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
    
    // Verificar que es afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        jsonResponse(['error' => 'Debe ser un afiliado para compartir campañas'], 403);
    }
    
    $afiliadoId = $afiliado['id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Método no permitido'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['campana_id']) || !isset($data['mensaje'])) {
        jsonResponse(['error' => 'ID de campaña y mensaje son requeridos'], 400);
    }
    
    $campanaId = $data['campana_id'];
    $mensaje = $data['mensaje'];
    
    // Verificar que la campaña pertenece al afiliado
    $stmt = $conn->prepare("SELECT * FROM campanas_afiliados WHERE id = ? AND afiliado_id = ?");
    $stmt->execute([$campanaId, $afiliadoId]);
    $campana = $stmt->fetch();
    
    if (!$campana) {
        jsonResponse(['error' => 'Campaña no encontrada'], 404);
    }
    
    // Obtener miembros de la red (afiliados referidos)
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.nombre, u.email 
        FROM usuarios u
        INNER JOIN afiliados a ON u.id = a.usuario_id
        WHERE a.referidor_id = ? AND u.estado = 'activo'
    ");
    $stmt->execute([$afiliadoId]);
    $miembrosRed = $stmt->fetchAll();
    
    if (empty($miembrosRed)) {
        jsonResponse(['error' => 'No tienes miembros en tu red para compartir'], 400);
    }
    
    // Crear notificaciones para cada miembro de la red
    $notificacionesCreadas = 0;
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, fecha_creacion)
        VALUES (?, 'campana_compartida', ?, ?, ?, NOW())
    ");
    
    $titulo = "Nueva campaña compartida: " . $campana['nombre'];
    $datosAdicionales = json_encode([
        'campana_id' => $campanaId,
        'afiliado_compartidor' => $user['nombre'],
        'enlace_campana' => $campana['enlace_personalizado']
    ]);
    
    foreach ($miembrosRed as $miembro) {
        $stmt->execute([
            $miembro['id'],
            $titulo,
            $mensaje,
            $datosAdicionales
        ]);
        $notificacionesCreadas++;
    }
    
    // Registrar la acción de compartir
    $stmt = $conn->prepare("
        INSERT INTO campana_compartidas (campana_id, afiliado_id, mensaje, miembros_notificados, fecha_compartido)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$campanaId, $afiliadoId, $mensaje, $notificacionesCreadas]);
    
    jsonResponse([
        'success' => true,
        'mensaje' => "Campaña compartida exitosamente con {$notificacionesCreadas} miembros de tu red",
        'miembros_notificados' => $notificacionesCreadas
    ]);
    
} catch (Exception $e) {
    error_log("Error compartiendo campaña: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?>