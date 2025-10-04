<?php
/**
 * API para Rankings y Top Performers del Dashboard
 * Obtiene datos de rendimiento para mostrar mejores escritores, afiliados, libros y categorías
 * 
 * Endpoints:
 * GET /api/dashboard/rankings.php
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
    // Crear conexión a la base de datos
    $pdo = getDBConnection();
    
    $rankings = array();
    
    // 1. ESCRITOR DEL MES
    try {
        $mesActual = date('Y-m');
        // Primero obtener el escritor activo con libros publicados
        $stmt = $pdo->prepare("
            SELECT u.nombre, u.email, COUNT(l.id) as libros_mes
            FROM usuarios u 
            LEFT JOIN libros l ON u.id = l.autor_id 
                AND l.estado = 'publicado' 
                AND DATE_FORMAT(l.fecha_publicacion, '%Y-%m') = ?
            WHERE u.rol = 'escritor' AND u.estado = 'activo'
            GROUP BY u.id 
            ORDER BY libros_mes DESC, u.fecha_registro DESC
            LIMIT 1
        ");
        $stmt->execute([$mesActual]);
        $escritor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escritor) {
            $rankings['escritor_del_mes'] = array(
                'nombre' => $escritor['nombre'],
                'email' => $escritor['email'],
                'libros_publicados' => (int)$escritor['libros_mes'],
                'royalties_generados' => 0, // Sin sistema de ventas aún
                'royalties_formato' => '$0'
            );
        } else {
            $rankings['escritor_del_mes'] = array(
                'nombre' => 'Ninguno',
                'email' => '-',
                'libros_publicados' => 0,
                'royalties_generados' => 0,
                'royalties_formato' => '$0'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando escritor del mes: " . $e->getMessage());
        $rankings['escritor_del_mes'] = array(
            'nombre' => 'Error de consulta',
            'email' => '-',
            'libros_publicados' => 0,
            'royalties_generados' => 0,
            'royalties_formato' => '$0'
        );
    }
    
    // 2. AFILIADO DESTACADO DEL MES
    try {
        // Obtener afiliado activo (sin sistema de ventas por ahora)
        $stmt = $pdo->prepare("
            SELECT u.nombre, u.email, u.fecha_registro
            FROM usuarios u 
            WHERE u.rol = 'afiliado' AND u.estado = 'activo'
            ORDER BY u.fecha_registro DESC
            LIMIT 1
        ");
        $stmt->execute();
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($afiliado) {
            $rankings['afiliado_destacado'] = array(
                'nombre' => $afiliado['nombre'],
                'email' => $afiliado['email'],
                'ventas_referidas' => 0,
                'comisiones_generadas' => 0,
                'comisiones_formato' => '$0'
            );
        } else {
            $rankings['afiliado_destacado'] = array(
                'nombre' => 'Ninguno',
                'email' => '-',
                'ventas_referidas' => 0,
                'comisiones_generadas' => 0,
                'comisiones_formato' => '$0'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando afiliado destacado: " . $e->getMessage());
        $rankings['afiliado_destacado'] = array(
            'nombre' => 'Error de consulta',
            'email' => '-',
            'ventas_referidas' => 0,
            'comisiones_generadas' => 0,
            'comisiones_formato' => '$0'
        );
    }
    
    // 3. LIBRO MÁS VENDIDO DEL MES
    try {
        // Obtener libro publicado (sin ventas por ahora, mostrar el disponible)
        $stmt = $pdo->prepare("
            SELECT l.titulo, u.nombre as autor, l.precio, l.categoria
            FROM libros l
            LEFT JOIN usuarios u ON l.autor_id = u.id
            WHERE l.estado = 'publicado'
            ORDER BY l.fecha_publicacion DESC
            LIMIT 1
        ");
        $stmt->execute();
        $libro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($libro) {
            $rankings['libro_mas_vendido'] = array(
                'titulo' => $libro['titulo'],
                'autor' => $libro['autor'],
                'precio' => (float)$libro['precio'],
                'precio_formato' => '$' . number_format($libro['precio'], 0, '.', ','),
                'total_ventas' => 0, // Sin ventas aún
                'ingresos_generados' => 0,
                'ingresos_formato' => '$0',
                'categoria' => $libro['categoria'] ?: 'Sin categoría'
            );
        } else {
            $rankings['libro_mas_vendido'] = array(
                'titulo' => 'Ninguno',
                'autor' => '-',
                'precio' => 0,
                'precio_formato' => '$0',
                'total_ventas' => 0,
                'ingresos_generados' => 0,
                'ingresos_formato' => '$0',
                'categoria' => '-'
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando libro más vendido: " . $e->getMessage());
        $rankings['libro_mas_vendido'] = array(
            'titulo' => 'Error de consulta',
            'autor' => '-',
            'precio' => 0,
            'precio_formato' => '$0',
            'total_ventas' => 0,
            'ingresos_generados' => 0,
            'ingresos_formato' => '$0',
            'categoria' => '-'
        );
    }
    
    // 4. CATEGORÍA MÁS POPULAR
    try {
        $stmt = $pdo->prepare("
            SELECT l.categoria, COUNT(l.id) as total_libros, COUNT(v.id) as total_ventas
            FROM libros l
            LEFT JOIN ventas v ON l.id = v.libro_id
            WHERE l.estado = 'publicado' AND l.categoria IS NOT NULL AND l.categoria != ''
            GROUP BY l.categoria
            ORDER BY total_ventas DESC, total_libros DESC
            LIMIT 1
        ");
        $stmt->execute();
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($categoria) {
            $rankings['categoria_popular'] = array(
                'nombre' => $categoria['categoria'] ?: 'Sin categoría',
                'total_libros' => (int)$categoria['total_libros'],
                'total_ventas' => (int)$categoria['total_ventas']
            );
        } else {
            $rankings['categoria_popular'] = array(
                'nombre' => 'Sin categorías',
                'total_libros' => 0,
                'total_ventas' => 0
            );
        }
    } catch (Exception $e) {
        error_log("Error consultando categoría popular: " . $e->getMessage());
        $rankings['categoria_popular'] = array(
            'nombre' => 'Error',
            'total_libros' => 0,
            'total_ventas' => 0
        );
    }
    
    // Formatear respuesta
    $response = array(
        'success' => true,
        'rankings' => $rankings,
        'periodo' => array(
            'mes_actual' => date('Y-m'),
            'mes_nombre' => date('F Y'),
            'fecha_actualizacion' => date('Y-m-d H:i:s')
        ),
        'mensaje' => 'Rankings cargados correctamente',
        'timestamp' => time()
    );
    
    // Log para debugging
    error_log("Rankings Dashboard generados: " . json_encode($rankings));
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de conexión BD en rankings: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'mensaje' => 'No se pudieron cargar los rankings',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error general en rankings: " . $e->getMessage());
    
    $response = array(
        'success' => false,
        'error' => 'Error interno del servidor',
        'mensaje' => 'No se pudieron cargar los rankings',
        'debug' => array(
            'error_message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        )
    );
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>