<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Creando Ventas de Prueba</h2>";
    
    // Verificar libros disponibles del escritor 7
    echo "<h3>Libros disponibles del escritor 7:</h3>";
    $stmt = $pdo->query("
        SELECT 
            l.id,
            l.titulo,
            l.precio,
            l.estado
        FROM libros l
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = 7 AND l.estado = 'publicado'
        ORDER BY l.id
    ");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($libros);
    echo "</pre>";
    
    // Verificar afiliados disponibles
    echo "<h3>Afiliados disponibles:</h3>";
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.usuario_id,
            u.nombre,
            a.codigo_afiliado
        FROM afiliados a
        JOIN usuarios u ON a.usuario_id = u.id
        LIMIT 5
    ");
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($afiliados);
    echo "</pre>";
    
    // Crear ventas de prueba
    if (!empty($libros) && !empty($afiliados)) {
        echo "<h3>Creando ventas de prueba...</h3>";
        
        // Ventas de prueba
        $ventas_prueba = [
            [
                'libro_id' => $libros[0]['id'],
                'comprador_id' => 1,
                'afiliado_id' => $afiliados[0]['id'],
                'total' => $libros[0]['precio'],
                'monto_autor' => $libros[0]['precio'] * 0.3,
                'monto_empresa' => $libros[0]['precio'] * 0.25,
                'fecha_venta' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'libro_id' => $libros[0]['id'],
                'comprador_id' => 2,
                'afiliado_id' => $afiliados[1]['id'],
                'total' => $libros[0]['precio'],
                'monto_autor' => $libros[0]['precio'] * 0.3,
                'monto_empresa' => $libros[0]['precio'] * 0.25,
                'fecha_venta' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'libro_id' => $libros[1]['id'],
                'comprador_id' => 3,
                'afiliado_id' => $afiliados[0]['id'],
                'total' => $libros[1]['precio'],
                'monto_autor' => $libros[1]['precio'] * 0.3,
                'monto_empresa' => $libros[1]['precio'] * 0.25,
                'fecha_venta' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
        
        $insert_query = "
            INSERT INTO ventas (
                libro_id, comprador_id, afiliado_id, total, 
                monto_autor, monto_empresa, fecha_venta, 
                precio_venta, porcentaje_autor, porcentaje_empresa
            ) VALUES (
                :libro_id, :comprador_id, :afiliado_id, :total,
                :monto_autor, :monto_empresa, :fecha_venta,
                :total, 30.00, 25.00
            )
        ";
        
        $stmt = $pdo->prepare($insert_query);
        $ventas_creadas = 0;
        
        foreach ($ventas_prueba as $venta) {
            try {
                $stmt->execute($venta);
                $ventas_creadas++;
                echo "<p>✅ Venta creada: Libro ID {$venta['libro_id']} - Afiliado ID {$venta['afiliado_id']}</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error creando venta: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>Resultado:</h3>";
        echo "<p>Se crearon $ventas_creadas ventas de prueba</p>";
        
        // Verificar ventas creadas
        echo "<h3>Ventas creadas:</h3>";
        $stmt = $pdo->query("
            SELECT 
                v.id,
                l.titulo,
                v.total,
                v.monto_autor,
                v.fecha_venta,
                u.nombre as afiliado_nombre
            FROM ventas v
            JOIN libros l ON v.libro_id = l.id
            JOIN afiliados a ON v.afiliado_id = a.id
            JOIN usuarios u ON a.usuario_id = u.id
            ORDER BY v.fecha_venta DESC
        ");
        $ventas_creadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($ventas_creadas);
        echo "</pre>";
        
    } else {
        echo "<p>❌ No hay libros o afiliados disponibles para crear ventas de prueba</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 