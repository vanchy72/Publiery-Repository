<?php
// Endpoint para cambiar el estado de un libro (aprobado/publicado, rechazado, correcciones)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación y permisos
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT rol FROM usuarios WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || ($user['rol'] !== 'admin' && $user['rol'] !== 'editorial')) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$libro_id = $data['libro_id'] ?? null;
$nuevo_estado = $data['estado'] ?? null;
$comentario = $data['comentario'] ?? '';

$estados_validos = ['publicado', 'rechazado', 'correccion_autor', 'en_revision', 'aprobado_autor'];
if (!$libro_id || !in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    // Actualizar estado del libro
    $stmt = $conn->prepare('UPDATE libros SET estado = ?, comentarios_editorial = ? WHERE id = ?');
    $stmt->execute([$nuevo_estado, $comentario, $libro_id]);

    // Registrar la revisión en la tabla revisiones_libros
    $stmt = $conn->prepare('INSERT INTO revisiones_libros (libro_id, usuario_id, accion, comentario, fecha) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$libro_id, $_SESSION['user_id'], $nuevo_estado, $comentario]);

    echo json_encode(['success' => true, 'message' => 'Estado del libro actualizado y revisión registrada correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado: ' . $e->getMessage()]);
} 