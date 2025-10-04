<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Verificar que hay una sesión activa
    if (!isset($_SESSION['user_id'])) {
        jsonResponse([
            'success' => false,
            'error' => 'No hay sesión activa',
            'redirect' => 'login.html'
        ], 401);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $db = getDBConnection();

    // Obtener datos completos del usuario (corregido: afiliados no tiene campo 'estado')
    $stmt = $db->prepare("
        SELECT u.*, a.codigo_afiliado, a.nivel, a.patrocinador_id, a.fecha_activacion
        FROM usuarios u 
        LEFT JOIN afiliados a ON u.id = a.usuario_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        jsonResponse([
            'success' => false,
            'error' => 'Usuario no encontrado'
        ], 404);
        exit;
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'user' => [
            'id' => (int)$userData['id'],
            'nombre' => $userData['nombre'],
            'email' => $userData['email'],
            'rol' => $userData['rol'],
            'estado' => $userData['estado'],
            'fecha_registro' => $userData['fecha_registro']
        ]
    ];

    // Si es afiliado, agregar datos específicos
    if ($userData['rol'] === 'afiliado' && $userData['codigo_afiliado']) {
        $response['user']['codigo_afiliado'] = $userData['codigo_afiliado'];
        $response['user']['nivel'] = (int)$userData['nivel'];
        $response['user']['patrocinador_id'] = $userData['patrocinador_id'];
        $response['user']['fecha_activacion'] = $userData['fecha_activacion'];
        // El estado del afiliado se determina por la fecha_activacion
        $response['user']['estado_afiliado'] = $userData['fecha_activacion'] ? 'activo' : 'pendiente';
    }

    jsonResponse($response);

} catch (Exception $e) {
    error_log('Error en verificar_sesion: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ], 500);
}
?>