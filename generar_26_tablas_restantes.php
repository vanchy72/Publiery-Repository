<?php
echo "🚀 GENERANDO ESQUEMAS PARA 26 TABLAS RESTANTES\n";
echo "==============================================\n\n";

// XAMPP MySQL
$xampp_host = 'localhost';
$xampp_dbname = 'publiery_db';
$xampp_username = 'root';
$xampp_password = '';

// Tablas ya migradas
$tablas_migradas = ['usuarios', 'libros', 'campanas', 'testimonios', 'afiliados', 'escritores', 'configuracion', 'config_comisiones', 'red_afiliados'];

try {
    // Conectar a XAMPP
    echo "🔌 Conectando a XAMPP MySQL...\n";
    $xampp_pdo = new PDO("mysql:host=$xampp_host;dbname=$xampp_dbname;charset=utf8", $xampp_username, $xampp_password);
    $xampp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión XAMPP exitosa\n\n";
    
    // Obtener todas las tablas
    $stmt = $xampp_pdo->query("SHOW TABLES");
    $todas_tablas = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $todas_tablas[] = $row[0];
    }
    
    // Filtrar tablas pendientes
    $tablas_pendientes = array_diff($todas_tablas, $tablas_migradas);
    
    echo "📋 TABLAS RESTANTES A PROCESAR: " . count($tablas_pendientes) . "\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql_completo = "-- ===============================================\n";
    $sql_completo .= "-- ESQUEMAS PARA 26 TABLAS RESTANTES - PUBLIERY\n";
    $sql_completo .= "-- ===============================================\n\n";
    
    foreach ($tablas_pendientes as $i => $tabla) {
        echo ($i + 1) . ". Procesando: $tabla\n";
        
        // Contar registros
        $stmt = $xampp_pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener estructura
        $stmt = $xampp_pdo->query("DESCRIBE `$tabla`");
        $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_completo .= "-- ============================================\n";
        $sql_completo .= "-- TABLA: " . strtoupper($tabla) . " ($count registros)\n";
        $sql_completo .= "-- ============================================\n\n";
        
        // SQL de creación de tabla
        $sql_completo .= "CREATE TABLE IF NOT EXISTS $tabla (\n";
        $field_definitions = [];
        
        foreach ($estructura as $campo) {
            $field_name = $campo['Field'];
            $mysql_type = $campo['Type'];
            $is_null = $campo['Null'] == 'YES' ? '' : ' NOT NULL';
            $key = $campo['Key'];
            $default = $campo['Default'];
            
            // Convertir tipos MySQL a PostgreSQL
            $pg_type = $mysql_type;
            if (strpos($mysql_type, 'int') !== false) {
                if (strpos($mysql_type, 'bigint') !== false) {
                    $pg_type = 'BIGINT';
                } elseif (strpos($mysql_type, 'tinyint(1)') !== false) {
                    $pg_type = 'BOOLEAN';
                } elseif (strpos($mysql_type, 'tinyint') !== false) {
                    $pg_type = 'SMALLINT';
                } else {
                    $pg_type = 'INTEGER';
                }
            } elseif (strpos($mysql_type, 'varchar') !== false) {
                $pg_type = str_replace('varchar', 'VARCHAR', $mysql_type);
            } elseif (strpos($mysql_type, 'text') !== false) {
                $pg_type = 'TEXT';
            } elseif (strpos($mysql_type, 'timestamp') !== false) {
                $pg_type = 'TIMESTAMP';
            } elseif (strpos($mysql_type, 'datetime') !== false) {
                $pg_type = 'TIMESTAMP';
            } elseif (strpos($mysql_type, 'decimal') !== false) {
                $pg_type = str_replace('decimal', 'DECIMAL', $mysql_type);
            } elseif (strpos($mysql_type, 'enum') !== false) {
                // Extraer valores del enum
                preg_match("/enum\((.+)\)/", $mysql_type, $matches);
                if (isset($matches[1])) {
                    $pg_type = 'VARCHAR(100)'; // Simplificar enum a varchar
                } else {
                    $pg_type = 'VARCHAR(100)';
                }
            } elseif (strpos($mysql_type, 'json') !== false) {
                $pg_type = 'JSONB';
            } elseif (strpos($mysql_type, 'float') !== false) {
                $pg_type = 'REAL';
            } elseif (strpos($mysql_type, 'double') !== false) {
                $pg_type = 'DOUBLE PRECISION';
            }
            
            $field_def = "    $field_name $pg_type$is_null";
            
            // Agregar DEFAULT si existe
            if ($default !== null && $default !== '' && $default !== 'NULL') {
                if ($default === 'CURRENT_TIMESTAMP') {
                    $field_def .= ' DEFAULT CURRENT_TIMESTAMP';
                } elseif (is_numeric($default)) {
                    $field_def .= " DEFAULT $default";
                } else {
                    $field_def .= " DEFAULT '$default'";
                }
            }
            
            if ($key == 'PRI') {
                $field_def .= ' PRIMARY KEY';
            } elseif ($key == 'UNI') {
                $field_def .= ' UNIQUE';
            }
            
            $field_definitions[] = $field_def;
        }
        
        $sql_completo .= implode(",\n", $field_definitions) . "\n);\n\n";
        
        // Si tiene datos, agregar migración de datos
        if ($count > 0) {
            echo "   💾 Tabla con $count registros - agregando datos\n";
            
            $stmt = $xampp_pdo->query("SELECT * FROM `$tabla` LIMIT 100"); // Limitar para evitar tablas muy grandes
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($datos) > 0) {
                $sql_completo .= "-- Datos para $tabla\n";
                $sql_completo .= "DELETE FROM $tabla;\n\n";
                
                foreach ($datos as $registro) {
                    $columns = implode(', ', array_keys($registro));
                    $values = [];
                    foreach ($registro as $valor) {
                        if ($valor === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($valor)) {
                            $values[] = $valor;
                        } else {
                            // Escapar comillas simples y caracteres especiales
                            $valor_escapado = str_replace("'", "''", $valor);
                            $valor_escapado = str_replace("\\", "\\\\", $valor_escapado);
                            $values[] = "'" . $valor_escapado . "'";
                        }
                    }
                    $sql_completo .= "INSERT INTO $tabla ($columns) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql_completo .= "\n";
            }
        } else {
            echo "   📝 Tabla vacía - solo estructura\n";
        }
        
        $sql_completo .= "-- Verificar $tabla\n";
        $sql_completo .= "SELECT COUNT(*) as total_$tabla FROM $tabla;\n\n";
    }
    
    // SQL final de verificación
    $sql_completo .= "-- ===============================================\n";
    $sql_completo .= "-- VERIFICACIÓN FINAL DE TODAS LAS TABLAS\n";
    $sql_completo .= "-- ===============================================\n\n";
    
    foreach ($tablas_pendientes as $tabla) {
        $sql_completo .= "SELECT '$tabla' as tabla, COUNT(*) as registros FROM $tabla;\n";
    }
    
    // Guardar archivo
    file_put_contents('migracion_26_tablas_restantes.sql', $sql_completo);
    
    echo "\n🎉 ESQUEMAS GENERADOS EXITOSAMENTE\n";
    echo "==================================\n";
    echo "📄 Archivo: migracion_26_tablas_restantes.sql\n";
    echo "📊 Tablas procesadas: " . count($tablas_pendientes) . "\n";
    echo "💾 Tamaño: " . round(strlen($sql_completo) / 1024, 2) . " KB\n\n";
    
    // Mostrar resumen por tipo
    echo "📋 RESUMEN POR TIPO:\n";
    echo "====================\n";
    
    $tablas_con_datos = 0;
    $tablas_vacias = 0;
    
    foreach ($tablas_pendientes as $tabla) {
        $stmt = $xampp_pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($count > 0) {
            $tablas_con_datos++;
            echo "🔥 $tabla ($count registros)\n";
        } else {
            $tablas_vacias++;
        }
    }
    
    echo "\n📊 ESTADÍSTICAS:\n";
    echo "- Tablas con datos: $tablas_con_datos\n";
    echo "- Tablas vacías: $tablas_vacias\n";
    echo "- Total: " . count($tablas_pendientes) . "\n\n";
    
    echo "🚀 PRÓXIMO PASO:\n";
    echo "Ejecutar el SQL en Supabase para crear todas las tablas restantes\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "GENERACIÓN DE ESQUEMAS COMPLETADA\n";
echo str_repeat("=", 60) . "\n";
?>