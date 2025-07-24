<?php
/**
 * API para descargar libros
 */

require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
$user = getCurrentUser();

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }
    
    $ventaId = $input['venta_id'] ?? null;
    $libroId = $input['libro_id'] ?? null;
    
    if (!$ventaId || !$libroId) {
        throw new Exception('Faltan datos requeridos');
    }
    
    $conn = getDBConnection();
    
    // Verificar que la venta existe y está pagada
    $stmt = $conn->prepare("
        SELECT v.*, l.archivo_pdf, l.titulo, l.autor_id 
        FROM ventas v 
        JOIN libros l ON v.libro_id = l.id 
        WHERE v.id = ? AND v.libro_id = ? AND v.estado = 'pagado'
    ");
    $stmt->execute([$ventaId, $libroId]);
    $venta = $stmt->fetch();
    
    if (!$venta) {
        throw new Exception('Venta no encontrada o no pagada');
    }
    
    // Permitir solo al comprador, afiliado, autor o admin
    $permitido = (
        $user['rol'] === 'admin' ||
        $user['id'] == $venta['comprador_id'] ||
        $user['id'] == $venta['afiliado_id'] ||
        $user['id'] == $venta['autor_id']
    );
    if (!$permitido) {
        throw new Exception('No autorizado para descargar este libro');
    }
    
    // Verificar que el archivo existe
    $archivoPath = '../../uploads/libros/' . $venta['archivo_pdf'];
    
    if (!file_exists($archivoPath)) {
        throw new Exception('Archivo del libro no encontrado');
    }
    
    // Verificar número de descargas (máximo 3)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM descargas WHERE venta_id = ?");
    $stmt->execute([$ventaId]);
    $descargas = $stmt->fetch();
    
    if ($descargas['total'] >= 3) {
        throw new Exception('Has alcanzado el límite de descargas (3)');
    }
    
    // Registrar la descarga
    $stmt = $conn->prepare("
        INSERT INTO descargas (venta_id, afiliado_id, libro_id, ip_descarga, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $ventaId,
        $venta['afiliado_id'],
        $libroId,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Actualizar fecha de descarga en la venta
    $stmt = $conn->prepare("UPDATE ventas SET fecha_descarga = NOW() WHERE id = ?");
    $stmt->execute([$ventaId]);
    
    // Configurar headers para descarga
    $fileName = $venta['titulo'] . '.pdf';
    $fileName = preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', $fileName); // Limpiar nombre
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($archivoPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Enviar archivo
    readfile($archivoPath);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 