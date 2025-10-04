<?php
require_once 'config/database.php';

echo "🗑️ ELIMINANDO SISTEMA campanas_afiliados\n";
echo "========================================\n\n";

$conn = getDBConnection();

// 1. Hacer backup de los datos por si acaso
echo "1. 💾 Creando backup de datos...\n";
$stmt = $conn->query('SELECT * FROM campanas_afiliados');
$backup = $stmt->fetchAll();
file_put_contents('backup_campanas_afiliados.json', json_encode($backup, JSON_PRETTY_PRINT));
echo "   ✅ Backup guardado en backup_campanas_afiliados.json\n\n";

// 2. Eliminar la tabla
echo "2. 🗑️ Eliminando tabla campanas_afiliados...\n";
try {
    $conn->exec('DROP TABLE campanas_afiliados');
    echo "   ✅ Tabla eliminada exitosamente\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 3. Verificar que se eliminó
echo "3. ✅ Verificando eliminación...\n";
try {
    $conn->query('SELECT 1 FROM campanas_afiliados LIMIT 1');
    echo "   ❌ La tabla aún existe\n";
} catch (Exception $e) {
    echo "   ✅ Tabla eliminada correctamente\n";
}

echo "\n🎯 SIGUIENTE PASO:\n";
echo "   Eliminar archivos relacionados:\n";
echo "   - api/afiliados/campanas.php\n";
echo "   - Referencias en dashboard de afiliados\n\n";

echo "✅ SISTEMA SIMPLIFICADO:\n";
echo "   Solo tabla 'campanas' para flujo Admin → Afiliados\n";
?>