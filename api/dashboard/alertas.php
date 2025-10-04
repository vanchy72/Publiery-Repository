<?php
/**
 * API para Alertas del Sistema en el Dashboard
 * Detecta situaciones que requieren atención del administrador
 * 
 * Endpoints:
 * GET /api/dashboard/alertas.php
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
    
    // 1. LIBROS PENDIENTES DE REVISIÓN POR MÁS DE 7 DÍAS
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   MIN(DATEDIFF(NOW(), fecha_registro)) as dias_mas_antiguo
            FROM libros 
            WHERE estado IN ('pendiente', 'en_revision') 
            AND DATEDIFF(NOW(), fecha_registro) > 7
        ");
        $stmt->execute();
        $pendientes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pendientes['total'] > 0) {
            $alertas[] = array(
                'tipo' => 'libros_pendientes',
                'nivel' => 'warning',
                'icono' => 'fas fa-clock',
                'color' => 'text-warning',
                'titulo' => 'Libros pendientes de revisión',
                'descripcion' => "{$pendientes['total']} libro" . ($pendientes['total'] > 1 ? 's' : '') . " pendiente" . ($pendientes['total'] > 1 ? 's' : '') . " por más de 7 días",
                'detalle' => "El más antiguo lleva {$pendientes['dias_mas_antiguo']} días esperando",
                'accion' => 'Ir a Revisión',
                'enlace' => '#revision',
                'prioridad' => 'alta'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando libros pendientes: " . $e->getMessage());
    }
    
    // 2. PAGOS DE ROYALTIES PENDIENTES
    try {
        // Verificar si existe tabla de pagos
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'pagos'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total
                FROM pagos 
                WHERE estado = 'pendiente' AND tipo_pago = 'royalty'
            ");
            $stmt->execute();
            $pagos_pendientes = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pagos_pendientes['total'] > 0) {
                $alertas[] = array(
                    'tipo' => 'pagos_pendientes',
                    'nivel' => 'danger',
                    'icono' => 'fas fa-money-bill-wave',
                    'color' => 'text-danger',
                    'titulo' => 'Pagos de royalties pendientes',
                    'descripcion' => "{$pagos_pendientes['total']} pago" . ($pagos_pendientes['total'] > 1 ? 's' : '') . " pendiente" . ($pagos_pendientes['total'] > 1 ? 's' : '') . " de procesar",
                    'detalle' => "Total: \$" . number_format($pagos_pendientes['monto_total'], 0, '.', ','),
                    'accion' => 'Procesar Pagos',
                    'enlace' => '#pagos',
                    'prioridad' => 'critica'
                );
            }
        } else {
            // Simular alerta para sistema sin tabla de pagos
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as ventas_sin_pago
                FROM ventas v
                WHERE NOT EXISTS (
                    SELECT 1 FROM pagos p 
                    WHERE p.referencia_venta = v.id AND p.tipo_pago = 'royalty'
                )
            ");
            $stmt->execute();
            $ventas_sin_pago = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ventas_sin_pago['ventas_sin_pago'] > 0) {
                $alertas[] = array(
                    'tipo' => 'sistema_pagos',
                    'nivel' => 'info',
                    'icono' => 'fas fa-cogs',
                    'color' => 'text-info',
                    'titulo' => 'Sistema de pagos',
                    'descripcion' => 'Configure el sistema de pagos automáticos',
                    'detalle' => 'Para procesar royalties y comisiones',
                    'accion' => 'Configurar',
                    'enlace' => '#configuracion',
                    'prioridad' => 'media'
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error consultando pagos pendientes: " . $e->getMessage());
    }
    
    // 3. TICKETS DE SOPORTE SIN RESPONDER
    try {
        // Verificar si existe tabla de soporte
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'tickets_soporte'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total,
                       MIN(DATEDIFF(NOW(), fecha_creacion)) as dias_mas_antiguo
                FROM tickets_soporte 
                WHERE estado = 'abierto' 
                AND DATEDIFF(NOW(), fecha_creacion) > 1
            ");
            $stmt->execute();
            $tickets = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tickets['total'] > 0) {
                $alertas[] = array(
                    'tipo' => 'tickets_pendientes',
                    'nivel' => 'warning',
                    'icono' => 'fas fa-life-ring',
                    'color' => 'text-warning',
                    'titulo' => 'Tickets de soporte pendientes',
                    'descripcion' => "{$tickets['total']} ticket" . ($tickets['total'] > 1 ? 's' : '') . " sin responder",
                    'detalle' => "El más antiguo tiene {$tickets['dias_mas_antiguo']} días",
                    'accion' => 'Ver Soporte',
                    'enlace' => '#soporte',
                    'prioridad' => 'alta'
                );
            }
        } else {
            // Sistema sin soporte configurado
            $alertas[] = array(
                'tipo' => 'sistema_soporte',
                'nivel' => 'info',
                'icono' => 'fas fa-headset',
                'color' => 'text-info',
                'titulo' => 'Sistema de soporte',
                'descripcion' => 'Configure el sistema de tickets de soporte',
                'detalle' => 'Para gestionar consultas de usuarios',
                'accion' => 'Configurar',
                'enlace' => '#configuracion',
                'prioridad' => 'baja'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando tickets de soporte: " . $e->getMessage());
    }
    
    // 4. USUARIOS INACTIVOS POR MUCHO TIEMPO
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM usuarios 
            WHERE estado = 'inactivo' 
            AND DATEDIFF(NOW(), fecha_registro) > 30
            AND rol != 'admin'
        ");
        $stmt->execute();
        $inactivos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inactivos['total'] > 5) { // Solo alertar si hay muchos inactivos
            $alertas[] = array(
                'tipo' => 'usuarios_inactivos',
                'nivel' => 'info',
                'icono' => 'fas fa-user-times',
                'color' => 'text-muted',
                'titulo' => 'Usuarios inactivos',
                'descripcion' => "{$inactivos['total']} usuarios inactivos por más de 30 días",
                'detalle' => 'Considere contactarlos o limpiar la base de datos',
                'accion' => 'Ver Usuarios',
                'enlace' => '#usuarios',
                'prioridad' => 'baja'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando usuarios inactivos: " . $e->getMessage());
    }
    
    // 5. LIBROS SIN VENTAS (para motivar promoción)
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM libros l
            LEFT JOIN ventas v ON l.id = v.libro_id
            WHERE l.estado = 'publicado' 
            AND v.id IS NULL
            AND DATEDIFF(NOW(), l.fecha_publicacion) > 30
        ");
        $stmt->execute();
        $sin_ventas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sin_ventas['total'] > 0) {
            $alertas[] = array(
                'tipo' => 'libros_sin_ventas',
                'nivel' => 'info',
                'icono' => 'fas fa-chart-line',
                'color' => 'text-info',
                'titulo' => 'Libros sin ventas',
                'descripcion' => "{$sin_ventas['total']} libro" . ($sin_ventas['total'] > 1 ? 's' : '') . " publicado" . ($sin_ventas['total'] > 1 ? 's' : '') . " sin ventas en 30+ días",
                'detalle' => 'Considere crear campañas promocionales',
                'accion' => 'Crear Campaña',
                'enlace' => '#campanas',
                'prioridad' => 'media'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando libros sin ventas: " . $e->getMessage());
    }
    
    // 6. ESTADO DEL SISTEMA (verificaciones básicas)
    try {
        $sistema_ok = true;
        $errores_sistema = array();
        
        // Verificar conectividad de BD
        $stmt = $pdo->prepare("SELECT 1");
        $stmt->execute();
        
        // Verificar tablas principales
        $tablas_requeridas = ['usuarios', 'libros', 'testimonios'];
        foreach ($tablas_requeridas as $tabla) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tabla]);
            if ($stmt->rowCount() == 0) {
                $errores_sistema[] = "Tabla '{$tabla}' no encontrada";
                $sistema_ok = false;
            }
        }
        
        if (!$sistema_ok) {
            $alertas[] = array(
                'tipo' => 'sistema_error',
                'nivel' => 'danger',
                'icono' => 'fas fa-exclamation-triangle',
                'color' => 'text-danger',
                'titulo' => 'Error en el sistema',
                'descripcion' => 'Se detectaron problemas en la base de datos',
                'detalle' => implode(', ', $errores_sistema),
                'accion' => 'Revisar Sistema',
                'enlace' => '#configuracion',
                'prioridad' => 'critica'
            );
        }
    } catch (Exception $e) {
        error_log("Error verificando estado del sistema: " . $e->getMessage());
        $alertas[] = array(
            'tipo' => 'sistema_error',
            'nivel' => 'danger',
            'icono' => 'fas fa-exclamation-triangle',
            'color' => 'text-danger',
            'titulo' => 'Error de sistema',
            'descripcion' => 'No se pudo verificar el estado del sistema',
            'detalle' => 'Revise la conectividad y configuración',
            'accion' => 'Revisar',
            'enlace' => '#configuracion',
            'prioridad' => 'critica'
        );
    }
    
    // Ordenar alertas por prioridad
    $prioridades = ['critica' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    usort($alertas, function($a, $b) use ($prioridades) {
        return $prioridades[$b['prioridad']] - $prioridades[$a['prioridad']];
    });
    
    // Contar alertas por nivel
    $resumen = array(
        'criticas' => 0,
        'altas' => 0,
        'medias' => 0,
        'bajas' => 0,
        'total' => count($alertas)
    );
    
    foreach ($alertas as $alerta) {
        switch ($alerta['prioridad']) {
            case 'critica': $resumen['criticas']++; break;
            case 'alta': $resumen['altas']++; break;
            case 'media': $resumen['medias']++; break;
            case 'baja': $resumen['bajas']++; break;
        }
    }
    
    // Si no hay alertas críticas, agregar mensaje positivo
    if ($resumen['criticas'] == 0 && $resumen['altas'] == 0) {
        array_unshift($alertas, array(
            'tipo' => 'sistema_ok',
            'nivel' => 'success',
            'icono' => 'fas fa-check-circle',
            'color' => 'text-success',
            'titulo' => 'Sistema funcionando correctamente',
            'descripcion' => 'No hay alertas críticas pendientes',
            'detalle' => 'Todas las operaciones funcionan normalmente',
            'accion' => '',
            'enlace' => '',
            'prioridad' => 'baja'
        ));
        $resumen['total']++;
        $resumen['bajas']++;
    }
    
    // Formatear respuesta
    $response = array(
        'success' => true,
        'alertas' => $alertas,
        'resumen' => $resumen,
        'fecha_actualizacion' => date('Y-m-d H:i:s'),
        'mensaje' => 'Alertas del sistema cargadas correctamente',
        'timestamp' => time()
    );
    
    // Log para debugging
    error_log("Alertas del sistema generadas: " . count($alertas) . " alertas");
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de conexión BD en alertas: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'mensaje' => 'No se pudieron cargar las alertas del sistema',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error general en alertas: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error interno del servidor',
        'mensaje' => 'No se pudieron cargar las alertas del sistema',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>