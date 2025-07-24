<?php
/**
 * API Dashboard Afiliado
 * Obtiene todos los datos necesarios para el dashboard del afiliado
 */

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/publiery');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Debug: Verificar si hay sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Mostrar información de sesión
error_log("Dashboard afiliado - Estado de sesión: " . session_status());
error_log("Dashboard afiliado - User ID en sesión: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'No hay user_id'));

// Función para verificar autenticación con sesión PHP
function checkSessionAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    return null;
}

// Verificar autenticación
$userId = checkSessionAuth();
error_log("Dashboard - User ID obtenido: " . ($userId ? $userId : 'null'));

if (!$userId) {
    error_log("Dashboard - No autorizado: userId es null");
    jsonResponse(['error' => 'No autorizado'], 401);
}

// Verificar que el usuario sea afiliado o admin
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    error_log("Dashboard - Usuario encontrado: " . ($user ? json_encode($user) : 'null'));
    
    if (!$user || ($user['rol'] !== 'afiliado' && $user['rol'] !== 'admin' && $user['rol'] !== 'lector')) {
        error_log("Dashboard - Acceso denegado: rol = " . ($user ? $user['rol'] : 'null'));
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
} catch (Exception $e) {
    error_log("Dashboard - Error de autenticación: " . $e->getMessage());
    jsonResponse(['error' => 'Error de autenticación'], 500);
}

