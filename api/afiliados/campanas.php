<?php
/**
 * API Gestión de Campañas para Afiliados
 * Permite crear, editar y gestionar campañas personalizadas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$user = getCurrentUser();
if ($user['rol'] !== 'afiliado' && $user['rol'] !== 'admin') {
    jsonResponse(['error' => 'Acceso denegado'], 403);
}

try {
    $conn = getDBConnection();
    $userId = $user['id'];
    $method = $_SERVER['REQUEST_METHOD'];

    // Obtener ID del afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado) {
        jsonResponse(['error' => 'Afiliado no encontrado'], 404);
    }
    
    $afiliadoId = $afiliado['id'];

    switch ($method) {
        case 'GET':
            // Obtener campañas del afiliado
            $stmt = $conn->prepare("
                SELECT 
                    c.*,
                    COUNT(DISTINCT v.id) as ventas_generadas,
                    SUM(v.total) as volumen_generado,
                    SUM(com.monto) as comisiones_generadas
                FROM campanas_afiliados c
                LEFT JOIN ventas v ON c.id = v.campana_id
                LEFT JOIN comisiones com ON v.id = com.venta_id AND com.afiliado_id = ?
                WHERE c.afiliado_id = ?
                GROUP BY c.id
                ORDER BY c.fecha_creacion DESC
            ");
            $stmt->execute([$afiliadoId, $afiliadoId]);
            $campanas = $stmt->fetchAll();

            jsonResponse(['success' => true, 'campanas' => $campanas]);

        case 'POST':
            // Crear nueva campaña
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['nombre']) || !isset($data['descripcion'])) {
                jsonResponse(['error' => 'Nombre y descripción son requeridos'], 400);
            }

            $stmt = $conn->prepare("
                INSERT INTO campanas_afiliados (
                    afiliado_id, nombre, descripcion, objetivo_ventas, 
                    fecha_inicio, fecha_fin, estado, enlace_personalizado
                ) VALUES (?, ?, ?, ?, ?, ?, 'activa', ?)
            ");

            $enlacePersonalizado = isset($data['enlace_personalizado']) 
                ? $data['enlace_personalizado'] 
                : "campana_" . time();

            $stmt->execute([
                $afiliadoId,
                $data['nombre'],
                $data['descripcion'],
                $data['objetivo_ventas'] ?? 0,
                $data['fecha_inicio'] ?? date('Y-m-d'),
                $data['fecha_fin'] ?? null,
                $enlacePersonalizado
            ]);

            $campanaId = $conn->lastInsertId();

            // Obtener la campaña creada
            $stmt = $conn->prepare("SELECT * FROM campanas_afiliados WHERE id = ?");
            $stmt->execute([$campanaId]);
            $nuevaCampana = $stmt->fetch();

            jsonResponse(['success' => true, 'campana' => $nuevaCampana], 201);

        case 'PUT':
            // Actualizar campaña existente
            $data = json_decode(file_get_contents('php://input'), true);
            $campanaId = $_GET['id'] ?? null;

            if (!$campanaId) {
                jsonResponse(['error' => 'ID de campaña requerido'], 400);
            }

            // Verificar que la campaña pertenece al afiliado
            $stmt = $conn->prepare("SELECT id FROM campanas_afiliados WHERE id = ? AND afiliado_id = ?");
            $stmt->execute([$campanaId, $afiliadoId]);
            if (!$stmt->fetch()) {
                jsonResponse(['error' => 'Campaña no encontrada'], 404);
            }

            $camposActualizables = ['nombre', 'descripcion', 'objetivo_ventas', 'fecha_inicio', 'fecha_fin', 'estado'];
            $updates = [];
            $valores = [];

            foreach ($camposActualizables as $campo) {
                if (isset($data[$campo])) {
                    $updates[] = "{$campo} = ?";
                    $valores[] = $data[$campo];
                }
            }

            if (empty($updates)) {
                jsonResponse(['error' => 'No hay campos para actualizar'], 400);
            }

            $valores[] = $campanaId;
            $valores[] = $afiliadoId;

            $sql = "UPDATE campanas_afiliados SET " . implode(', ', $updates) . " WHERE id = ? AND afiliado_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($valores);

            // Obtener la campaña actualizada
            $stmt = $conn->prepare("SELECT * FROM campanas_afiliados WHERE id = ?");
            $stmt->execute([$campanaId]);
            $campanaActualizada = $stmt->fetch();

            jsonResponse(['success' => true, 'campana' => $campanaActualizada]);

        case 'DELETE':
            // Eliminar campaña
            $campanaId = $_GET['id'] ?? null;

            if (!$campanaId) {
                jsonResponse(['error' => 'ID de campaña requerido'], 400);
            }

            // Verificar que la campaña pertenece al afiliado
            $stmt = $conn->prepare("SELECT id FROM campanas_afiliados WHERE id = ? AND afiliado_id = ?");
            $stmt->execute([$campanaId, $afiliadoId]);
            if (!$stmt->fetch()) {
                jsonResponse(['error' => 'Campaña no encontrada'], 404);
            }

            // Verificar que no hay ventas asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE campana_id = ?");
            $stmt->execute([$campanaId]);
            $ventasAsociadas = $stmt->fetch();

            if ($ventasAsociadas['total'] > 0) {
                jsonResponse(['error' => 'No se puede eliminar una campaña con ventas asociadas'], 400);
            }

            $stmt = $conn->prepare("DELETE FROM campanas_afiliados WHERE id = ? AND afiliado_id = ?");
            $stmt->execute([$campanaId, $afiliadoId]);

            jsonResponse(['success' => true, 'message' => 'Campaña eliminada correctamente']);

        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }

} catch (Exception $e) {
    error_log("Error en campañas afiliado: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?> 