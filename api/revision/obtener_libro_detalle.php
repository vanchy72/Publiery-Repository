<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación de admin (permitir localhost para testing)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isAuthenticated = isset($_SESSION['email']) && $_SESSION['rol'] === 'admin';

if (!$isAuthenticated && !$isLocalhost) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$libroId = $_GET['id'] ?? '';

if (empty($libroId)) {
    echo json_encode(['success' => false, 'error' => 'ID de libro requerido']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Obtener detalles completos del libro
    $sql = "
        SELECT 
            l.*,
            u.nombre as autor_nombre,
            u.email as autor_email,
            u.biografia as autor_biografia,
            u.cuenta_payu as autor_cuenta_payu,
            (SELECT COUNT(*) FROM ventas v WHERE v.libro_id = l.id) as total_ventas,
            (SELECT SUM(v.total) FROM ventas v WHERE v.libro_id = l.id) as ingresos_totales,
            (SELECT COUNT(*) FROM ventas v WHERE v.libro_id = l.id AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as ventas_ultimo_mes
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        WHERE l.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        echo json_encode(['success' => false, 'error' => 'Libro no encontrado']);
        exit;
    }
    
    // Obtener historial de cambios de estado
    $historialSql = "
        SELECT 
            sl.id,
            sl.estado_anterior,
            sl.estado_nuevo,
            sl.comentario,
            sl.fecha_cambio,
            sl.admin_id,
            ua.nombre as admin_nombre
        FROM system_logs sl
        LEFT JOIN usuarios ua ON sl.admin_id = ua.id
        WHERE sl.libro_id = ? 
        ORDER BY sl.fecha_cambio DESC
        LIMIT 20
    ";
    
    try {
        $historialStmt = $pdo->prepare($historialSql);
        $historialStmt->execute([$libroId]);
        $historial = $historialStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Si no existe la tabla system_logs, crear historial simulado
        $historial = [
            [
                'estado_anterior' => null,
                'estado_nuevo' => $libro['estado'],
                'comentario' => 'Estado actual del libro',
                'fecha_cambio' => $libro['fecha_registro'],
                'admin_nombre' => 'Sistema'
            ]
        ];
    }
    
    // Obtener ventas recientes del libro
    $ventasSql = "
        SELECT
            v.id,
            v.fecha_venta,
            v.total,
            v.cantidad,
            v.tipo,
            uc.nombre as comprador_nombre,
            uc.email as comprador_email
        FROM ventas v
        LEFT JOIN usuarios uc ON v.comprador_id = uc.id
        WHERE v.libro_id = ?
        ORDER BY v.fecha_venta DESC
        LIMIT 10
    ";

    $ventasStmt = $pdo->prepare($ventasSql);
    $ventasStmt->execute([$libroId]);
    $ventasRecientes = $ventasStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener información de archivos
    $archivos = [
        'original' => null,
        'correcciones' => []
    ];

    // Archivo original (si existe)
    if (!empty($libro['archivo_original'])) {
        $originalPath = '../../uploads/libros/' . $libro['archivo_original'];
        if (file_exists($originalPath)) {
            $archivos['original'] = [
                'nombre' => $libro['archivo_original'],
                'tamano' => filesize($originalPath),
                'tipo' => pathinfo($libro['archivo_original'], PATHINFO_EXTENSION),
                'fecha' => date('d/m/Y H:i', filemtime($originalPath)),
                'url' => 'uploads/libros/' . $libro['archivo_original']
            ];
        }
    }

    // Obtener historial de correcciones
    try {
        $correccionesSql = "
            SELECT
                cl.id,
                cl.archivo_correccion,
                cl.comentarios,
                cl.fecha_subida,
                ua.nombre as admin_nombre
            FROM correcciones_libros cl
            LEFT JOIN usuarios ua ON cl.admin_id = ua.id
            WHERE cl.libro_id = ?
            ORDER BY cl.fecha_subida DESC
            LIMIT 20
        ";

        $correccionesStmt = $pdo->prepare($correccionesSql);
        $correccionesStmt->execute([$libroId]);
        $correcciones = $correccionesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($correcciones as $correccion) {
            $correccionPath = '../../uploads/correcciones/' . $correccion['archivo_correccion'];
            if (file_exists($correccionPath)) {
                $archivos['correcciones'][] = [
                    'id' => $correccion['id'],
                    'nombre' => $correccion['archivo_correccion'],
                    'comentarios' => $correccion['comentarios'],
                    'tamano' => filesize($correccionPath),
                    'tipo' => pathinfo($correccion['archivo_correccion'], PATHINFO_EXTENSION),
                    'fecha' => date('d/m/Y H:i', strtotime($correccion['fecha_subida'])),
                    'admin' => $correccion['admin_nombre'] ?: 'Administrador',
                    'url' => 'uploads/correcciones/' . $correccion['archivo_correccion']
                ];
            }
        }
    } catch (Exception $e) {
        // Si no existe la tabla correcciones_libros, continuar sin ella
        error_log("Tabla correcciones_libros no existe: " . $e->getMessage());
    }
    
    // Formatear datos
    $libro['precio_formateado'] = number_format($libro['precio'], 0, ',', '.');
    $libro['precio_afiliado_formateado'] = number_format($libro['precio_afiliado'], 0, ',', '.');
    $libro['ingresos_totales_formateado'] = $libro['ingresos_totales'] ? number_format($libro['ingresos_totales'], 0, ',', '.') : '0';
    
    // Formatear fechas
    $libro['fecha_registro_formateada'] = $libro['fecha_registro'] ? date('d/m/Y H:i', strtotime($libro['fecha_registro'])) : null;
    $libro['fecha_revision_formateada'] = $libro['fecha_revision'] ? date('d/m/Y H:i', strtotime($libro['fecha_revision'])) : null;
    $libro['fecha_aprobacion_autor_formateada'] = $libro['fecha_aprobacion_autor'] ? date('d/m/Y H:i', strtotime($libro['fecha_aprobacion_autor'])) : null;
    $libro['fecha_publicacion_formateada'] = $libro['fecha_publicacion'] ? date('d/m/Y H:i', strtotime($libro['fecha_publicacion'])) : null;
    
    // Calcular tiempo en estado actual
    $fechaUltimoCambio = $libro['fecha_revision'] ?: $libro['fecha_registro'];
    if ($fechaUltimoCambio) {
        $diasEnEstado = floor((time() - strtotime($fechaUltimoCambio)) / (24 * 60 * 60));
        $libro['dias_en_estado'] = $diasEnEstado;
    }
    
    // Formatear historial
    foreach ($historial as &$item) {
        $item['fecha_cambio_formateada'] = $item['fecha_cambio'] ? date('d/m/Y H:i', strtotime($item['fecha_cambio'])) : null;
    }
    
    // Formatear ventas
    foreach ($ventasRecientes as &$venta) {
        $venta['fecha_venta_formateada'] = $venta['fecha_venta'] ? date('d/m/Y H:i', strtotime($venta['fecha_venta'])) : null;
        $venta['total_formateado'] = number_format($venta['total'], 0, ',', '.');
    }
    
    // Determinar acciones disponibles según el estado
    $accionesDisponibles = [];
    switch ($libro['estado']) {
        case 'pendiente_revision':
            $accionesDisponibles = ['iniciar_revision', 'rechazar'];
            break;
        case 'en_revision':
            $accionesDisponibles = ['aprobar', 'solicitar_correccion', 'rechazar'];
            break;
        case 'correccion_autor':
            $accionesDisponibles = ['aprobar', 'rechazar'];
            break;
        case 'aprobado_autor':
            $accionesDisponibles = ['publicar', 'rechazar'];
            break;
        case 'publicado':
            $accionesDisponibles = ['despublicar'];
            break;
        case 'rechazado':
            $accionesDisponibles = ['revisar_nuevamente'];
            break;
    }
    
    echo json_encode([
        'success' => true,
        'libro' => $libro,
        'historial' => $historial,
        'ventas_recientes' => $ventasRecientes,
        'archivos' => $archivos,
        'acciones_disponibles' => $accionesDisponibles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
