<?php
/**
 * API para estadísticas agregadas del Dashboard
 * Consulta todas las fuentes de datos para obtener estadísticas reales y actualizadas
 * 
 * Endpoints:
 * GET /api/dashboard/estadisticas.php
 * 
 * @autor Publiery
 * @fecha 2025-09-21
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuración de base de datos
require_once __DIR__ . '/../../config/database.php';

try {
    // Crear conexión a la base de datos usando la función común
    $pdo = getDBConnection();
    
    $estadisticas = array();
    
    // 1. USUARIOS TOTALES
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado != 'eliminado'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['usuarios'] = (int)$result['total'];
    } catch (Exception $e) {
        error_log("Error consultando usuarios: " . $e->getMessage());
        $estadisticas['usuarios'] = 0;
    }
    
    // 2. LIBROS PUBLICADOS
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM libros WHERE estado = 'publicado'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['libros'] = (int)$result['total'];
    } catch (Exception $e) {
        error_log("Error consultando libros: " . $e->getMessage());
        $estadisticas['libros'] = 0;
    }
    
    // 3. CAMPAÑAS ACTIVAS
    try {
        // Verificar si existe la tabla campañas
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'campañas'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM campanas WHERE estado IN ('enviando', 'programada')");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $estadisticas['campanas'] = (int)$result['total'];
        } else {
            $estadisticas['campanas'] = 0;
        }
    } catch (Exception $e) {
        error_log("Error consultando campañas: " . $e->getMessage());
        $estadisticas['campanas'] = 0;
    }
    
    // 4. VENTAS DEL MES ACTUAL
    try {
        // Verificar si existe la tabla ventas
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'ventas'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $mesActual = date('Y-m');
            $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(precio), 0) as ingresos FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?");
            $stmt->execute([$mesActual]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $estadisticas['ventas_mes'] = (int)$result['total'];
            $estadisticas['ingresos_mes'] = (float)$result['ingresos'];
        } else {
            $estadisticas['ventas_mes'] = 0;
            $estadisticas['ingresos_mes'] = 0;
        }
    } catch (Exception $e) {
        error_log("Error consultando ventas: " . $e->getMessage());
        $estadisticas['ventas_mes'] = 0;
        $estadisticas['ingresos_mes'] = 0;
    }
    
    // 5. ESTADÍSTICAS ADICIONALES ÚTILES
    try {
        // Usuarios nuevos este mes
        $mesActual = date('Y-m');
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE DATE_FORMAT(fecha_registro, '%Y-%m') = ? AND estado != 'eliminado'");
        $stmt->execute([$mesActual]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['usuarios_nuevos_mes'] = (int)$result['total'];
        
        // Libros pendientes de revisión
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM libros WHERE estado IN ('pendiente', 'en_revision')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['libros_pendientes'] = (int)$result['total'];
        
        // Escritores activos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'escritor' AND estado = 'activo'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['escritores_activos'] = (int)$result['total'];
        
        // Afiliados activos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'afiliado' AND estado = 'activo'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadisticas['afiliados_activos'] = (int)$result['total'];
        
    } catch (Exception $e) {
        error_log("Error consultando estadísticas adicionales: " . $e->getMessage());
        $estadisticas['usuarios_nuevos_mes'] = 0;
        $estadisticas['libros_pendientes'] = 0;
        $estadisticas['escritores_activos'] = 0;
        $estadisticas['afiliados_activos'] = 0;
    }
    
    // Formatear respuesta
    $response = array(
        'success' => true,
        'estadisticas' => array(
            // Estadísticas principales del Dashboard
            'usuarios_total' => $estadisticas['usuarios'],
            'libros_publicados' => $estadisticas['libros'],
            'campanas_activas' => $estadisticas['campanas'],
            'ventas_mes' => $estadisticas['ventas_mes'],
            'ingresos_mes' => number_format($estadisticas['ingresos_mes'], 2, '.', ''),
            'ingresos_mes_formato' => '$' . number_format($estadisticas['ingresos_mes'], 0, '.', ','),
            
            // Estadísticas adicionales
            'usuarios_nuevos_mes' => $estadisticas['usuarios_nuevos_mes'],
            'libros_pendientes' => $estadisticas['libros_pendientes'],
            'escritores_activos' => $estadisticas['escritores_activos'],
            'afiliados_activos' => $estadisticas['afiliados_activos'],
            
            // Metadatos
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
            'mes_actual' => date('Y-m'),
            'mes_nombre' => date('F Y')
        ),
        'mensaje' => 'Estadísticas cargadas correctamente',
        'timestamp' => time()
    );
    
    // Log para debugging
    error_log("Estadísticas Dashboard generadas: " . json_encode($estadisticas));
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de conexión BD en estadísticas: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'mensaje' => 'No se pudieron cargar las estadísticas',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error general en estadísticas: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error interno del servidor',
        'mensaje' => 'No se pudieron cargar las estadísticas',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>