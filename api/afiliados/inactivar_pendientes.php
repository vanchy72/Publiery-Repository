<?php
/**
 * Script para inactivar automáticamente afiliados que no se activaron en 3 días
 * Puede ejecutarse manualmente o como cron job
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

try {
    $conn = getDBConnection();
    // Buscar afiliados pendientes sin fecha de activación y con más de 3 días desde el registro
    $stmt = $conn->prepare("SELECT u.id, u.nombre, u.email, u.fecha_registro FROM usuarios u JOIN afiliados a ON u.id = a.usuario_id WHERE u.rol = 'afiliado' AND u.estado = 'pendiente' AND (a.fecha_activacion IS NULL OR a.fecha_activacion = '') AND u.fecha_registro < (NOW() - INTERVAL 3 DAY)");
    $stmt->execute();
    $pendientes = $stmt->fetchAll();
    $inactivados = 0;
    foreach ($pendientes as $afiliado) {
        // Cambiar estado a inactivo
        $stmt2 = $conn->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id = ?");
        $stmt2->execute([$afiliado['id']]);
        logActivity($afiliado['id'], 'afiliado_inactivado', 'Afiliado inactivado automáticamente por no activarse en 3 días');
        $inactivados++;
        // (Opcional) Enviar notificación por correo
        // sendInactivationEmail($afiliado['email'], $afiliado['nombre']);
    }
    $msg = "Afiliados inactivados: $inactivados";
    echo json_encode(['success' => true, 'message' => $msg, 'detalles' => $pendientes]);
} catch (Exception $e) {
    error_log('Error en inactivación automática: ' . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}

// Función opcional para enviar correo (activar cuando SMTP esté configurado)
/*
function sendInactivationEmail($email, $nombre) {
    $asunto = 'Tu cuenta de afiliado ha sido inactivada';
    $mensaje = "Hola $nombre,\n\nNo completaste tu activación en el plazo de 3 días. Si deseas reactivar tu cuenta, por favor inicia sesión y sigue las instrucciones en la plataforma.\n\nSaludos,\nEquipo Publiery";
    // mail($email, $asunto, $mensaje); // Descomentar cuando SMTP esté listo
}
*/ 