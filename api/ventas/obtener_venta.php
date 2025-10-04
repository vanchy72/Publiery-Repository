<?php
/**
 * API para obtener información de una venta
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
$user = getCurrentUser();

// Verificar método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $ventaId = $_GET['id'] ?? null;
    
    if (!$ventaId) {
        throw new Exception('ID de venta requerido');
    }
    
    $conn = getDBConnection();
    
    // Obtener información completa de la venta
    $stmt = $conn->prepare("
        SELECT 
            v.*,
            l.titulo as libro_titulo,
            l.descripcion as libro_descripcion,
            l.imagen_portada as libro_imagen_portada,
            l.archivo_pdf as libro_archivo,
            l.autor_id,
            u.nombre as afiliado_nombre,
            u.email as afiliado_email,
            a.nombre as autor_nombre,
            a.foto as autor_foto,
            a.biografia as autor_bio
        FROM ventas v
        JOIN libros l ON v.libro_id = l.id
        JOIN usuarios u ON v.afiliado_id = u.id
        JOIN usuarios a ON l.autor_id = a.id
        WHERE v.id = ?
    ");
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch();
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
    // Permitir solo al comprador, afiliado, autor o admin
    $permitido = (
        $user['rol'] === 'admin' ||
        $user['id'] == $venta['comprador_id'] ||
        $user['id'] == $venta['afiliado_id'] ||
        $user['id'] == $venta['autor_id']
    );
    if (!$permitido) {
        throw new Exception('No autorizado para ver esta venta');
    }
    
    // Verificar que la venta esté pagada
    if ($venta['estado'] !== 'pagado') {
        throw new Exception('La venta no ha sido pagada');
    }
    
    // Formatear datos para la respuesta
    $ventaFormateada = [
        'id' => $venta['id'],
        'referencia_pago' => $venta['referencia_pago'],
        'precio_pagado' => $venta['precio_pagado'],
        'comision_afiliado' => $venta['comision_afiliado'],
        'estado' => $venta['estado'],
        'metodo_pago' => $venta['metodo_pago'],
        'fecha_compra' => $venta['fecha_compra'],
        'fecha_pago' => $venta['fecha_pago'],
        'libro' => [
            'id' => $venta['libro_id'],
            'titulo' => $venta['libro_titulo'],
            'descripcion' => $venta['libro_descripcion'],
            'imagen_portada' => $venta['libro_imagen_portada'],
            'archivo_pdf' => $venta['libro_archivo'],
            'autor_nombre' => $venta['autor_nombre'],
            'autor_foto' => $venta['autor_foto'],
            'autor_bio' => $venta['autor_bio']
        ],
        'afiliado' => [
            'id' => $venta['afiliado_id'],
            'nombre' => $venta['afiliado_nombre'],
            'email' => $venta['afiliado_email']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'venta' => $ventaFormateada
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 