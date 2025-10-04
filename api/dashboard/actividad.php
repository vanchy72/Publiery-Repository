<?php
/**
 * API para Actividad Reciente del Dashboard
 * Obtiene las últimas acciones realizadas en el sistema para mostrar un feed de actividad
 * 
 * Endpoints:
 * GET /api/dashboard/actividad.php?limite=10
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
    
    $limite = isset($_GET['limite']) ? max(1, min(50, (int)$_GET['limite'])) : 10;
    $dias = isset($_GET['dias']) ? max(1, min(30, (int)$_GET['dias'])) : 7; // Limitar a 7 días por defecto
    $actividades = array();
    
    // 1. USUARIOS REGISTRADOS RECIENTEMENTE (ÚLTIMOS 7 DÍAS)
    try {
        $stmt = $pdo->prepare("
            SELECT 'usuario_registrado' as tipo, nombre, email, fecha_registro as fecha, rol
            FROM usuarios 
            WHERE estado != 'eliminado'
            AND fecha_registro >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY fecha_registro DESC 
            LIMIT ?
        ");
        $stmt->execute([$dias, $limite]);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usuarios as $usuario) {
            $actividades[] = array(
                'tipo' => 'usuario_registrado',
                'icono' => 'fas fa-user-plus',
                'color' => 'text-primary',
                'titulo' => 'Nuevo usuario registrado',
                'descripcion' => "{$usuario['nombre']} se registró como {$usuario['rol']}",
                'detalle' => $usuario['email'],
                'fecha' => $usuario['fecha'],
                'fecha_humana' => tiempoTranscurrido($usuario['fecha'])
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando usuarios recientes: " . $e->getMessage());
    }
    
    // 2. LIBROS PUBLICADOS RECIENTEMENTE (ÚLTIMOS 7 DÍAS)
    try {
        $stmt = $pdo->prepare("
            SELECT 'libro_publicado' as tipo, l.titulo, l.fecha_publicacion as fecha, 
                   u.nombre as autor, l.precio
            FROM libros l
            LEFT JOIN usuarios u ON l.autor_id = u.id
            WHERE l.estado = 'publicado'
            AND l.fecha_publicacion >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY l.fecha_publicacion DESC 
            LIMIT ?
        ");
        $stmt->execute([$dias, $limite]);
        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($libros as $libro) {
            $actividades[] = array(
                'tipo' => 'libro_publicado',
                'icono' => 'fas fa-book',
                'color' => 'text-success',
                'titulo' => 'Libro publicado',
                'descripcion' => "\"{$libro['titulo']}\" fue publicado",
                'detalle' => "por {$libro['autor']} - \$" . number_format($libro['precio'], 0, '.', ','),
                'fecha' => $libro['fecha'],
                'fecha_humana' => tiempoTranscurrido($libro['fecha'])
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando libros recientes: " . $e->getMessage());
    }
    
    // 3. VENTAS PROCESADAS (ÚLTIMOS 7 DÍAS)
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'ventas'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT 'venta_procesada' as tipo, v.fecha_venta as fecha, v.precio,
                       l.titulo, u.nombre as comprador
                FROM ventas v
                LEFT JOIN libros l ON v.libro_id = l.id
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                WHERE v.fecha_venta >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY v.fecha_venta DESC 
                LIMIT ?
            ");
            $stmt->execute([$dias, $limite]);
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ventas as $venta) {
                $actividades[] = array(
                    'tipo' => 'venta_procesada',
                    'icono' => 'fas fa-shopping-cart',
                    'color' => 'text-info',
                    'titulo' => 'Venta procesada',
                    'descripcion' => "Venta de \"{$venta['titulo']}\"",
                    'detalle' => "por \$" . number_format($venta['precio'], 0, '.', ',') . " - " . $venta['comprador'],
                    'fecha' => $venta['fecha'],
                    'fecha_humana' => tiempoTranscurrido($venta['fecha'])
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error consultando ventas recientes: " . $e->getMessage());
    }
    
    // 4. PAGOS PROCESADOS (simulado por ahora)
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'pagos'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT 'pago_procesado' as tipo, fecha_pago as fecha, monto, 
                       beneficiario, tipo_pago
                FROM pagos 
                WHERE estado = 'completado'
                ORDER BY fecha_pago DESC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pagos as $pago) {
                $actividades[] = array(
                    'tipo' => 'pago_procesado',
                    'icono' => 'fas fa-dollar-sign',
                    'color' => 'text-warning',
                    'titulo' => 'Pago procesado',
                    'descripcion' => ucfirst($pago['tipo_pago']) . " procesado",
                    'detalle' => "\$" . number_format($pago['monto'], 0, '.', ',') . " para " . $pago['beneficiario'],
                    'fecha' => $pago['fecha'],
                    'fecha_humana' => tiempoTranscurrido($pago['fecha'])
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error consultando pagos recientes: " . $e->getMessage());
    }
    
    // 5. TESTIMONIOS APROBADOS (ÚLTIMOS 7 DÍAS)
    try {
        $stmt = $pdo->prepare("
            SELECT 'testimonio_aprobado' as tipo, t.fecha_creacion as fecha,
                   t.nombre_cliente, l.titulo as libro, t.calificacion
            FROM testimonios t
            LEFT JOIN libros l ON t.libro_id = l.id
            WHERE t.estado = 'aprobado'
            AND t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY t.fecha_creacion DESC 
            LIMIT ?
        ");
        $stmt->execute([$dias, $limite]);
        $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($testimonios as $testimonio) {
            $estrellas = str_repeat('⭐', (int)$testimonio['calificacion']);
            $actividades[] = array(
                'tipo' => 'testimonio_aprobado',
                'icono' => 'fas fa-star',
                'color' => 'text-warning',
                'titulo' => 'Testimonio aprobado',
                'descripcion' => "Nuevo testimonio para \"{$testimonio['libro']}\"",
                'detalle' => "por {$testimonio['nombre_cliente']} - {$estrellas}",
                'fecha' => $testimonio['fecha'],
                'fecha_humana' => tiempoTranscurrido($testimonio['fecha'])
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando testimonios recientes: " . $e->getMessage());
    }
    
    // Ordenar todas las actividades por fecha (más recientes primero)
    usort($actividades, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    // Limitar al número solicitado
    $actividades = array_slice($actividades, 0, $limite);
    
    // Si no hay actividades, crear algunas de ejemplo
    if (empty($actividades)) {
        $actividades = array(
            array(
                'tipo' => 'sistema_iniciado',
                'icono' => 'fas fa-server',
                'color' => 'text-muted',
                'titulo' => 'Sistema iniciado',
                'descripcion' => 'Panel administrativo de Publiery',
                'detalle' => 'Sistema funcionando correctamente',
                'fecha' => date('Y-m-d H:i:s'),
                'fecha_humana' => 'hace unos momentos'
            )
        );
    }
    
    // Formatear respuesta
    $response = array(
        'success' => true,
        'actividades' => $actividades,
        'total' => count($actividades),
        'limite' => $limite,
        'periodo' => array(
            'dias' => $dias,
            'descripcion' => "Últimos {$dias} días",
            'fecha_desde' => date('Y-m-d', strtotime("-{$dias} days")),
            'fecha_hasta' => date('Y-m-d')
        ),
        'fecha_actualizacion' => date('Y-m-d H:i:s'),
        'mensaje' => "Actividad reciente cargada correctamente (últimos {$dias} días)",
        'timestamp' => time()
    );
    
    // Log para debugging
    error_log("Actividad reciente generada: " . count($actividades) . " items");
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de conexión BD en actividad: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'mensaje' => 'No se pudo cargar la actividad reciente',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error general en actividad: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error interno del servidor',
        'mensaje' => 'No se pudo cargar la actividad reciente',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Función para calcular tiempo transcurrido en formato humano
 */
function tiempoTranscurrido($fecha) {
    $ahora = new DateTime();
    $fechaObj = new DateTime($fecha);
    $diferencia = $ahora->diff($fechaObj);
    
    if ($diferencia->d > 0) {
        return $diferencia->d . ' día' . ($diferencia->d > 1 ? 's' : '') . ' atrás';
    } elseif ($diferencia->h > 0) {
        return $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '') . ' atrás';
    } elseif ($diferencia->i > 0) {
        return $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '') . ' atrás';
    } else {
        return 'hace unos momentos';
    }
}
?>