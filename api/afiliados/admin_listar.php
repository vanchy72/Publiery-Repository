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
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden listar afiliados'], 403);
    exit;
}

try {
    // Consulta completa de afiliados con información del usuario y patrocinador
    $sql = "
        SELECT 
            a.id,
            a.usuario_id,
            a.codigo_afiliado,
            a.patrocinador_id,
            a.nivel,
            a.frontal,
            a.fecha_activacion,
            u.nombre,
            u.email,
            u.estado as estado_usuario,
            u.fecha_registro,
            pat.nombre as patrocinador_nombre
        FROM afiliados a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN afiliados pat_a ON a.patrocinador_id = pat_a.id
        LEFT JOIN usuarios pat ON pat_a.usuario_id = pat.id
        ORDER BY a.id DESC
    ";
    
    $stmt = $db->query($sql);
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los datos para el frontend
    $afiliadosFormateados = array_map(function($afiliado) {
        return [
            'id' => (int)$afiliado['id'],
            'usuario_id' => (int)$afiliado['usuario_id'],
            'nombre' => $afiliado['nombre'],
            'email' => $afiliado['email'],
            'codigo_afiliado' => $afiliado['codigo_afiliado'],
            'nivel' => (int)$afiliado['nivel'],
            'frontal' => (int)$afiliado['frontal'],
            'patrocinador_id' => $afiliado['patrocinador_id'] ? (int)$afiliado['patrocinador_id'] : null,
            'patrocinador_nombre' => $afiliado['patrocinador_nombre'],
            'fecha_activacion' => $afiliado['fecha_activacion'],
            'fecha_registro' => $afiliado['fecha_registro'],
            'estado_usuario' => $afiliado['estado_usuario']
        ];
    }, $afiliados);
    
    jsonResponse([
        'success' => true,
        'afiliados' => $afiliadosFormateados,
        'total' => count($afiliadosFormateados)
    ]);
    
} catch (Exception $e) {
    error_log('Error listando afiliados para admin: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener afiliados: ' . $e->getMessage()
    ], 500);
}
?>
