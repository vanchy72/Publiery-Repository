<?php
require_once 'config/database.php';
$pdo = getDBConnection();

echo "=== ESTRUCTURA DE TABLAS ===\n\n";

$tables = ['usuarios', 'afiliados', 'testimonios', 'libros', 'ventas', 'escritores'];

foreach ($tables as $table) {
    echo "Tabla: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while($row = $stmt->fetch()) { 
            echo "  " . $row['Field'] . " - " . $row['Type'] . "\n"; 
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>