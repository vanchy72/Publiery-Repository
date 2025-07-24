<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>Creando Múltiples Ventas de Prueba</h1>";
    
    // Obtener datos
    $stmt = $pdo->query("SELECT id, titulo, precio FROM libros WHERE estado = 'publicado' LIMIT 3");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id, usuario_id FROM afiliados LIMIT 3");
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id, nombre FROM usuarios LIMIT 5");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Libros disponibles: " . count($libros) . "</p>";
    echo "<p>Afiliados disponibles: " . count($afiliados) . "</p>";
    echo "<p>Usuarios disponibles: " . count($usuarios) . "</p>";
    
    // Crear múltiples ventas
    $ventas_creadas = 0;
    $insert_query = "
        INSERT INTO ventas (
            libro_id, comprador_id, afiliado_id, total, 
            monto_autor, monto_empresa, fecha_venta, 
            precio_venta, porcentaje_autor, porcentaje_empresa
        ) VALUES (
            :libro_id, :comprador_id, :afiliado_id, :total,
            :monto_autor, :monto_empresa, :fecha_venta,
            :precio_venta, :porcentaje_autor, :porcentaje_empresa
        )
    ";
    
    $stmt = $pdo->prepare($insert_query);
    
    // Crear ventas con diferentes fechas
    $fechas = [
        date('Y-m-d H:i:s', strtotime('-30 days')),
        date('Y-m-d H:i:s', strtotime('-25 days')),
        date('Y-m-d H:i:s', strtotime('-20 days')),
        date('Y-m-d H:i:s', strtotime('-15 days')),
        date('Y-m-d H:i:s', strtotime('-10 days')),
        date('Y-m-d H:i:s', strtotime('-5 days')),
        date('Y-m-d H:i:s', strtotime('-2 days')),
        date('Y-m-d H:i:s', strtotime('-1 day')),
        date('Y-m-d H:i:s')
    ];
    
    foreach ($fechas as $index => $fecha) {
        $libro = $libros[$index % count($libros)];
        $afiliado = $afiliados[$index % count($afiliados)];
        $comprador = $usuarios[$index % count($usuarios)];
        
        $venta_data = [
            'libro_id' => $libro['id'],
            'comprador_id' => $comprador['id'],
            'afiliado_id' => $afiliado['id'],
            'total' => $libro['precio'],
            'monto_autor' => $libro['precio'] * 0.3,
            'monto_empresa' => $libro['precio'] * 0.25,
            'fecha_venta' => $fecha,
            'precio_venta' => $libro['precio'],
            'porcentaje_autor' => 30.00,
            'porcentaje_empresa' => 25.00
        ];
        
        try {
            $stmt->execute($venta_data);
            $ventas_creadas++;
            echo "<p>✅ Venta $ventas_creadas: {$libro['titulo']} - {$comprador['nombre']} - {$fecha}</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error en venta $index: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Resultado Final</h2>";
    echo "<p>Total de ventas creadas: $ventas_creadas</p>";
    
    // Verificar ventas totales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $total_ventas = $stmt->fetch()['total'];
    echo "<p>Total de ventas en la base de datos: $total_ventas</p>";
    
    // Verificar ventas por afiliado
    echo "<h3>Ventas por Afiliado:</h3>";
    $stmt = $pdo->query("
        SELECT 
            a.id as afiliado_id,
            u.nombre as afiliado_nombre,
            COUNT(v.id) as ventas,
            SUM(v.monto_autor) as ganancias
        FROM ventas v
        JOIN afiliados a ON v.afiliado_id = a.id
        JOIN usuarios u ON a.usuario_id = u.id
        GROUP BY a.id, u.nombre
        ORDER BY ganancias DESC
    ");
    $ventas_por_afiliado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($ventas_por_afiliado);
    echo "</pre>";
    
    echo "<p><a href='api/escritores/analytics.php?escritor_id=7&periodo=12'>Probar Analytics</a></p>";
    echo "<p><a href='dashboard-escritor-mejorado.html'>Ir al Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 