<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creando Ventas de Prueba - Versión Simple</h1>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Configuración cargada</p>";
    
    $pdo = getDBConnection();
    echo "<p>✅ Conexión establecida</p>";
    
    // 1. Verificar libros
    echo "<h3>1. Verificando libros...</h3>";
    $stmt = $pdo->query("
        SELECT id, titulo, precio, estado 
        FROM libros 
        WHERE estado = 'publicado' 
        LIMIT 3
    ");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Libros encontrados: " . count($libros) . "</p>";
    echo "<pre>";
    print_r($libros);
    echo "</pre>";
    
    // 2. Verificar afiliados
    echo "<h3>2. Verificando afiliados...</h3>";
    $stmt = $pdo->query("
        SELECT id, usuario_id, codigo_afiliado 
        FROM afiliados 
        LIMIT 3
    ");
    $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Afiliados encontrados: " . count($afiliados) . "</p>";
    echo "<pre>";
    print_r($afiliados);
    echo "</pre>";
    
    // 2.5 Verificar usuarios disponibles para compradores
    echo "<h3>2.5 Verificando usuarios disponibles...</h3>";
    $stmt = $pdo->query("
        SELECT id, nombre, email 
        FROM usuarios 
        LIMIT 5
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Usuarios encontrados: " . count($usuarios) . "</p>";
    echo "<pre>";
    print_r($usuarios);
    echo "</pre>";
    
    // 3. Crear una venta simple
    if (!empty($libros) && !empty($afiliados) && !empty($usuarios)) {
        echo "<h3>3. Creando venta de prueba...</h3>";
        
        $libro = $libros[0];
        $afiliado = $afiliados[0];
        $comprador = $usuarios[0]; // Usar el primer usuario como comprador
        
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
        
        $venta_data = [
            'libro_id' => $libro['id'],
            'comprador_id' => $comprador['id'],
            'afiliado_id' => $afiliado['id'],
            'total' => $libro['precio'],
            'monto_autor' => $libro['precio'] * 0.3,
            'monto_empresa' => $libro['precio'] * 0.25,
            'fecha_venta' => date('Y-m-d H:i:s'),
            'precio_venta' => $libro['precio'],
            'porcentaje_autor' => 30.00,
            'porcentaje_empresa' => 25.00
        ];
        
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute($venta_data);
        
        echo "<p>✅ Venta creada exitosamente</p>";
        echo "<p>Libro: {$libro['titulo']}</p>";
        echo "<p>Comprador: {$comprador['nombre']} (ID: {$comprador['id']})</p>";
        echo "<p>Afiliado ID: {$afiliado['id']}</p>";
        echo "<p>Total: $" . number_format($libro['precio'], 2) . "</p>";
        
        // 4. Verificar la venta creada
        echo "<h3>4. Verificando venta creada...</h3>";
        $stmt = $pdo->query("
            SELECT 
                v.id,
                l.titulo,
                v.total,
                v.monto_autor,
                v.fecha_venta
            FROM ventas v
            JOIN libros l ON v.libro_id = l.id
            ORDER BY v.id DESC
            LIMIT 1
        ");
        $venta_creada = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($venta_creada);
        echo "</pre>";
        
    } else {
        echo "<p>❌ No hay libros o afiliados disponibles</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
}

echo "<p><a href='test_simple.php'>Volver a prueba simple</a></p>";
?> 