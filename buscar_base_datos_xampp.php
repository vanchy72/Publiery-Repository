<?php
echo "🔍 BUSCANDO BASE DE DATOS EN XAMPP\n";
echo "==================================\n\n";

// Conexión sin especificar base de datos
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexión a XAMPP MySQL exitosa\n\n";
    
    // Listar todas las bases de datos
    echo "📋 BASES DE DATOS DISPONIBLES:\n";
    echo "-------------------------------\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dbname = $row['Database'];
        if (!in_array($dbname, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
            $databases[] = $dbname;
            echo "- $dbname\n";
        }
    }
    
    echo "\n🔍 VERIFICANDO TABLAS EN CADA BASE DE DATOS:\n";
    echo "============================================\n";
    
    foreach ($databases as $dbname) {
        echo "\n📂 Base de datos: $dbname\n";
        echo str_repeat("-", 30) . "\n";
        
        try {
            $pdo->exec("USE $dbname");
            $stmt = $pdo->query("SHOW TABLES");
            $tables = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                echo "  (vacía)\n";
            } else {
                foreach ($tables as $table) {
                    echo "  - $table";
                    if (in_array($table, ['usuarios', 'libros', 'campanas', 'testimonios'])) {
                        echo " ⭐";
                    }
                    echo "\n";
                }
                
                // Si encontramos testimonios, verificar su contenido
                if (in_array('testimonios', $tables)) {
                    echo "\n  🎯 ¡TESTIMONIOS ENCONTRADA!\n";
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM testimonios");
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    echo "  📊 Total registros: $total\n";
                    
                    if ($total > 0) {
                        echo "  📋 Estructura:\n";
                        $stmt = $pdo->query("DESCRIBE testimonios");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo "  ❌ Error accediendo a $dbname: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "BÚSQUEDA COMPLETADA\n";
echo str_repeat("=", 50) . "\n";
?>