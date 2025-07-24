<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Verificación de Categorías</h2>";
    
    // Verificar categorías de libros del escritor 7
    echo "<h3>Categorías de libros del escritor 7:</h3>";
    $stmt = $pdo->query("
        SELECT 
            l.id,
            l.titulo,
            l.categoria,
            l.estado
        FROM libros l
        JOIN escritores e ON l.autor_id = e.usuario_id
        WHERE e.id = 7
        ORDER BY l.id
    ");
    $libros_categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($libros_categorias);
    echo "</pre>";
    
    // Verificar si hay categorías definidas en la base de datos
    echo "<h3>Categorías únicas en la base de datos:</h3>";
    $stmt = $pdo->query("
        SELECT DISTINCT categoria 
        FROM libros 
        WHERE categoria IS NOT NULL AND categoria != ''
        ORDER BY categoria
    ");
    $categorias_unicas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($categorias_unicas);
    echo "</pre>";
    
    // Proponer categorías para los libros sin categoría
    echo "<h3>Propuesta de categorías para libros sin categoría:</h3>";
    $libros_sin_categoria = array_filter($libros_categorias, function($libro) {
        return empty($libro['categoria']);
    });
    
    if (!empty($libros_sin_categoria)) {
        echo "<p>Libros que necesitan categoría:</p>";
        echo "<ul>";
        foreach ($libros_sin_categoria as $libro) {
            echo "<li>ID {$libro['id']}: {$libro['titulo']}</li>";
        }
        echo "</ul>";
        
        echo "<p>Comandos SQL para asignar categorías:</p>";
        echo "<pre>";
        echo "-- Asignar categorías a los libros\n";
        echo "UPDATE libros SET categoria = 'Autoayuda' WHERE id = 8;\n";
        echo "UPDATE libros SET categoria = 'Autoayuda' WHERE id = 10;\n";
        echo "UPDATE libros SET categoria = 'Autoayuda' WHERE id = 12;\n";
        echo "UPDATE libros SET categoria = 'Autoayuda' WHERE id = 13;\n";
        echo "</pre>";
    } else {
        echo "<p>✅ Todos los libros tienen categoría asignada</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 