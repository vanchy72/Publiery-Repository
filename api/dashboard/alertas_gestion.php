<?php
/**
 * API para Alertas de Gestión - Dashboard Administrativo
 * Alertas específicas y prácticas para la gestión diaria
 * 
 * Endpoints:
 * GET /api/dashboard/alertas_gestion.php
 * 
 * @autor Publiery
 * @fecha 2025-09-21
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuración de base de datos
require_once __DIR__ . '/../../config/database.php';

try {
    // Crear conexión a la base de datos
    $pdo = getDBConnection();
    
    $alertas = array();
    
    // 1. LIBROS PENDIENTES POR REVISAR (CRÍTICO)
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   MIN(DATEDIFF(NOW(), fecha_registro)) as dias_mas_antiguo
            FROM libros 
            WHERE estado IN ('pendiente', 'en_revision')
        ");
        $stmt->execute();
        $pendientes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pendientes['total'] > 0) {
            $nivel = $pendientes['dias_mas_antiguo'] > 3 ? 'critica' : 'alta';
            $alertas[] = array(
                'id' => 'libros_pendientes',
                'nivel' => $nivel,
                'icono' => 'fas fa-book-open',
                'titulo' => 'Libros pendientes por revisar',
                'cantidad' => $pendientes['total'],
                'descripcion' => "{$pendientes['total']} libro(s) esperando revisión",
                'detalle' => "El más antiguo: {$pendientes['dias_mas_antiguo']} días",
                'accion' => 'Revisar ahora',
                'enlace' => 'libros?filter=pendientes'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando libros pendientes: " . $e->getMessage());
    }
    
    // 2. TESTIMONIOS POR APROBAR
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   MIN(DATEDIFF(NOW(), fecha_creacion)) as dias_mas_antiguo
            FROM testimonios 
            WHERE estado = 'pendiente'
        ");
        $stmt->execute();
        $testimonios = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testimonios['total'] > 0) {
            $alertas[] = array(
                'id' => 'testimonios_pendientes',
                'nivel' => 'media',
                'icono' => 'fas fa-comments',
                'titulo' => 'Testimonios por aprobar',
                'cantidad' => $testimonios['total'],
                'descripcion' => "{$testimonios['total']} testimonio(s) esperando aprobación",
                'detalle' => "El más antiguo: {$testimonios['dias_mas_antiguo']} días",
                'accion' => 'Revisar testimonios',
                'enlace' => 'testimonios?filter=pendientes'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando testimonios pendientes: " . $e->getMessage());
    }
    
    // 3. CAMPAÑAS PRÓXIMAS A VENCER
    try {
        // Verificar si existe tabla de campañas
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'campanas'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total,
                       MIN(DATEDIFF(fecha_fin, NOW())) as dias_hasta_vencer
                FROM campanas 
                WHERE estado IN ('enviando', 'programada') 
                AND fecha_fin <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                AND fecha_fin > NOW()
            ");
            $stmt->execute();
            $campanas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($campanas['total'] > 0) {
                $nivel = $campanas['dias_hasta_vencer'] <= 2 ? 'alta' : 'media';
                $alertas[] = array(
                    'id' => 'campanas_vencen',
                    'nivel' => $nivel,
                    'icono' => 'fas fa-calendar-times',
                    'titulo' => 'Campañas próximas a vencer',
                    'cantidad' => $campanas['total'],
                    'descripcion' => "{$campanas['total']} campaña(s) vencen pronto",
                    'detalle' => "La próxima en {$campanas['dias_hasta_vencer']} días",
                    'accion' => 'Gestionar campañas',
                    'enlace' => 'campanas?filter=vencen_pronto'
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error consultando campañas: " . $e->getMessage());
    }
    
    // 4. USUARIOS SIN ACTIVAR CUENTA (RECIENTES)
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM usuarios 
            WHERE estado = 'inactivo' 
            AND DATEDIFF(NOW(), fecha_registro) <= 7
            AND email_verificado = 0
        ");
        $stmt->execute();
        $sin_activar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sin_activar['total'] > 0) {
            $alertas[] = array(
                'id' => 'usuarios_sin_activar',
                'nivel' => 'baja',
                'icono' => 'fas fa-user-clock',
                'titulo' => 'Usuarios sin activar cuenta',
                'cantidad' => $sin_activar['total'],
                'descripcion' => "{$sin_activar['total']} usuario(s) reciente(s) sin activar",
                'detalle' => "Registrados en los últimos 7 días",
                'accion' => 'Enviar recordatorio',
                'enlace' => 'usuarios?filter=sin_activar'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando usuarios sin activar: " . $e->getMessage());
    }
    
    // 5. PAGOS PENDIENTES DE PROCESAR
    try {
        // Simular pagos pendientes basados en ventas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as ventas_pendientes,
                   COALESCE(SUM(precio), 0) as monto_total
            FROM ventas 
            WHERE estado = 'completada'
            AND DATEDIFF(NOW(), fecha_venta) > 30
        ");
        $stmt->execute();
        $pagos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pagos['ventas_pendientes'] > 0) {
            $alertas[] = array(
                'id' => 'pagos_pendientes',
                'nivel' => 'alta',
                'icono' => 'fas fa-dollar-sign',
                'titulo' => 'Pagos de royalties pendientes',
                'cantidad' => $pagos['ventas_pendientes'],
                'descripcion' => "{$pagos['ventas_pendientes']} pago(s) pendiente(s)",
                'detalle' => "Total: $" . number_format($pagos['monto_total'], 0, '.', ','),
                'accion' => 'Procesar pagos',
                'enlace' => 'pagos?filter=pendientes'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando pagos pendientes: " . $e->getMessage());
    }
    
    // 6. ERRORES EN EL SISTEMA (LOGS RECIENTES)
    try {
        // Simular verificación de logs de errores
        $log_path = __DIR__ . '/../../logs/error.log';
        if (file_exists($log_path)) {
            $log_content = file_get_contents($log_path);
            $errores_recientes = substr_count($log_content, date('Y-m-d'));
            
            if ($errores_recientes > 10) {
                $alertas[] = array(
                    'id' => 'errores_sistema',
                    'nivel' => 'critica',
                    'icono' => 'fas fa-bug',
                    'titulo' => 'Errores detectados en el sistema',
                    'cantidad' => $errores_recientes,
                    'descripcion' => "{$errores_recientes} errores hoy",
                    'detalle' => "Revisar logs de errores",
                    'accion' => 'Ver logs',
                    'enlace' => 'system/logs'
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error verificando logs: " . $e->getMessage());
    }
    
    // 7. VENTAS SIN CONFIRMAR
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM ventas 
            WHERE estado = 'pendiente'
            AND DATEDIFF(NOW(), fecha_venta) > 1
        ");
        $stmt->execute();
        $ventas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ventas_pendientes['total'] > 0) {
            $alertas[] = array(
                'id' => 'ventas_sin_confirmar',
                'nivel' => 'media',
                'icono' => 'fas fa-shopping-cart',
                'titulo' => 'Ventas sin confirmar',
                'cantidad' => $ventas_pendientes['total'],
                'descripcion' => "{$ventas_pendientes['total']} venta(s) pendiente(s)",
                'detalle' => "Requieren confirmación manual",
                'accion' => 'Revisar ventas',
                'enlace' => 'ventas?filter=pendientes'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando ventas pendientes: " . $e->getMessage());
    }
    
    // Ordenar alertas por prioridad
    $orden_prioridad = ['critica' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    usort($alertas, function($a, $b) use ($orden_prioridad) {
        return $orden_prioridad[$b['nivel']] - $orden_prioridad[$a['nivel']];
    });
    
    // Calcular resumen
    $resumen = array(
        'total' => count($alertas),
        'criticas' => 0,
        'altas' => 0,
        'medias' => 0,
        'bajas' => 0
    );
    
    foreach ($alertas as $alerta) {
        $resumen[$alerta['nivel'] . 's']++;
    }
    
    // Si no hay alertas críticas, agregar mensaje positivo
    if ($resumen['criticas'] == 0 && $resumen['altas'] == 0) {
        array_unshift($alertas, array(
            'id' => 'todo_ok',
            'nivel' => 'success',
            'icono' => 'fas fa-check-circle',
            'titulo' => 'Todo en orden',
            'cantidad' => 0,
            'descripcion' => 'No hay tareas urgentes pendientes',
            'detalle' => 'El sistema funciona correctamente',
            'accion' => '',
            'enlace' => ''
        ));
        $resumen['total']++;
    }
    
    $response = array(
        'success' => true,
        'alertas' => $alertas,
        'resumen' => $resumen,
        'fecha_actualizacion' => date('Y-m-d H:i:s'),
        'mensaje' => 'Alertas de gestión cargadas correctamente'
    );
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de conexión BD en alertas de gestión: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'mensaje' => 'No se pudieron cargar las alertas de gestión'
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error general en alertas de gestión: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error interno del servidor',
        'mensaje' => 'No se pudieron cargar las alertas de gestión'
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>