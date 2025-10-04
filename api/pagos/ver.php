<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar autenticación simple
function isAdminAuth() {
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID de comisión requerido'], 400);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener detalles completos de la comisión
    $sql = "SELECT c.id, c.monto, c.tipo, c.porcentaje, c.nivel, c.estado, 
                   c.fecha_creacion, c.fecha_pago,
                   u.nombre as destinatario_nombre, u.email as destinatario_email,
                   v.fecha_venta, v.precio_venta, v.referencia_pago,
                   l.titulo as libro_titulo, l.precio as libro_precio,
                   a.codigo_afiliado,
                   au.nombre as autor_nombre
            FROM comisiones c 
            LEFT JOIN usuarios u ON c.usuario_id = u.id 
            LEFT JOIN ventas v ON c.venta_id = v.id 
            LEFT JOIN libros l ON v.libro_id = l.id 
            LEFT JOIN afiliados a ON u.id = a.usuario_id
            LEFT JOIN usuarios au ON l.autor_id = au.id
            WHERE c.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $comision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comision) {
        sendResponse(['success' => false, 'error' => 'Comisión no encontrada'], 404);
    }
    
    // Formatear datos para mostrar
    $pago = [
        'id' => $comision['id'],
        'afiliado_nombre' => $comision['destinatario_nombre'],
        'codigo_afiliado' => $comision['codigo_afiliado'],
        'monto' => number_format($comision['monto'], 2),
        'tipo' => $comision['tipo'] . ' (' . $comision['porcentaje'] . '% - Nivel ' . $comision['nivel'] . ')',
        'fecha' => $comision['fecha_pago'] ?: $comision['fecha_creacion'],
        'estado' => ucfirst($comision['estado']),
        'libro_titulo' => $comision['libro_titulo'],
        'autor_nombre' => $comision['autor_nombre'],
        'fecha_venta' => $comision['fecha_venta'],
        'precio_venta' => $comision['precio_venta'],
        'referencia_pago' => $comision['referencia_pago'],
        'notas' => "Comisión generada por venta del libro '{$comision['libro_titulo']}' el {$comision['fecha_venta']}"
    ];
    
    sendResponse([
        'success' => true,
        'pago' => $pago
    ]);

} catch (Exception $e) {
    error_log('Error al obtener detalles comisión: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener detalles: ' . $e->getMessage()
    ], 500);
}
?>
