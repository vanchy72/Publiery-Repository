<?php
/**
 * Script para enviar recordatorios de activación a afiliados pendientes
 * Se puede ejecutar manualmente o como cron job
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once '../../config/email.php';

try {
    $conn = getDBConnection();
    
    // Buscar afiliados pendientes con más de 1 día desde el registro
    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.email, u.fecha_registro, a.codigo_afiliado, a.nivel
        FROM usuarios u 
        JOIN afiliados a ON u.id = a.usuario_id 
        WHERE u.rol = 'afiliado' 
        AND u.estado = 'pendiente' 
        AND a.fecha_activacion IS NULL 
        AND u.fecha_registro < (NOW() - INTERVAL 1 DAY)
        AND u.fecha_registro > (NOW() - INTERVAL 7 DAY) -- Solo afiliados de los últimos 7 días
    ");
    $stmt->execute();
    $pendientes = $stmt->fetchAll();
    
    $enviados = 0;
    $errores = 0;
    
    foreach ($pendientes as $afiliado) {
        try {
            $userDataForEmail = [
                'nombre' => $afiliado['nombre'],
                'email' => $afiliado['email'],
                'codigo_afiliado' => $afiliado['codigo_afiliado'],
                'nivel' => $afiliado['nivel']
            ];
            
            $result = sendActivationReminderEmail($userDataForEmail);
            
            if ($result) {
                $enviados++;
                logActivity($afiliado['id'], 'reminder_email_sent', 'Recordatorio de activación enviado');
            } else {
                $errores++;
                error_log("Error enviando recordatorio a: " . $afiliado['email']);
            }
            
        } catch (Exception $e) {
            $errores++;
            error_log("Error procesando recordatorio para {$afiliado['email']}: " . $e->getMessage());
        }
    }
    
    $response = [
        'success' => true,
        'message' => "Recordatorios enviados: $enviados, Errores: $errores",
        'detalles' => [
            'total_pendientes' => count($pendientes),
            'enviados' => $enviados,
            'errores' => $errores
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Error en envío de recordatorios: ' . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}
?> 