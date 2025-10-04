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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden ver testimonios'], 403);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(['success' => false, 'error' => 'ID de testimonio requerido'], 400);
    exit;
}

try {
    // Obtener testimonio
    $stmt = $db->prepare("
        SELECT 
            id,
            nombre,
            email,
            testimonio,
            calificacion,
            estado,
            fecha_envio,
            fecha_revision,
            admin_revisor_id,
            observaciones_admin,
            es_destacado
        FROM testimonios 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $testimonio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$testimonio) {
        jsonResponse(['success' => false, 'error' => 'Testimonio no encontrado'], 404);
        exit;
    }

    // Formatear datos del testimonio
    $testimonioFormateado = [
        'id' => (int)$testimonio['id'],
        'nombre' => $testimonio['nombre'],
        'email' => $testimonio['email'],
        'testimonio' => $testimonio['testimonio'],
        'calificacion' => (int)$testimonio['calificacion'],
        'estado' => $testimonio['estado'],
        'fecha_envio' => $testimonio['fecha_envio'],
        'fecha_revision' => $testimonio['fecha_revision'],
        'admin_revisor_id' => $testimonio['admin_revisor_id'] ? (int)$testimonio['admin_revisor_id'] : null,
        'observaciones_admin' => $testimonio['observaciones_admin'],
        'es_destacado' => (bool)$testimonio['es_destacado']
    ];

    jsonResponse([
        'success' => true,
        'testimonio' => $testimonioFormateado
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo testimonio: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener testimonio: ' . $e->getMessage()
    ], 500);
}
?>
