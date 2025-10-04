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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver detalles de comisiones'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de comisión requerido'], 400);
    exit;
}

try {
    // Obtener información completa de la comisión
    $sql = "
        SELECT 
            c.*,
            ua.nombre as afiliado_nombre,
            ua.email as afiliado_email,
            af.codigo_afiliado,
            v.total as venta_total,
            v.fecha_venta as venta_fecha,
            l.titulo as libro_titulo,
            uc.nombre as comprador_nombre,
            uc.email as comprador_email
        FROM comisiones c
        INNER JOIN afiliados af ON c.afiliado_id = af.id
        INNER JOIN usuarios ua ON af.usuario_id = ua.id
        INNER JOIN ventas v ON c.venta_id = v.id
        INNER JOIN libros l ON v.libro_id = l.id
        INNER JOIN usuarios uc ON v.comprador_id = uc.id
        WHERE c.id = ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $comision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comision) {
        jsonResponse(['success' => false, 'error' => 'Comisión no encontrada'], 404);
        exit;
    }

    // Formatear datos de la comisión
    $comisionFormateada = [
        'id' => (int)$comision['id'],
        'venta_id' => (int)$comision['venta_id'],
        'afiliado_id' => (int)$comision['afiliado_id'],
        'nivel' => (int)$comision['nivel'],
        'porcentaje' => (float)$comision['porcentaje'],
        'monto' => (float)$comision['monto'],
        'estado' => $comision['estado'],
        'fecha_generacion' => $comision['fecha_generacion'],
        'fecha_pago' => $comision['fecha_pago'],
        
        // Información del afiliado
        'afiliado_nombre' => $comision['afiliado_nombre'],
        'afiliado_email' => $comision['afiliado_email'],
        'codigo_afiliado' => $comision['codigo_afiliado'],
        
        // Información de la venta
        'venta_total' => (float)$comision['venta_total'],
        'venta_fecha' => $comision['venta_fecha'],
        'libro_titulo' => $comision['libro_titulo'],
        
        // Información del comprador
        'comprador_nombre' => $comision['comprador_nombre'],
        'comprador_email' => $comision['comprador_email']
    ];

    jsonResponse([
        'success' => true,
        'comision' => $comisionFormateada
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo detalle de comisión: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener detalle de comisión: ' . $e->getMessage()
    ], 500);
}
?>
