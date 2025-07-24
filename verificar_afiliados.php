<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Estructura de la Tabla AFILIADOS</h2>";
    
    // Verificar estructura de la tabla afiliados
    $stmt = $pdo->query("DESCRIBE afiliados");
    $columnas_afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnas_afiliados);
    echo "</pre>";
    
    // Verificar algunos datos de ejemplo
    echo "<h3>Datos de Ejemplo:</h3>";
    $stmt = $pdo->query("SELECT * FROM afiliados LIMIT 3");
    $afiliados_ejemplo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($afiliados_ejemplo);
    echo "</pre>";
    
    // Verificar la relación entre escritores y usuarios
    echo "<h3>Relación Escritores-Usuarios:</h3>";
    $stmt = $pdo->query("
        SELECT 
            e.id as escritor_id,
            e.usuario_id,
            u.nombre as nombre_usuario,
            u.email
        FROM escritores e
        LEFT JOIN usuarios u ON e.usuario_id = u.id
    ");
    $relacion_escritores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($relacion_escritores);
    echo "</pre>";
    
    // Verificar libros con sus autores
    echo "<h3>Libros con Autores:</h3>";
    $stmt = $pdo->query("
        SELECT 
            l.id as libro_id,
            l.titulo,
            l.autor_id,
            l.estado,
            u.nombre as nombre_autor
        FROM libros l
        LEFT JOIN usuarios u ON l.autor_id = u.id
        LIMIT 5
    ");
    $libros_con_autores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($libros_con_autores);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 