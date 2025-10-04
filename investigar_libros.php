<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🔍 INVESTIGANDO LIBROS EN LA BASE DE DATOS\n\n";
    
    // 1. Verificar todos los libros y sus estados
    $stmt = $conn->prepare("
        SELECT 
            l.id, 
            l.titulo, 
            l.estado, 
            l.fecha_publicacion,
            l.precio,
            l.precio_afiliado,
            u.nombre as autor_nombre,
            u.rol as autor_rol
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        ORDER BY l.id DESC
    ");
    $stmt->execute();
    $todos_libros = $stmt->fetchAll();
    
    echo "📚 TODOS LOS LIBROS EN LA BASE DE DATOS:\n";
    foreach ($todos_libros as $libro) {
        echo "ID: {$libro['id']} | {$libro['titulo']} | Estado: {$libro['estado']} | Autor: {$libro['autor_nombre']} ({$libro['autor_rol']})\n";
        echo "    Fecha pub: {$libro['fecha_publicacion']} | Precio: \${$libro['precio']} | Precio afiliado: \${$libro['precio_afiliado']}\n\n";
    }
    
    // 2. Verificar específicamente los publicados
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM libros 
        WHERE estado = 'publicado'
    ");
    $stmt->execute();
    $publicados = $stmt->fetch();
    
    echo "📊 RESUMEN:\n";
    echo "- Total libros: " . count($todos_libros) . "\n";
    echo "- Libros publicados: " . $publicados['total'] . "\n";
    
    // 3. Verificar los estados únicos que existen
    $stmt = $conn->prepare("
        SELECT estado, COUNT(*) as cantidad 
        FROM libros 
        GROUP BY estado
    ");
    $stmt->execute();
    $estados = $stmt->fetchAll();
    
    echo "\n📋 ESTADOS DE LIBROS:\n";
    foreach ($estados as $estado) {
        echo "- {$estado['estado']}: {$estado['cantidad']} libros\n";
    }
    
    // 4. Verificar si hay libros que deberían estar publicados pero no aparecen
    $stmt = $conn->prepare("
        SELECT 
            l.id, 
            l.titulo, 
            l.estado, 
            l.fecha_publicacion,
            l.precio,
            l.precio_afiliado,
            u.nombre as autor_nombre
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        WHERE l.estado != 'publicado' 
        AND l.fecha_publicacion IS NOT NULL
        ORDER BY l.fecha_publicacion DESC
    ");
    $stmt->execute();
    $potenciales = $stmt->fetchAll();
    
    if (count($potenciales) > 0) {
        echo "\n⚠️ LIBROS QUE PODRÍAN ESTAR PUBLICADOS:\n";
        foreach ($potenciales as $libro) {
            echo "ID: {$libro['id']} | {$libro['titulo']} | Estado actual: {$libro['estado']}\n";
            echo "    Autor: {$libro['autor_nombre']} | Fecha pub: {$libro['fecha_publicacion']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>