<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden generar facturas'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    jsonResponse(['success' => false, 'error' => 'ID de venta requerido'], 400);
    exit;
}

$ventaId = (int)$input['id'];

try {
    // Obtener datos de la venta
    $stmt = $db->prepare("
        SELECT 
            v.id,
            v.libro_id,
            v.comprador_nombre,
            v.comprador_email,
            v.precio_pagado,
            v.estado,
            v.fecha_venta,
            l.titulo as libro_titulo,
            l.autor as libro_autor,
            l.precio as libro_precio,
            a.nombre as afiliado_nombre,
            a.email as afiliado_email
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        LEFT JOIN afiliados a ON v.afiliado_id = a.id
        WHERE v.id = ?
    ");
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch();

    if (!$venta) {
        jsonResponse(['success' => false, 'error' => 'Venta no encontrada'], 404);
        exit;
    }

    if ($venta['estado'] !== 'completada') {
        jsonResponse(['success' => false, 'error' => 'Solo se pueden generar facturas para ventas completadas'], 400);
        exit;
    }

    // Generar número de factura único
    $numeroFactura = 'FAC-' . date('Y') . '-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT);

    // Datos de la factura
    $factura = [
        'numero' => $numeroFactura,
        'fecha' => date('d/m/Y'),
        'venta' => [
            'id' => $venta['id'],
            'fecha_venta' => date('d/m/Y', strtotime($venta['fecha_venta'])),
            'estado' => $venta['estado']
        ],
        'libro' => [
            'titulo' => $venta['libro_titulo'],
            'autor' => $venta['libro_autor'],
            'precio_unitario' => number_format($venta['libro_precio'], 2),
            'cantidad' => 1,
            'subtotal' => number_format($venta['precio_pagado'], 2)
        ],
        'comprador' => [
            'nombre' => $venta['comprador_nombre'],
            'email' => $venta['comprador_email']
        ],
        'afiliado' => $venta['afiliado_nombre'] ? [
            'nombre' => $venta['afiliado_nombre'],
            'email' => $venta['afiliado_email']
        ] : null,
        'totales' => [
            'subtotal' => number_format($venta['precio_pagado'], 2),
            'impuestos' => '0.00',
            'total' => number_format($venta['precio_pagado'], 2)
        ],
        'empresa' => [
            'nombre' => 'Publiery',
            'direccion' => 'Plataforma Digital de Publicación',
            'telefono' => 'contacto@publiery.com',
            'email' => 'soporte@publiery.com'
        ]
    ];

    jsonResponse([
        'success' => true,
        'factura' => $factura,
        'message' => 'Factura generada correctamente'
    ]);

} catch (Exception $e) {
    error_log('Error generando factura: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>