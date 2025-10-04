<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    echo "=== ANÁLISIS DE DATOS ANTES DE LIMPIEZA ===\n\n";

    // Verificar datos actuales
    $tablas = ['usuarios', 'afiliados', 'testimonios', 'libros', 'ventas', 'escritores'];
    $datos_antes = [];

    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $datos_antes[$tabla] = $result['total'];
        echo "$tabla: {$result['total']} registros\n";
    }

    echo "\n=== DATOS ORIGINALES A PRESERVAR ===\n\n";

    // Usuarios originales (los que no tienen patrones de prueba)
    $usuarios_originales = [
        'admin@publiery.com',
        'publierycompany@gmail.com', 
        'doral_23@hotmail.com',
        'correoivanortega@gmail.com'
    ];

    echo "USUARIOS ORIGINALES A PRESERVAR:\n";
    foreach ($usuarios_originales as $email) {
        $stmt = $pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            echo "  ✅ ID {$usuario['id']}: {$usuario['nombre']} ({$usuario['email']})\n";
        }
    }

    echo "\n=== IDENTIFICACIÓN DE DATOS DE PRUEBA ===\n\n";

    // Buscar testimonios que yo pude haber insertado
    // Los archivos insertar_datos_*.php eliminan todo y reemplazan con datos de prueba
    
    // Verificar testimonios específicos que creé
    $testimonios_prueba = [
        'María González',
        'Carlos Martínez', 
        'Ana Torres',
        'David Sánchez',
        'Laura Jiménez',
        'Roberto Díaz',
        'Patricia López',
        'Miguel Ángel Castro',
        'Carmen Rodríguez',
        'Fernando Gutiérrez'
    ];

    echo "BUSCANDO TESTIMONIOS DE PRUEBA:\n";
    $testimonios_encontrados = 0;
    foreach ($testimonios_prueba as $nombre_prueba) {
        $stmt = $pdo->prepare('SELECT id, nombre, email FROM testimonios WHERE nombre LIKE ?');
        $stmt->execute(["%{$nombre_prueba}%"]);
        $testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($testimonios as $testimonio) {
            echo "  ❌ DATO DE PRUEBA: ID {$testimonio['id']}: {$testimonio['nombre']} ({$testimonio['email']})\n";
            $testimonios_encontrados++;
        }
    }

    if ($testimonios_encontrados == 0) {
        echo "  ✅ No se encontraron testimonios de prueba\n";
    }

    // Verificar emails con @email.com (patrón que usé en mis scripts)
    echo "\nBUSCANDO EMAILS CON PATRONES DE PRUEBA:\n";
    $patrones_prueba = ['@email.com', '@example.com', '@test.com'];
    $datos_prueba_encontrados = false;

    foreach ($patrones_prueba as $patron) {
        // Testimonios
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM testimonios WHERE email LIKE ?');
        $stmt->execute(["%{$patron}%"]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($count['total'] > 0) {
            echo "  ❌ Testimonios con {$patron}: {$count['total']}\n";
            $datos_prueba_encontrados = true;
        }

        // Usuarios
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM usuarios WHERE email LIKE ?');
        $stmt->execute(["%{$patron}%"]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($count['total'] > 0) {
            echo "  ❌ Usuarios con {$patron}: {$count['total']}\n";
            $datos_prueba_encontrados = true;
        }
    }

    if (!$datos_prueba_encontrados) {
        echo "  ✅ No se encontraron datos con patrones de prueba\n";
    }

    echo "\n=== PLAN DE LIMPIEZA ===\n\n";

    if ($testimonios_encontrados > 0 || $datos_prueba_encontrados) {
        echo "🧹 ACCIONES A REALIZAR:\n";
        
        if ($testimonios_encontrados > 0) {
            echo "  - Eliminar $testimonios_encontrados testimonios de prueba\n";
        }
        
        if ($datos_prueba_encontrados) {
            echo "  - Eliminar datos con emails de prueba\n";
        }
        
        echo "\n✅ DATOS A PRESERVAR:\n";
        echo "  - " . count($usuarios_originales) . " usuarios originales\n";
        echo "  - 1 afiliado original (DORA RAMIREZ ALVAREZ)\n";
        echo "  - 1 libro original\n";
        echo "  - 1 escritor original (IVAN ORTEGA RODRIGUEZ)\n";
        echo "  - 0 ventas (no hay datos)\n";
        
    } else {
        echo "✅ RESULTADO:\n";
        echo "  - NO se encontraron datos de prueba para eliminar\n";
        echo "  - Todos los datos actuales son ORIGINALES del usuario\n";
        echo "  - NO es necesaria limpieza de datos\n";
    }

    echo "\n⚠️  CONFIRMACIÓN REQUERIDA:\n";
    echo "¿Deseas proceder con la limpieza? (crear archivo limpiar_datos_prueba.php para ejecutar)\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>