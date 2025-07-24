<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Estructura de las Tablas</h2>";
    
    // Verificar estructura de la tabla ventas
    echo "<h3>Tabla VENTAS:</h3>";
    $stmt = $pdo->query("DESCRIBE ventas");
    $columnas_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnas_ventas);
    echo "</pre>";
    
    // Verificar estructura de la tabla libros
    echo "<h3>Tabla LIBROS:</h3>";
    $stmt = $pdo->query("DESCRIBE libros");
    $columnas_libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnas_libros);
    echo "</pre>";
    
    // Verificar estructura de la tabla escritores
    echo "<h3>Tabla ESCRITORES:</h3>";
    $stmt = $pdo->query("DESCRIBE escritores");
    $columnas_escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnas_escritores);
    echo "</pre>";
    
    // Verificar algunos datos de ejemplo
    echo "<h3>Datos de Ejemplo:</h3>";
    
    echo "<h4>Primeras 3 ventas:</h4>";
    $stmt = $pdo->query("SELECT * FROM ventas LIMIT 3");
    $ventas_ejemplo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($ventas_ejemplo);
    echo "</pre>";
    
    echo "<h4>Primeros 3 libros:</h4>";
    $stmt = $pdo->query("SELECT * FROM libros LIMIT 3");
    $libros_ejemplo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($libros_ejemplo);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 