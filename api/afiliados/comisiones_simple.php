<?php
/**
 * API simple para obtener comisiones del afiliado
 * Versión simplificada sin autenticación compleja
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión básica
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Obtener ID del afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        echo json_encode(['error' => 'Afiliado no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $afiliadoId = $afiliado['id'];
    
    // Obtener comisiones con información detallada
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.monto,
            c.porcentaje,
            c.estado,
            c.fecha_generacion,
            c.fecha_pago,
            v.total as precio_venta,
            l.titulo as libro_titulo,
            u.nombre as comprador_nombre,
            u.email as comprador_email,
            CASE 
                WHEN c.afiliado_id = v.afiliado_id THEN 1
                ELSE 2
            END as nivel
        FROM comisiones c
        JOIN ventas v ON c.venta_id = v.id
        JOIN libros l ON v.libro_id = l.id
        JOIN usuarios u ON v.comprador_id = u.id
        WHERE c.afiliado_id = ?
        ORDER BY c.fecha_generacion DESC
    ");
    $stmt->execute([$afiliadoId]);
    $comisiones = $stmt->fetchAll();
    
    // Normalizar datos
    foreach ($comisiones as &$comision) {
        $comision['monto'] = (float)$comision['monto'];
        $comision['porcentaje'] = (float)$comision['porcentaje'];
        $comision['nivel'] = (int)$comision['nivel'];
        $comision['precio_venta'] = (float)$comision['precio_venta'];
        
        // Formatear fechas
        $comision['fecha_generacion'] = date('Y-m-d H:i:s', strtotime($comision['fecha_generacion']));
        if ($comision['fecha_pago']) {
            $comision['fecha_pago'] = date('Y-m-d H:i:s', strtotime($comision['fecha_pago']));
        }
    }
    
    // Calcular estadísticas
    $totalGanado = array_sum(array_column($comisiones, 'monto'));
    $comisionesPagadas = array_sum(array_column(array_filter($comisiones, function($c) {
        return $c['estado'] === 'pagada';
    }), 'monto'));
    $comisionesPendientes = array_sum(array_column(array_filter($comisiones, function($c) {
        return $c['estado'] === 'pendiente';
    }), 'monto'));
    
    // Agrupar por nivel para el gráfico
    $comisionesPorNivel = [];
    foreach ($comisiones as $comision) {
        $nivel = $comision['nivel'];
        if (!isset($comisionesPorNivel[$nivel])) {
            $comisionesPorNivel[$nivel] = 0;
        }
        $comisionesPorNivel[$nivel] += $comision['monto'];
    }
    
    $response = [
        'success' => true,
        'comisiones' => $comisiones,
        'estadisticas' => [
            'total_ganado' => $totalGanado,
            'comisiones_pagadas' => $comisionesPagadas,
            'comisiones_pendientes' => $comisionesPendientes,
            'total_comisiones' => count($comisiones)
        ],
        'comisiones_por_nivel' => $comisionesPorNivel
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?> 