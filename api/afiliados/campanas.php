<?php
/**
 * API Gestión de Campañas para Afiliados - Solo Datos Reales
 * Usa la misma tabla 'campanas' que el panel de administración
 */

// Iniciar sesión para autenticación
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

try {
    $conn = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Verificar autenticación
        if (!isAuthenticated()) {
            jsonResponse(['error' => 'No autorizado'], 401);
        }

        $user = getCurrentUser();
        
        // Obtener campañas de la tabla 'campanas' con información completa
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.nombre,
                c.descripcion,
                c.tipo,
                c.audiencia_tipo,
                c.fecha_programada,
                c.fecha_creacion,
                c.estado,
                c.imagen_promocional,
                c.libro_ids,
                c.admin_creador_id,
                u.nombre as admin_creador_nombre,
                u.email as admin_creador_email
            FROM campanas c
            LEFT JOIN usuarios u ON c.admin_creador_id = u.id
            WHERE c.estado IN ('borrador', 'programada', 'enviando', 'completada')
            ORDER BY c.fecha_creacion DESC
        ");
        
        $stmt->execute();
        $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener información detallada de libros para cada campaña
        $campanasFormateadas = [];
        
        foreach ($campanas as $campana) {
            // Procesar libro_ids para obtener información completa de libros
            $librosDetalle = [];
            $enlaceCompra = 'tienda-lectores.html'; // Enlace por defecto a tienda general
            
            if (!empty($campana['libro_ids'])) {
                $librosIds = explode(',', $campana['libro_ids']);
                $librosIds = array_map('trim', $librosIds);
                $librosIds = array_filter($librosIds);
                
                if (!empty($librosIds)) {
                    // Obtener información detallada de los libros
                    $placeholders = str_repeat('?,', count($librosIds) - 1) . '?';
                    $stmtLibros = $conn->prepare("
                        SELECT id, titulo, precio, descripcion 
                        FROM libros 
                        WHERE id IN ($placeholders) 
                        AND estado = 'publicado'
                    ");
                    $stmtLibros->execute($librosIds);
                    $librosDetalle = $stmtLibros->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Si hay solo un libro, el enlace va directo a ese libro
                    if (count($librosDetalle) === 1) {
                        $enlaceCompra = 'tienda-lectores.html?libro=' . $librosDetalle[0]['id'];
                    }
                }
            }
            
            // Formatear para el dashboard de afiliados con toda la información
            $campanasFormateadas[] = [
                'id' => (int)$campana['id'],
                'nombre' => $campana['nombre'],
                'descripcion' => $campana['descripcion'],
                'tipo' => $campana['tipo'],
                'audiencia_tipo' => $campana['audiencia_tipo'],
                'estado' => $campana['estado'],
                'fecha_creacion' => $campana['fecha_creacion'],
                'fecha_programada' => $campana['fecha_programada'],
                
                // Imagen promocional
                'imagen_promocional' => $campana['imagen_promocional'],
                'imagen_url' => !empty($campana['imagen_promocional']) ? 
                    'uploads/campanas/' . $campana['imagen_promocional'] : null,
                
                // Información del creador
                'admin_creador' => $campana['admin_creador_nombre'],
                'admin_creador_email' => $campana['admin_creador_email'],
                
                // Libros relacionados (información completa)
                'libros_detalle' => $librosDetalle,
                'total_libros' => count($librosDetalle),
                'enlace_compra' => $enlaceCompra,
                
                // Campos para compatibilidad con dashboard existente
                'libro_ids' => !empty($librosIds) ? $librosIds : [],
                'libro_id' => !empty($librosDetalle) ? (int)$librosDetalle[0]['id'] : null,
                
                // Información adicional para afiliados
                'enlace_personalizado' => 'campana_' . $campana['id'],
                'objetivo_ventas' => 0,
                'ventas_generadas' => 0,
                'volumen_generado' => 0,
                'comisiones_generadas' => 0,
                
                // Mostrar información promocional
                'precio_original' => !empty($librosDetalle) ? (float)($librosDetalle[0]['precio'] ?? 0) : 0,
                'precio_afiliado' => !empty($librosDetalle) ? (float)($librosDetalle[0]['precio'] ?? 0) : 0,
                'descuento_porcentaje' => 0 // Sin descuentos por ahora
            ];
        }

        jsonResponse([
            'success' => true, 
            'campanas' => $campanasFormateadas,
            'total' => count($campanasFormateadas),
            'nota' => 'Datos reales desde tabla campanas'
        ]);
    }

    // Para métodos que modifican, requerir autenticación estricta
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }

    $user = getCurrentUser();
    
    // Solo admins pueden crear/modificar/eliminar campañas
    if ($user['rol'] !== 'admin') {
        jsonResponse(['error' => 'Solo los administradores pueden gestionar campañas'], 403);
    }

    switch ($method) {
        case 'POST':
            jsonResponse(['error' => 'Use el panel de administración para crear campañas'], 400);

        case 'PUT':
            jsonResponse(['error' => 'Use el panel de administración para editar campañas'], 400);

        case 'DELETE':
            jsonResponse(['error' => 'Use el panel de administración para eliminar campañas'], 400);

        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }

} catch (Exception $e) {
    error_log("Error en campañas afiliado: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
?>

    // Para métodos que modifican, requerir autenticación estricta
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }

    $user = getCurrentUser();
    if (!in_array($user['rol'], ['afiliado', 'admin'])) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }

    $userId = $user['id'];

    // Obtener ID del afiliado
    $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $afiliado = $stmt->fetch();
    
    if (!$afiliado && $user['rol'] !== 'admin') {
        jsonResponse(['error' => 'Debe ser un afiliado para gestionar campañas'], 403);
    }
    
    $afiliadoId = $afiliado ? $afiliado['id'] : null;

    switch ($method) {
        case 'POST':
            // Crear nueva campaña
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['nombre']) || !isset($data['descripcion'])) {
                jsonResponse(['error' => 'Nombre y descripción son requeridos'], 400);
            }

            $stmt = $conn->prepare("
                INSERT INTO campanas_afiliados (
                    afiliado_id, nombre, descripcion, objetivo_ventas, fecha_inicio, 
                    fecha_fin, estado, enlace_personalizado, libro_id
                ) VALUES (?, ?, ?, ?, ?, ?, 'activa', ?, ?)
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
                $enlacePersonalizado,
                $data['libro_id'] ?? null
            ]);

            $campanaId = $conn->lastInsertId();

            // Obtener la campaña creada
            $stmt = $conn->prepare("SELECT * FROM campanas_afiliados WHERE id = ?");
            $stmt->execute([$campanaId]);
            $nuevaCampana = $stmt->fetch(PDO::FETCH_ASSOC);

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

            $camposActualizables = ['nombre', 'descripcion', 'objetivo_ventas', 'fecha_inicio', 'fecha_fin', 'estado', 'libro_id'];
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
            $campanaActualizada = $stmt->fetch(PDO::FETCH_ASSOC);

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
    jsonResponse(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
?>