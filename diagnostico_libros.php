<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

$ids = [8, 10, 11];

try {
    $conn = getDBConnection();
    echo "<h1>Diagnóstico de Libros (IDs: 8, 10, 11)</h1>";
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, titulo, imagen_portada FROM libros WHERE id IN ($in)");
    $stmt->execute($ids);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($libros) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>ID</th>
                <th>Título</th>
                <th>Imagen Portada</th>
                <th>Vista Previa</th>
              </tr>";
        foreach ($libros as $libro) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($libro['id']) . "</td>";
            echo "<td>" . htmlspecialchars($libro['titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($libro['imagen_portada']) . "</td>";
            echo "<td>";
            if ($libro['imagen_portada']) {
                echo "<img src='images/" . htmlspecialchars($libro['imagen_portada']) . "' style='height:60px;'>";
            } else {
                echo "<span style='color:red;'>Sin imagen</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>No se encontraron libros con esos IDs.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de base de datos: " . $e->getMessage() . "</p>";
}
?> 