try {
    // Obtener información del afiliado
    $stmt = $conn->prepare("
        SELECT a.*, u.nombre, u.email, u.estado as estado_usuario, u.fecha_registro, up.nombre AS nombre_patrocinador
        FROM afiliados a
        JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN afiliados ap ON a.patrocinador_id = ap.id
        LEFT JOIN usuarios up ON ap.usuario_id = up.id
        WHERE a.usuario_id = ?
    ");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();

    error_log("Dashboard - Afiliado encontrado: " . ($afiliado ? json_encode($afiliado) : 'null'));

    // Si es lector, crear datos básicos sin necesidad de registro en afiliados
    if ($user['rol'] === 'lector') {
        $afiliado = [
            'id' => null,
            'codigo_afiliado' => 'LECTOR_' . $userId, // Código temporal para lectores
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'nivel' => 0,
            'frontal' => 0,
            'fecha_activacion' => null,
            'fecha_registro' => $user['fecha_registro'],
            'estado_usuario' => $user['estado'],
            'nombre_patrocinador' => null
        ];
    } elseif (!$afiliado) {
        error_log("Dashboard - Afiliado no encontrado para userId: " . $userId);
        jsonResponse(['error' => 'Afiliado no encontrado'], 404);
    }

    // Obtener estadísticas de comisiones (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $statsComisiones = [
            'total_comisiones' => 0,
            'comisiones_pendientes' => 0,
            'comisiones_pagadas' => 0,
            'total_ganado' => 0
        ];
    } else {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_comisiones,
                SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as comisiones_pendientes,
                SUM(CASE WHEN estado = 'pagada' THEN monto ELSE 0 END) as comisiones_pagadas,
                SUM(monto) as total_ganado
            FROM comisiones 
            WHERE afiliado_id = ?
        ");
        $stmt->execute([$afiliado['id']]);
        $statsComisiones = $stmt->fetch();
    }

    // Obtener comisiones recientes (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $comisionesRecientes = [];
    } else {
        $stmt = $conn->prepare("
            SELECT c.*, v.total as precio_venta, l.titulo as libro_titulo, u.nombre as comprador_nombre
            FROM comisiones c
            JOIN ventas v ON c.venta_id = v.id
            JOIN libros l ON v.libro_id = l.id
            JOIN usuarios u ON v.comprador_id = u.id
            WHERE c.afiliado_id = ?
            ORDER BY c.fecha_generacion DESC
            LIMIT 10
        ");
        $stmt->execute([$afiliado['id']]);
        $comisionesRecientes = $stmt->fetchAll();
    }

    // Obtener red de afiliados (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $redAfiliados = [];
    } else {
        $stmt = $conn->prepare("
            SELECT 
                a.id,
                a.codigo_afiliado,
                a.nivel,
                a.comision_total,
                a.ventas_totales,
                u.nombre,
                u.estado as estado_usuario,
                COUNT(DISTINCT h.id) as hijos_directos
            FROM afiliados a
            JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN afiliados h ON a.id = h.patrocinador_id
            WHERE a.patrocinador_id = ?
            GROUP BY a.id
            ORDER BY a.nivel, a.fecha_activacion
        ");
        $stmt->execute([$afiliado['id']]);
        $redAfiliados = $stmt->fetchAll();
    }

    // Obtener ventas recientes (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $ventasRecientes = [];
    } else {
        $stmt = $conn->prepare("
            SELECT 
                v.*,
                l.titulo as libro_titulo,
                u.nombre as comprador_nombre,
                c.monto as comision_generada
            FROM ventas v
            JOIN libros l ON v.libro_id = l.id
            JOIN usuarios u ON v.comprador_id = u.id
            LEFT JOIN comisiones c ON v.id = c.venta_id AND c.afiliado_id = ?
            WHERE v.afiliado_id = ?
            ORDER BY v.fecha_venta DESC
            LIMIT 10
        ");
        $stmt->execute([$afiliado['id'], $afiliado['id']]);
        $ventasRecientes = $stmt->fetchAll();
    }

    // Obtener retiros recientes (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $retirosRecientes = [];
    } else {
        $stmt = $conn->prepare("
            SELECT *
            FROM retiros
            WHERE usuario_id = ?
            ORDER BY fecha_solicitud DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $retirosRecientes = $stmt->fetchAll();
    }

    // Calcular estadísticas de red por nivel (solo para afiliados)
    if ($user['rol'] === 'lector') {
        $statsRed = [];
        for ($i = 1; $i <= 6; $i++) {
            $statsRed[$i] = [
                'nivel' => $i,
                'total_afiliados' => 0,
                'comision_total' => 0
            ];
        }
    } else {
        $statsRed = [];
        for ($i = 1; $i <= 6; $i++) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total, SUM(comision_total) as comision_total
                FROM afiliados
                WHERE patrocinador_id = ? AND nivel = ?
            ");
            $stmt->execute([$afiliado['id'], $i]);
            $nivelStats = $stmt->fetch();
            $statsRed[$i] = [
                'nivel' => $i,
                'total_afiliados' => $nivelStats['total'] ?? 0,
                'comision_total' => $nivelStats['comision_total'] ?? 0
            ];
        }
    }

    // Normalizar valores numéricos en statsComisiones
    foreach ([
        'total_comisiones',
        'comisiones_pendientes',
        'comisiones_pagadas',
        'total_ganado'
    ] as $campo) {
        if (!isset($statsComisiones[$campo]) || $statsComisiones[$campo] === null) {
            $statsComisiones[$campo] = 0;
        }
    }

    // Normalizar arrays recientes
    $comisionesRecientes = $comisionesRecientes ?: [];
    $ventasRecientes = $ventasRecientes ?: [];
    $retirosRecientes = $retirosRecientes ?: [];
    $redAfiliados = $redAfiliados ?: [];

    // Normalizar statsRed
    foreach ($statsRed as &$nivel) {
        if (!isset($nivel['total_afiliados']) || $nivel['total_afiliados'] === null) {
            $nivel['total_afiliados'] = 0;
        }
        if (!isset($nivel['comision_total']) || $nivel['comision_total'] === null) {
            $nivel['comision_total'] = 0;
        }
    }
    unset($nivel);

    // === CÁLCULO DE CRECIMIENTO ===
    // Período actual: últimos 30 días
    $fechaFin = date('Y-m-d');
    $fechaInicio = date('Y-m-d', strtotime('-30 days'));
    // Período anterior: 30 días antes del actual
    $fechaFinAnterior = date('Y-m-d', strtotime('-31 days'));
    $fechaInicioAnterior = date('Y-m-d', strtotime('-60 days'));

    // Ventas actuales
    $stmt = $conn->prepare("SELECT COUNT(*) as total_ventas FROM ventas WHERE afiliado_id = ? AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicio, $fechaFin]);
    $ventasActual = $stmt->fetchColumn();
    // Ventas anteriores
    $stmt = $conn->prepare("SELECT COUNT(*) as total_ventas FROM ventas WHERE afiliado_id = ? AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicioAnterior, $fechaFinAnterior]);
    $ventasAnterior = $stmt->fetchColumn();
    // Crecimiento ventas
    $crecimientoVentas = ($ventasAnterior > 0) ? round((($ventasActual - $ventasAnterior) / $ventasAnterior) * 100, 2) : ($ventasActual > 0 ? 100 : 0);

    // Comisiones actuales
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM comisiones WHERE afiliado_id = ? AND fecha_generacion BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicio, $fechaFin]);
    $comisionesActual = $stmt->fetchColumn() ?: 0;
    // Comisiones anteriores
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM comisiones WHERE afiliado_id = ? AND fecha_generacion BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicioAnterior, $fechaFinAnterior]);
    $comisionesAnterior = $stmt->fetchColumn() ?: 0;
    // Crecimiento comisiones
    $crecimientoComisiones = ($comisionesAnterior > 0) ? round((($comisionesActual - $comisionesAnterior) / $comisionesAnterior) * 100, 2) : ($comisionesActual > 0 ? 100 : 0);

    // Afiliados actuales
    $stmt = $conn->prepare("SELECT COUNT(*) FROM afiliados WHERE patrocinador_id = ? AND fecha_activacion BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicio, $fechaFin]);
    $afiliadosActual = $stmt->fetchColumn();
    // Afiliados anteriores
    $stmt = $conn->prepare("SELECT COUNT(*) FROM afiliados WHERE patrocinador_id = ? AND fecha_activacion BETWEEN ? AND ?");
    $stmt->execute([$afiliado['id'], $fechaInicioAnterior, $fechaFinAnterior]);
    $afiliadosAnterior = $stmt->fetchColumn();
    // Crecimiento afiliados
    $crecimientoAfiliados = ($afiliadosAnterior > 0) ? round((($afiliadosActual - $afiliadosAnterior) / $afiliadosAnterior) * 100, 2) : ($afiliadosActual > 0 ? 100 : 0);

    // Preparar respuesta
    $response = [
        'success' => true,
        'afiliado' => [
            'id' => $afiliado['id'],
            'codigo_afiliado' => $afiliado['codigo_afiliado'],
            'nombre' => $afiliado['nombre'],
            'email' => $afiliado['email'],
            'nivel' => $afiliado['nivel'],
            'frontal' => $afiliado['frontal'],
            'enlace_afiliado' => APP_URL . "/registro.html?ref=" . $afiliado['codigo_afiliado'],
            'qr_code' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode(APP_URL . "/registro.html?ref=" . $afiliado['codigo_afiliado']),
            'fecha_activacion' => $afiliado['fecha_activacion'],
            'fecha_registro' => $afiliado['fecha_registro'],
            'estado' => $afiliado['estado_usuario'],
            'nombre_patrocinador' => $afiliado['nombre_patrocinador']
        ],
        'estadisticas' => [
            'comisiones' => $statsComisiones,
            'red' => $statsRed,
            'total_afiliados_red' => array_sum(array_column($statsRed, 'total_afiliados')),
            'comision_total_red' => array_sum(array_column($statsRed, 'comision_total')),
            // NUEVO: porcentajes de crecimiento
            'crecimiento' => [
                'ventas' => $crecimientoVentas,
                'comisiones' => $crecimientoComisiones,
                'afiliados' => $crecimientoAfiliados
            ]
        ],
        'datos_recientes' => [
            'comisiones' => $comisionesRecientes,
            'ventas' => $ventasRecientes,
            'retiros' => $retirosRecientes,
            'red_afiliados' => $redAfiliados
        ],
        'campanas_activas' => [] // Por ahora vacío hasta implementar campañas
    ];

    jsonResponse($response, 200);

} catch (Exception $e) {
    error_log("Error en dashboard afiliado: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?> 