<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

function contraccionAfiliado($conn, $afiliado_id) {
    // Obtener datos del afiliado a eliminar
    $afiliado = $conn->prepare('SELECT id, patrocinador_id FROM afiliados WHERE id = ?');
    $afiliado->execute([$afiliado_id]);
    $af = $afiliado->fetch();
    if (!$af) return;
    $patrocinador_id = $af['patrocinador_id'];
    // Reasignar referidos directos al patrocinador
    $stmt = $conn->prepare('UPDATE afiliados SET patrocinador_id = ? WHERE patrocinador_id = ?');
    $stmt->execute([$patrocinador_id, $afiliado_id]);
    // Opcional: actualizar nivel, frontal, etc. según tu lógica de red
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['id'])) throw new Exception('ID de usuario no recibido');
    $usuario_id = intval($input['id']);
    $conn = getDBConnection();
    // Verificar si es afiliado
    $afiliado = $conn->prepare('SELECT id FROM afiliados WHERE usuario_id = ?');
    $afiliado->execute([$usuario_id]);
    $af = $afiliado->fetch();
    if ($af) {
        contraccionAfiliado($conn, $af['id']);
        // Eliminar afiliado
        $conn->prepare('DELETE FROM afiliados WHERE id = ?')->execute([$af['id']]);
    }
    // Eliminar usuario
    $conn->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$usuario_id]);
    echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 