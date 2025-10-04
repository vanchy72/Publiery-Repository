<?php
/**
 * API Debug de Campañas para Afiliados
 * Versión con manejo robusto de errores
 */

// Capturar errores y warnings
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en salida
ini_set('log_errors', 1);

// Capturar cualquier salida no deseada
ob_start();

// Headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Función de respuesta JSON segura
function safeJsonResponse($data, $status = 200) {
    // Limpiar cualquier salida previa
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($status);
    
    // Asegurar que los datos sean válidos para JSON
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    
    if ($json === false) {
        // Si falla el JSON, enviar error básico
        $json = json_encode([
            'success' => false,
            'error' => 'Error de codificación JSON: ' . json_last_error_msg()
        ]);
    }
    
    echo $json;
    exit;
}

try {
    // Verificar archivos antes de incluirlos
    $required_files = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../config/auth_functions.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            safeJsonResponse([
                'success' => false,
                'error' => 'Archivo requerido no encontrado: ' . basename($file)
            ], 500);
        }
    }
    
    // Incluir archivos necesarios
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/auth_functions.php';
    
    // Verificar funciones necesarias
    if (!function_exists('getDBConnection')) {
        safeJsonResponse([
            'success' => false,
            'error' => 'Función getDBConnection no disponible'
        ], 500);
    }
    
    if (!function_exists('isAuthenticated')) {
        safeJsonResponse([
            'success' => false,
            'error' => 'Función isAuthenticated no disponible'
        ], 500);
    }
    
    $conn = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Para debug, no requerir autenticación por ahora
        /*
        if (!isAuthenticated()) {
            safeJsonResponse(['error' => 'No autorizado'], 401);
        }
        */
        
        // Verificar que la tabla existe
        $stmt = $conn->prepare("SHOW TABLES LIKE 'campanas'");
        $stmt->execute();
        $tablaExiste = $stmt->fetch();
        
        if (!$tablaExiste) {
            safeJsonResponse([
                'success' => false,
                'error' => 'Tabla campanas no existe en la base de datos'
            ], 500);
        }
        
        // Obtener campañas de la tabla 'campanas'
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
                u.nombre as admin_creador_nombre
            FROM campanas c
            LEFT JOIN usuarios u ON c.admin_creador_id = u.id
            WHERE c.estado IN ('borrador', 'programada', 'enviando', 'completada')
            ORDER BY c.fecha_creacion DESC
        ");
        
        $stmt->execute();
        $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar datos para el formato esperado por el dashboard
        $campanasFormateadas = [];
        
        foreach ($campanas as $campana) {
            // Convertir libro_ids de texto a array si existe
            $libros = [];
            if (!empty($campana['libro_ids'])) {
                $libros = explode(',', $campana['libro_ids']);
                $libros = array_map('trim', $libros);
                $libros = array_filter($libros);
            }
            
            // Formatear para el dashboard de afiliados
            $campanasFormateadas[] = [
                'id' => (int)$campana['id'],
                'nombre' => $campana['nombre'] ?? '',
                'descripcion' => $campana['descripcion'] ?? '',
                'tipo' => $campana['tipo'] ?? 'promocion',
                'audiencia_tipo' => $campana['audiencia_tipo'] ?? 'todos',
                'estado' => $campana['estado'] ?? 'borrador',
                'fecha_creacion' => $campana['fecha_creacion'] ?? '',
                'fecha_programada' => $campana['fecha_programada'] ?? null,
                'imagen_promocional' => $campana['imagen_promocional'] ?? null,
                'libro_ids' => $libros,
                'libro_id' => !empty($libros) ? (int)$libros[0] : null,
                'admin_creador' => $campana['admin_creador_nombre'] ?? 'Desconocido',
                
                // Campos adicionales para compatibilidad con dashboard
                'enlace_personalizado' => 'campana_' . $campana['id'],
                'objetivo_ventas' => 0,
                'ventas_generadas' => 0,
                'volumen_generado' => 0,
                'comisiones_generadas' => 0
            ];
        }

        safeJsonResponse([
            'success' => true, 
            'campanas' => $campanasFormateadas,
            'total' => count($campanasFormateadas),
            'debug_info' => [
                'tabla_existe' => true,
                'total_db' => count($campanas),
                'metodo' => $method,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    // Para otros métodos
    safeJsonResponse([
        'success' => false,
        'error' => 'Método ' . $method . ' no implementado en versión debug'
    ], 405);

} catch (PDOException $e) {
    safeJsonResponse([
        'success' => false,
        'error' => 'Error de base de datos',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    safeJsonResponse([
        'success' => false,
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}

// Si llegamos aquí, algo salió mal
safeJsonResponse([
    'success' => false,
    'error' => 'Flujo de ejecución inesperado'
], 500);
?>