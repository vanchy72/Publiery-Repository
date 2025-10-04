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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden revisar testimonios'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['testimonio_ids']) || !is_array($input['testimonio_ids']) || empty($input['testimonio_ids'])) {
    jsonResponse(['success' => false, 'error' => 'IDs de testimonios requeridos'], 400);
    exit;
}

$testimonioIds = array_map('intval', $input['testimonio_ids']);
$estado = $input['estado'] ?? 'aprobado';
$observaciones = $input['observaciones'] ?? '';
$marcarDestacados = isset($input['marcar_destacados']) && $input['marcar_destacados'];

// Validar estado
if (!in_array($estado, ['pendiente', 'aprobado', 'rechazado'])) {
    jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
    exit;
}

try {
    $db->beginTransaction();

    // Verificar que todos los testimonios existen y están pendientes
    $placeholders = implode(',', array_fill(0, count($testimonioIds), '?'));
    $stmt = $db->prepare("
        SELECT id, nombre, estado 
        FROM testimonios 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($testimonioIds);
    $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($testimonios) !== count($testimonioIds)) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Algunos testimonios no existen'], 404);
        exit;
    }

    // Verificar que todos están pendientes (para aprobación masiva)
    if ($estado === 'aprobado') {
        $testimoniosPendientes = array_filter($testimonios, function($t) {
            return $t['estado'] === 'pendiente';
        });

        if (count($testimoniosPendientes) !== count($testimonios)) {
            $db->rollBack();
            $testimoniosNoPendientes = array_filter($testimonios, function($t) {
                return $t['estado'] !== 'pendiente';
            });
            $nombres = array_map(function($t) { return $t['nombre']; }, $testimoniosNoPendientes);
            jsonResponse(['success' => false, 'error' => 'Hay testimonios que no están pendientes: ' . implode(', ', $nombres)], 400);
            exit;
        }
    }

    // Actualizar todos los testimonios
    $stmt = $db->prepare("
        UPDATE testimonios 
        SET estado = ?, 
            fecha_revision = NOW(), 
            admin_revisor_id = ?,
            observaciones_admin = ?,
            es_destacado = ?
        WHERE id IN ($placeholders)
    ");
    
    $params = array_merge(
        [$estado, $_SESSION['user_id'], $observaciones, $marcarDestacados ? 1 : 0],
        $testimonioIds
    );
    $stmt->execute($params);

    $db->commit();

    // Log de la acción para auditoría
    $nombres = array_map(function($t) { return $t['nombre']; }, $testimonios);
    error_log("Admin {$_SESSION['user_id']} procesó masivamente " . count($testimonioIds) . " testimonios como '{$estado}': " . implode(', ', $nombres));

    $resumen = [
        'testimonios_procesados' => count($testimonioIds),
        'estado_aplicado' => $estado,
        'marcados_destacados' => $marcarDestacados,
        'observaciones' => $observaciones
    ];

    jsonResponse([
        'success' => true,
        'message' => count($testimonioIds) . " testimonios procesados como '{$estado}' correctamente",
        'resumen' => $resumen,
        'testimonios' => array_values($testimonios)
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error procesando testimonios masivamente: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
