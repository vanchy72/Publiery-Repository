<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// DEBUG: Log de sesión y usuario
file_put_contents(__DIR__.'/debug_sesion.txt',
    'PHPSESSID: '.(isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : 'NO COOKIE')."\n".
    'session_id(): '.session_id()."\n".
    'user_id: '.(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO SESSION')."\n".
    'getCurrentUser: '.(function_exists('getCurrentUser') ? print_r(getCurrentUser(), true) : 'NO FUNC')."\n",
    FILE_APPEND
);

// Verificar autenticación
if (!function_exists('isAuthenticated') || !isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    http_response_code(401);
    exit;
}

$user = getCurrentUser();
if (!in_array($user['rol'], ['escritor', 'admin', 'editorial'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    http_response_code(403);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Si es admin/editorial, mostrar todos los libros pendientes o en correcciones
    if (in_array($user['rol'], ['admin', 'editorial'])) {
        $libros_query = "
            SELECT 
                l.id,
                l.titulo,
                l.descripcion,
                l.precio,
                l.precio_afiliado,
                l.comision_porcentaje,
                l.estado,
                l.fecha_registro,
                l.imagen_portada,
                l.archivo_original as archivo_pdf,
                l.comentarios_editorial,
                u.nombre as autor_nombre
            FROM libros l
            LEFT JOIN usuarios u ON l.autor_id = u.id
            WHERE l.estado IN ('pendiente_revision', 'en_revision', 'correccion_autor')
            ORDER BY l.fecha_registro DESC
        ";
        $stmt = $conn->prepare($libros_query);
        $stmt->execute();
        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'libros' => $libros
        ]);
        exit;
    }

    // Si es escritor, obtener datos completos
    $autor_id = $user['id'];
    
    // Obtener el escritor_id correspondiente al usuario
    $escritor_id_query = "
        SELECT e.id as escritor_id
        FROM escritores e
        WHERE e.usuario_id = :autor_id
    ";
    $stmt = $conn->prepare($escritor_id_query);
    $stmt->execute(['autor_id' => $autor_id]);
    $escritor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escritor_info) {
        echo json_encode(['success' => false, 'error' => 'Escritor no encontrado']);
        exit;
    }
    
    $escritor_id = $escritor_info['escritor_id'];
    
    // 1. Información del escritor
    $escritor_query = "
        SELECT 
            e.id as escritor_id,
            u.id as usuario_id,
            u.nombre,
            u.email,
            u.fecha_registro,
            u.estado as cuenta_activa,
            u.biografia,
            u.foto
        FROM escritores e
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.id = :escritor_id
    ";
    $stmt = $conn->prepare($escritor_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Libros del escritor con ventas y ganancias
    $libros_query = "
        SELECT 
            l.id,
            l.titulo,
            l.descripcion,
            l.precio,
            l.precio_afiliado,
            l.comision_porcentaje,
            l.estado,
            l.fecha_registro,
            l.imagen_portada,
            l.archivo_original as archivo_pdf,
            l.comentarios_editorial,
            l.categoria,
            l.isbn,
            l.fecha_publicacion,
            COUNT(v.id) as ventas_totales,
            SUM(v.monto_autor) as ganancias_libro
        FROM libros l
        JOIN escritores e ON l.autor_id = e.usuario_id
        LEFT JOIN ventas v ON l.id = v.libro_id
        WHERE e.id = :escritor_id
        GROUP BY l.id, l.titulo, l.descripcion, l.precio, l.precio_afiliado, l.comision_porcentaje, l.estado, l.fecha_registro, l.imagen_portada, l.archivo_original, l.comentarios_editorial, l.categoria, l.isbn, l.fecha_publicacion
        ORDER BY l.fecha_registro DESC
    ";
    $stmt = $conn->prepare($libros_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Estadísticas de ventas
    $ventas_query = "
        SELECT 
            COUNT(*) as total_ventas,
            SUM(v.monto_autor) as ingresos_totales,
            AVG(v.monto_autor) as ganancia_promedio_por_venta,
            COUNT(DISTINCT v.libro_id) as libros_vendidos
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
    ";
    $stmt = $conn->prepare($ventas_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $estadisticas_ventas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Royalties del escritor
    $royalties_query = "
        SELECT 
            SUM(r.monto) as total_royalties,
            COUNT(*) as total_pagos,
            MAX(r.fecha_pago) as ultimo_pago
        FROM royalties r
        WHERE r.escritor_id = :escritor_id
    ";
    $stmt = $conn->prepare($royalties_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $royalties = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. Notificaciones no leídas
    $notificaciones_query = "
        SELECT COUNT(*) as no_leidas
        FROM notificaciones n
        WHERE n.usuario_id = :usuario_id AND n.leida = 0
    ";
    $stmt = $conn->prepare($notificaciones_query);
    $stmt->execute(['usuario_id' => $autor_id]);
    $notificaciones = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 6. Ventas por mes (últimos 12 meses)
    $ventas_mensuales_query = "
        SELECT 
            DATE_FORMAT(v.fecha_venta,'%Y-%m') as mes,
            COUNT(*) as ventas,
            SUM(v.monto_autor) as ganancias
        FROM ventas v
        INNER JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(v.fecha_venta, '%Y-%m')
        ORDER BY mes DESC
    ";
    $stmt = $conn->prepare($ventas_mensuales_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $ventas_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Libros más vendidos
    $libros_top_query = "
        SELECT 
            l.titulo,
            COUNT(v.id) as ventas,
            SUM(v.monto_autor) as ganancias_libro
        FROM libros l
        LEFT JOIN ventas v ON l.id = v.libro_id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        GROUP BY l.id, l.titulo
        HAVING ventas > 0
        ORDER BY ventas DESC, ganancias_libro DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($libros_top_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $libros_top = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Ventas recientes
    $ventas_recientes_query = "
        SELECT 
            v.id,
            l.titulo as libro_titulo,
            v.monto_autor as ganancia,
            v.fecha_venta
        FROM ventas v
        JOIN libros l ON v.libro_id = l.id
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = :escritor_id
        ORDER BY v.fecha_venta DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($ventas_recientes_query);
    $stmt->execute(['escritor_id' => $escritor_id]);
    $ventas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'escritor' => $escritor,
        'estadisticas' => [
            'total_libros' => count($libros),
            'libros_aprobados' => count(array_filter($libros, function($libro) { return $libro['estado'] === 'publicado'; })),
            'libros_pendientes' => count(array_filter($libros, function($libro) { return $libro['estado'] === 'pendiente_revision'; })),
            'total_ventas' => (int)$estadisticas_ventas['total_ventas'],
            'ganancias_totales' => (float)$estadisticas_ventas['ingresos_totales'],
            'ganancia_promedio_por_venta' => (float)$estadisticas_ventas['ganancia_promedio_por_venta'],
            'libros_vendidos' => (int)$estadisticas_ventas['libros_vendidos'],
            'total_royalties' => (float)$royalties['total_royalties'],
            'total_pagos_royalties' => (int)$royalties['total_pagos'],
            'notificaciones_no_leidas' => (int)$notificaciones['no_leidas']
        ],
        'libros' => $libros,
        'datos_graficos' => $ventas_mensuales,
        'libros_top' => $libros_top,
        'ventas_recientes' => $ventas_recientes,
        'royalties' => $royalties
    ];
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
    http_response_code(500);
} 