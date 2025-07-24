<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Verificar autenticación y permisos
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('No autenticado');
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT rol FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['rol'] !== 'admin') {
        throw new Exception('Solo administradores pueden eliminar testimonios');
    }

    // Obtener ID del testimonio a eliminar
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_POST['id'] ?? null);
    if (!$id) {
        throw new Exception('ID de testimonio requerido');
    }

    // Cargar el archivo JSON de testimonios
    $jsonFile = __DIR__ . '/testimonios.json';
    if (!file_exists($jsonFile)) {
        throw new Exception('Archivo de testimonios no encontrado');
    }
    $jsonContent = file_get_contents($jsonFile);
    $testimoniosData = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al parsear JSON de testimonios');
    }

    // Buscar y eliminar el testimonio
    $encontrado = false;
    foreach ($testimoniosData['testimonios'] as $i => $testimonio) {
        if ($testimonio['id'] == $id) {
            array_splice($testimoniosData['testimonios'], $i, 1);
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado) {
        throw new Exception('Testimonio no encontrado');
    }

    // Guardar cambios
    $resultado = file_put_contents($jsonFile, json_encode($testimoniosData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($resultado === false) {
        throw new Exception('Error al guardar los cambios');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Testimonio eliminado correctamente',
        'id' => $id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 