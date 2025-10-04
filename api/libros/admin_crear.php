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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden crear libros'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    exit;
}

$titulo = trim($input['titulo'] ?? '');
$autor_id = (int)($input['autor_id'] ?? 0);
$descripcion = trim($input['descripcion'] ?? '');
$categoria = $input['categoria'] ?? '';
$precio = (float)($input['precio'] ?? 0);
$estado = $input['estado'] ?? 'pendiente_revision';

// Validaciones
if (empty($titulo) || empty($autor_id) || empty($categoria) || $precio < 0) {
    jsonResponse(['success' => false, 'error' => 'Todos los campos obligatorios deben estar completos'], 400);
    exit;
}

$estadosValidos = ['pendiente_revision', 'en_revision', 'correccion_autor', 'aprobado_autor', 'publicado', 'rechazado'];
if (!in_array($estado, $estadosValidos)) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    // Verificar que el autor existe y es escritor
    $stmt = $db->prepare("SELECT id, nombre FROM usuarios WHERE id = ? AND rol = 'escritor'");
    $stmt->execute([$autor_id]);
    $autor = $stmt->fetch();
    
    if (!$autor) {
        jsonResponse(['success' => false, 'error' => 'Autor no encontrado o no es escritor'], 400);
        exit;
    }
    
    // Verificar que no existe un libro con el mismo título del mismo autor
    $stmt = $db->prepare("SELECT id FROM libros WHERE titulo = ? AND autor_id = ?");
    $stmt->execute([$titulo, $autor_id]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Ya existe un libro con este título del mismo autor'], 400);
        exit;
    }
    
    // Crear el libro
    $stmt = $db->prepare("
        INSERT INTO libros (titulo, autor_id, descripcion, categoria, precio, precio_afiliado, comision_porcentaje, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Calcular precio afiliado (70% del precio normal)
    $precio_afiliado = $precio * 0.70;
    // Comisión por defecto 30%
    $comision_porcentaje = 30.00;
    
    $stmt->execute([$titulo, $autor_id, $descripcion, $categoria, $precio, $precio_afiliado, $comision_porcentaje, $estado]);
    $libroId = $db->lastInsertId();
    
    jsonResponse([
        'success' => true,
        'message' => 'Libro creado correctamente',
        'libro_id' => $libroId
    ]);
    
} catch (Exception $e) {
    error_log('Error creando libro: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error interno del servidor'
    ], 500);
}
?>
