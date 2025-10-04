<?php
/**
 * API para obtener campañas compartidas por el admin para afiliados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth_functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

$userId = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }
    
    // Verificar que es afiliado o admin
    if (!in_array($user['rol'], ['afiliado', 'admin'])) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
    
    // Obtener datos del afiliado para generar enlaces
    $afiliadoData = null;
    if ($user['rol'] === 'afiliado') {
        $stmt = $conn->prepare("SELECT * FROM afiliados WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        $afiliadoData = $stmt->fetch();
        
        if (!$afiliadoData) {
            jsonResponse(['error' => 'Perfil de afiliado no encontrado'], 404);
        }
    }
    
    // Obtener campañas compartidas por el admin
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.nombre,
            c.descripcion,
            c.tipo,
            c.imagen_promocional,
            c.libro_ids,
            c.fecha_compartida,
            c.fecha_creacion,
            c.estado,
            c.compartida_red,
            GROUP_CONCAT(l.id, ':', l.titulo, ':', l.precio_afiliado SEPARATOR '|') as libros_info
        FROM campanas c
        LEFT JOIN libros l ON FIND_IN_SET(l.id, c.libro_ids) > 0
        WHERE c.compartida_red = 1 
        AND c.estado IN ('enviando', 'programada', 'completada')
        GROUP BY c.id, c.nombre, c.descripcion, c.tipo, c.imagen_promocional, c.libro_ids, c.fecha_compartida, c.fecha_creacion, c.estado, c.compartida_red
        ORDER BY c.fecha_compartida DESC, c.fecha_creacion DESC
    ");
    $stmt->execute();
    $campanasRaw = $stmt->fetchAll();
    
    // Procesar campañas para el frontend
    $campanas = [];
    foreach ($campanasRaw as $campana) {
        $procesada = [
            'id' => $campana['id'],
            'nombre' => $campana['nombre'],
            'descripcion' => $campana['descripcion'],
            'tipo' => $campana['tipo'],
            'imagen_promocional' => $campana['imagen_promocional'],
            'fecha_compartida' => $campana['fecha_compartida'],
            'fecha_creacion' => $campana['fecha_creacion'],
            'libros' => [],
            'enlaces_afiliado' => []
        ];
        
        // Procesar libros asociados
        if ($campana['libros_info']) {
            $librosInfo = explode('|', $campana['libros_info']);
            foreach ($librosInfo as $libroInfo) {
                $partes = explode(':', $libroInfo);
                if (count($partes) >= 3) {
                    $libroId = $partes[0];
                    $titulo = $partes[1];
                    $precio = $partes[2];
                    
                    $procesada['libros'][] = [
                        'id' => $libroId,
                        'titulo' => $titulo,
                        'precio_afiliado' => $precio
                    ];
                    
                    // Generar enlace de afiliado si el usuario es afiliado
                    if ($afiliadoData) {
                        $procesada['enlaces_afiliado'][] = [
                            'libro_id' => $libroId,
                            'titulo' => $titulo,
                            'enlace' => "pago.html?libro={$libroId}&ref={$afiliadoData['codigo_afiliado']}&campaign=admin_{$campana['id']}"
                        ];
                    }
                }
            }
        }
        
        $campanas[] = $procesada;
    }
    
    jsonResponse([
        'success' => true,
        'campanas' => $campanas,
        'total' => count($campanas),
        'usuario_afiliado' => $afiliadoData ? $afiliadoData['codigo_afiliado'] : null
    ]);
    
} catch (Exception $e) {
    error_log("Error obteniendo campañas admin: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}
?>