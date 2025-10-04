<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de ventas'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de venta requerido'], 400);
    exit;
}

try {
    // Obtener información completa de la venta
    $sql = "
        SELECT 
            v.*,
            l.titulo as libro_titulo,
            l.precio as libro_precio,
            ue.nombre as escritor_nombre,
            ue.email as escritor_email,
            uc.nombre as comprador_nombre,
            uc.email as comprador_email,
            ua.nombre as afiliado_nombre,
            a.codigo_afiliado as afiliado_codigo
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios ue ON l.autor_id = ue.id
        INNER JOIN usuarios uc ON v.comprador_id = uc.id
        LEFT JOIN afiliados a ON v.afiliado_id = a.id
        LEFT JOIN usuarios ua ON a.usuario_id = ua.id
        WHERE v.id = ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        jsonResponse(['success' => false, 'error' => 'Venta no encontrada'], 404);
        exit;
    }

    // Obtener comisiones relacionadas con esta venta
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.monto,
            c.nivel,
            c.estado as comision_estado,
            u.nombre as beneficiario_nombre,
            a.codigo_afiliado as beneficiario_codigo
        FROM comisiones c
        INNER JOIN afiliados a ON c.afiliado_id = a.id
        INNER JOIN usuarios u ON a.usuario_id = u.id
        WHERE c.venta_id = ?
        ORDER BY c.nivel ASC
    ");
    $stmt->execute([$id]);
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos de la venta
    $ventaFormateada = [
        'id' => (int)$venta['id'],
        'libro_id' => (int)$venta['libro_id'],
        'comprador_id' => (int)$venta['comprador_id'],
        'afiliado_id' => $venta['afiliado_id'] ? (int)$venta['afiliado_id'] : null,
        'fecha_venta' => $venta['fecha_venta'],
        'cantidad' => (int)$venta['cantidad'],
        'total' => (float)$venta['total'],
        'transaction_id' => $venta['transaction_id'],
        'tipo' => $venta['tipo'],
        'estado' => $venta['estado'],
        'monto_autor' => (float)$venta['monto_autor'],
        'monto_empresa' => (float)$venta['monto_empresa'],
        'porcentaje_autor' => (float)$venta['porcentaje_autor'],
        'porcentaje_empresa' => (float)$venta['porcentaje_empresa'],
        'precio_venta' => (float)$venta['precio_venta'],
        
        // Información del libro
        'libro_titulo' => $venta['libro_titulo'],
        'libro_precio' => (float)$venta['libro_precio'],
        
        // Información del escritor
        'escritor_nombre' => $venta['escritor_nombre'],
        'escritor_email' => $venta['escritor_email'],
        
        // Información del comprador
        'comprador_nombre' => $venta['comprador_nombre'],
        'comprador_email' => $venta['comprador_email'],
        
        // Información del afiliado
        'afiliado_nombre' => $venta['afiliado_nombre'],
        'afiliado_codigo' => $venta['afiliado_codigo'],
        
        // Comisiones
        'comisiones' => $comisiones,
        'total_comisiones' => array_sum(array_column($comisiones, 'monto'))
    ];

    jsonResponse([
        'success' => true,
        'venta' => $ventaFormateada
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo detalle de venta: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalle de venta: ' . $e->getMessage()
    ], 500);
}
?>
