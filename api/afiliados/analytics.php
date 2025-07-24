<?php
/**
 * API Analytics Avanzados para Afiliados
 * Proporciona métricas detalladas de rendimiento y análisis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$user = getCurrentUser();
if ($user['rol'] !== 'afiliado' && $user['rol'] !== 'admin') {
    jsonResponse(['error' => 'Acceso denegado'], 403);
}

try {
    $conn = getDBConnection();
    $userId = $user['id'];
    $afiliadoId = null;

    error_log('[analytics.php] Iniciando para usuario: ' . $userId);

    // Obtener ID del afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    error_log('[analytics.php] Resultado SELECT afiliados: ' . json_encode($afiliado));
    
    if (!$afiliado) {
        error_log("[analytics.php] Afiliado no encontrado para usuario $userId");
        // Devolver respuesta vacía pero válida
        $response = [
            'success' => true,
            'periodo_analisis' => [
                'inicio' => date('Y-m-d', strtotime('-30 days')),
                'fin' => date('Y-m-d'),
                'dias' => 30
            ],
            'metricas_generales' => [
                'ventas' => [
                    'total' => 0,
                    'volumen' => 0,
                    'ticket_promedio' => 0,
                    'clientes_unicos' => 0
                ],
                'comisiones' => [
                    'total_generadas' => 0,
                    'transacciones_con_comision' => 0
                ]
            ],
            'tendencias' => [
                'diarias' => [],
                'crecimiento' => [
                    'ventas' => 0,
                    'volumen' => 0
                ]
            ],
            'productos_top' => [],
            'analisis_red' => [],
            'metricas_conversion' => [
                'total_registros' => 0,
                'registros_convertidos' => 0,
                'registros_con_ventas' => 0,
                'tasa_conversion' => 0
            ],
            'analisis_horarios' => [],
            'comparacion_periodos' => [
                'actual' => [
                    'ventas' => 0,
                    'volumen' => 0,
                    'comisiones' => 0
                ],
                'anterior' => [
                    'ventas' => 0,
                    'volumen' => 0,
                    'comisiones' => 0
                ]
            ]
        ];
        jsonResponse($response, 200);
    }

    $afiliadoId = $afiliado['id'];
    error_log('[analytics.php] afiliadoId: ' . $afiliadoId);

    // Obtener período de análisis (últimos 30 días por defecto)
    $periodo = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 30;
    $fechaInicio = date('Y-m-d', strtotime("-{$periodo} days"));
    $fechaFin = date('Y-m-d');
    error_log("[analytics.php] Periodo: $fechaInicio a $fechaFin");

    // 1. MÉTRICAS DE RENDIMIENTO GENERAL
    try {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT v.id) as total_ventas, SUM(v.total) as volumen_ventas, AVG(v.total) as ticket_promedio, COUNT(DISTINCT v.comprador_id) as clientes_unicos, SUM(c.monto) as comisiones_generadas, COUNT(c.id) as transacciones_con_comision FROM ventas v LEFT JOIN comisiones c ON v.id = c.venta_id AND c.afiliado_id = ? WHERE v.afiliado_id = ? AND v.fecha_venta BETWEEN ? AND ?");
        $stmt->execute([$afiliadoId, $afiliadoId, $fechaInicio, $fechaFin]);
        $metricasGenerales = $stmt->fetch();
        error_log('[analytics.php] metricasGenerales: ' . json_encode($metricasGenerales));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en metricasGenerales: ' . $e->getMessage());
        $metricasGenerales = [
            'total_ventas' => 0,
            'volumen_ventas' => 0,
            'ticket_promedio' => 0,
            'clientes_unicos' => 0,
            'comisiones_generadas' => 0,
            'transacciones_con_comision' => 0
        ];
    }

    // 2. ANÁLISIS DE TENDENCIAS DIARIAS
    try {
        $stmt = $conn->prepare("SELECT DATE(v.fecha_venta) as fecha, COUNT(v.id) as ventas, SUM(v.total) as volumen, SUM(c.monto) as comisiones FROM ventas v LEFT JOIN comisiones c ON v.id = c.venta_id AND c.afiliado_id = ? WHERE v.afiliado_id = ? AND v.fecha_venta BETWEEN ? AND ? GROUP BY DATE(v.fecha_venta) ORDER BY fecha");
        $stmt->execute([$afiliadoId, $afiliadoId, $fechaInicio, $fechaFin]);
        $tendenciasDiarias = $stmt->fetchAll();
        error_log('[analytics.php] tendenciasDiarias: ' . json_encode($tendenciasDiarias));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en tendenciasDiarias: ' . $e->getMessage());
        $tendenciasDiarias = [];
    }

    // 3. ANÁLISIS DE PRODUCTOS MÁS VENDIDOS (GENERAL)
    try {
        $stmt = $conn->prepare("SELECT l.id, l.titulo, l.imagen_portada, COUNT(v.id) as veces_vendido, SUM(v.total) as volumen_generado, SUM(c.monto) as comisiones_generadas, AVG(v.total) as precio_promedio FROM ventas v JOIN libros l ON v.libro_id = l.id LEFT JOIN comisiones c ON v.id = c.venta_id WHERE v.fecha_venta BETWEEN ? AND ? GROUP BY l.id ORDER BY veces_vendido DESC LIMIT 10");
        $stmt->execute([$fechaInicio, $fechaFin]);
        $productosTop = $stmt->fetchAll();
        error_log('[analytics.php] productosTop (general): ' . json_encode($productosTop));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en productosTop: ' . $e->getMessage());
        $productosTop = [];
    }

    // 4. ANÁLISIS DE RED MULTINIVEL
    try {
        $stmt = $conn->prepare("SELECT nivel, COUNT(*) as total_afiliados, SUM(comision_total) as comision_total_nivel, AVG(comision_total) as comision_promedio, COUNT(CASE WHEN estado = 'activo' THEN 1 END) as afiliados_activos, COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as afiliados_pendientes FROM afiliados WHERE patrocinador_id = ? GROUP BY nivel ORDER BY nivel");
        $stmt->execute([$afiliadoId]);
        $analisisRed = $stmt->fetchAll();
        error_log('[analytics.php] analisisRed: ' . json_encode($analisisRed));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en analisisRed: ' . $e->getMessage());
        $analisisRed = [];
    }

    // 5. MÉTRICAS DE CONVERSIÓN
    try {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT u.id) as total_registros, COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN u.id END) as registros_convertidos, COUNT(DISTINCT CASE WHEN v.id IS NOT NULL THEN u.id END) as registros_con_ventas FROM usuarios u LEFT JOIN afiliados a ON u.id = a.usuario_id AND a.patrocinador_id = ? LEFT JOIN ventas v ON u.id = v.comprador_id AND v.afiliado_id = ? WHERE u.fecha_registro BETWEEN ? AND ?");
        $stmt->execute([$afiliadoId, $afiliadoId, $fechaInicio, $fechaFin]);
        $metricasConversion = $stmt->fetch();
        error_log('[analytics.php] metricasConversion: ' . json_encode($metricasConversion));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en metricasConversion: ' . $e->getMessage());
        $metricasConversion = [
            'total_registros' => 0,
            'registros_convertidos' => 0,
            'registros_con_ventas' => 0
        ];
    }

    // 6. ANÁLISIS DE HORARIOS DE VENTA
    try {
        $stmt = $conn->prepare("SELECT HOUR(v.fecha_venta) as hora, COUNT(v.id) as ventas, SUM(v.total) as volumen FROM ventas v WHERE v.afiliado_id = ? AND v.fecha_venta BETWEEN ? AND ? GROUP BY HOUR(v.fecha_venta) ORDER BY hora");
        $stmt->execute([$afiliadoId, $fechaInicio, $fechaFin]);
        $analisisHorarios = $stmt->fetchAll();
        error_log('[analytics.php] analisisHorarios: ' . json_encode($analisisHorarios));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en analisisHorarios: ' . $e->getMessage());
        $analisisHorarios = [];
    }

    // 7. COMPARACIÓN CON PERÍODOS ANTERIORES
    $periodoAnterior = $periodo * 2;
    $fechaInicioAnterior = date('Y-m-d', strtotime("-{$periodoAnterior} days"));
    $fechaFinAnterior = date('Y-m-d', strtotime("-{$periodo} days"));
    try {
        $stmt = $conn->prepare("SELECT COUNT(v.id) as ventas_anterior, SUM(v.total) as volumen_anterior, SUM(c.monto) as comisiones_anterior FROM ventas v LEFT JOIN comisiones c ON v.id = c.venta_id AND c.afiliado_id = ? WHERE v.afiliado_id = ? AND v.fecha_venta BETWEEN ? AND ?");
        $stmt->execute([$afiliadoId, $afiliadoId, $fechaInicioAnterior, $fechaFinAnterior]);
        $comparacionAnterior = $stmt->fetch();
        error_log('[analytics.php] comparacionAnterior: ' . json_encode($comparacionAnterior));
    } catch (Exception $e) {
        error_log('[analytics.php] Error en comparacionAnterior: ' . $e->getMessage());
        $comparacionAnterior = [
            'ventas_anterior' => 0,
            'volumen_anterior' => 0,
            'comisiones_anterior' => 0
        ];
    }

    // Calcular porcentajes de crecimiento
    $crecimientoVentas = $metricasGenerales['total_ventas'] > 0 && $comparacionAnterior['ventas_anterior'] > 0 
        ? (($metricasGenerales['total_ventas'] - $comparacionAnterior['ventas_anterior']) / $comparacionAnterior['ventas_anterior']) * 100 
        : 0;
    
    $crecimientoVolumen = $metricasGenerales['volumen_ventas'] > 0 && $comparacionAnterior['volumen_anterior'] > 0 
        ? (($metricasGenerales['volumen_ventas'] - $comparacionAnterior['volumen_anterior']) / $comparacionAnterior['volumen_anterior']) * 100 
        : 0;

    // Normalizar tendencias diarias: asegurar que haya al menos 7 días (o el periodo solicitado) con datos
    $dias = $periodo;
    $tendenciasPorFecha = [];
    foreach ($tendenciasDiarias as $t) {
        $tendenciasPorFecha[$t['fecha']] = [
            'fecha' => $t['fecha'],
            'ventas' => (int)$t['ventas'],
            'volumen' => (float)($t['volumen'] ?? 0),
            'comisiones' => (float)($t['comisiones'] ?? 0)
        ];
    }
    $tendenciasCompletas = [];
    for ($i = $dias - 1; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        if (isset($tendenciasPorFecha[$fecha])) {
            $tendenciasCompletas[] = $tendenciasPorFecha[$fecha];
        } else {
            $tendenciasCompletas[] = [
                'fecha' => $fecha,
                'ventas' => 0,
                'volumen' => 0,
                'comisiones' => 0
            ];
        }
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'periodo_analisis' => [
            'inicio' => $fechaInicio,
            'fin' => $fechaFin,
            'dias' => $periodo
        ],
        'metricas_generales' => [
            'ventas' => [
                'total' => (int)$metricasGenerales['total_ventas'],
                'volumen' => (float)$metricasGenerales['volumen_ventas'],
                'ticket_promedio' => (float)$metricasGenerales['ticket_promedio'],
                'clientes_unicos' => (int)$metricasGenerales['clientes_unicos']
            ],
            'comisiones' => [
                'total_generadas' => (float)$metricasGenerales['comisiones_generadas'],
                'transacciones_con_comision' => (int)$metricasGenerales['transacciones_con_comision']
            ]
        ],
        'tendencias' => [
            'diarias' => $tendenciasCompletas,
            'crecimiento' => [
                'ventas' => round($crecimientoVentas, 2),
                'volumen' => round($crecimientoVolumen, 2)
            ]
        ],
        'productos_top' => $productosTop,
        'analisis_red' => $analisisRed,
        'metricas_conversion' => [
            'total_registros' => (int)$metricasConversion['total_registros'],
            'registros_convertidos' => (int)$metricasConversion['registros_convertidos'],
            'registros_con_ventas' => (int)$metricasConversion['registros_con_ventas'],
            'tasa_conversion' => $metricasConversion['total_registros'] > 0 
                ? round(($metricasConversion['registros_convertidos'] / $metricasConversion['total_registros']) * 100, 2)
                : 0
        ],
        'analisis_horarios' => $analisisHorarios,
        'comparacion_periodos' => [
            'actual' => [
                'ventas' => (int)$metricasGenerales['total_ventas'],
                'volumen' => (float)$metricasGenerales['volumen_ventas'],
                'comisiones' => (float)$metricasGenerales['comisiones_generadas']
            ],
            'anterior' => [
                'ventas' => (int)$comparacionAnterior['ventas_anterior'],
                'volumen' => (float)$comparacionAnterior['volumen_anterior'],
                'comisiones' => (float)$comparacionAnterior['comisiones_anterior']
            ]
        ]
    ];

    jsonResponse($response, 200);

} catch (Exception $e) {
    error_log("Error en analytics afiliado: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
?> 