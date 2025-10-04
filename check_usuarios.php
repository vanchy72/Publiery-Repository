<?php
require_once 'config/database.php';
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query('SHOW TABLES LIKE "usuarios"');
    if ($stmt->rowCount() > 0) {
        echo "✅ La tabla 'usuarios' EXISTE\n";
        
        // Mostrar estructura
        $stmt2 = $pdo->query('DESCRIBE usuarios');
        echo "Estructura de la tabla usuarios:\n";
        while($row = $stmt2->fetch()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "❌ La tabla 'usuarios' NO EXISTE\n";
        echo "Necesitamos crear datos directamente en las tablas existentes.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
