<?php
require_once 'config/database.php';
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query('DESCRIBE ventas');
    echo "Columnas de la tabla 'ventas':\n";
    while($row = $stmt->fetch()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
