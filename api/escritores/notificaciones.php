<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$user = getCurrentUser();
if ($user['rol'] !== 'escritor' && $user['rol'] !== 'admin') {
    jsonResponse(['error' => 'Acceso denegado'], 403);
}

try {
    $conn = getDBConnection();
    $escritor_id = null;
    if ($user['rol'] === 'escritor') {
        // Buscar el id del escritor por usuario
        $stmt = $conn->prepare('SELECT id FROM escritores WHERE usuario_id = ?');
        $stmt->execute([$user['id']]);
        $escritor = $stmt->fetch();
        if (!$escritor) {
            jsonResponse(['error' => 'Escritor no encontrado'], 404);
        }
        $escritor_id = $escritor['id'];
    } else if ($user['rol'] === 'admin') {
        // Permitir admin consultar cualquier escritor si se pasa por GET
        $escritor_id = isset($_GET['escritor_id']) ? intval($_GET['escritor_id']) : null;
        if (!$escritor_id) {
            jsonResponse(['error' => 'Falta escritor_id para admin'], 400);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener notificaciones
        $filtro_no_leidas = isset($_GET['no_leidas']) ? $_GET['no_leidas'] === 'true' : false;
        $where_clause = 'WHERE n.escritor_id = :escritor_id';
        if ($filtro_no_leidas) {
            $where_clause .= ' AND n.leida = 0';
        }
        $query = "
            SELECT 
                n.id,
                n.tipo,
                n.titulo,
                n.mensaje,
                n.fecha_creacion,
                n.leida,
                n.datos_adicionales
            FROM notificaciones_escritores n
            $where_clause
            ORDER BY n.fecha_creacion DESC
            LIMIT 50
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute(['escritor_id' => $escritor_id]);
        $notificaciones = $stmt->fetchAll();
        // Contar no leídas
        $count_query = 'SELECT COUNT(*) as no_leidas FROM notificaciones_escritores WHERE escritor_id = :escritor_id AND leida = 0';
        $stmt = $conn->prepare($count_query);
        $stmt->execute(['escritor_id' => $escritor_id]);
        $no_leidas = $stmt->fetch()['no_leidas'];
        echo json_encode([
            'success' => true,
            'notificaciones' => $notificaciones,
            'no_leidas' => $no_leidas
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Marcar como leída o marcar todas como leídas
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['marcar_todas']) && $input['marcar_todas']) {
            $query = 'UPDATE notificaciones_escritores SET leida = 1 WHERE escritor_id = :escritor_id AND leida = 0';
            $stmt = $conn->prepare($query);
            $stmt->execute(['escritor_id' => $escritor_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas'
            ]);
        } elseif (isset($input['notificacion_id'])) {
            $query = 'UPDATE notificaciones_escritores SET leida = 1 WHERE id = :notificacion_id AND escritor_id = :escritor_id';
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'notificacion_id' => $input['notificacion_id'],
                'escritor_id' => $escritor_id
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Actualizar configuración de notificaciones
        $input = json_decode(file_get_contents('php://input'), true);
        $query = "
            UPDATE escritores 
            SET 
                notif_ventas = :notif_ventas,
                notif_royalties = :notif_royalties,
                notif_comentarios = :notif_comentarios,
                notif_aprobacion = :notif_aprobacion,
                notif_email = :notif_email,
                notif_push = :notif_push
            WHERE id = :escritor_id
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'escritor_id' => $escritor_id,
            'notif_ventas' => $input['notif_ventas'] ?? 1,
            'notif_royalties' => $input['notif_royalties'] ?? 1,
            'notif_comentarios' => $input['notif_comentarios'] ?? 1,
            'notif_aprobacion' => $input['notif_aprobacion'] ?? 1,
            'notif_email' => $input['notif_email'] ?? 1,
            'notif_push' => $input['notif_push'] ?? 1
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Configuración de notificaciones actualizada'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 