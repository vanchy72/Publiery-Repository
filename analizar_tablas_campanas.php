<?php
require_once 'config/database.php';

echo "📊 ANÁLISIS DE TABLAS DE CAMPAÑAS\n";
echo "================================\n\n";

$conn = getDBConnection();

$tablas = [
    'campanas',
    'campanas_afiliados', 
    'campanas_compartidas_admin',
    'campana_compartidas',
    'campana_distribucion',
    'campana_envios',
    'campana_tracking'
];

foreach ($tablas as $tabla) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM $tabla");
        $total = $stmt->fetch()['total'];
        echo "✅ $tabla: $total registros\n";
        
        // Obtener estructura básica
        $stmt = $conn->query("DESCRIBE $tabla");
        $columnas = $stmt->fetchAll();
        echo "   Columnas principales: ";
        $nombres = array_slice(array_column($columnas, 'Field'), 0, 5);
        echo implode(', ', $nombres);
        if (count($columnas) > 5) echo '...';
        echo "\n";
        
        // Si tiene datos, mostrar algunos ejemplos
        if ($total > 0) {
            $stmt = $conn->query("SELECT * FROM $tabla LIMIT 2");
            $ejemplos = $stmt->fetchAll();
            foreach ($ejemplos as $i => $ejemplo) {
                echo "   Ejemplo " . ($i+1) . ": ID=" . ($ejemplo['id'] ?? 'N/A');
                if (isset($ejemplo['nombre'])) echo ", nombre=" . substr($ejemplo['nombre'], 0, 20);
                if (isset($ejemplo['campana_id'])) echo ", campana_id=" . $ejemplo['campana_id'];
                echo "\n";
            }
        }
        echo "\n";
        
    } catch (Exception $e) {
        echo "❌ $tabla: No existe o error - " . $e->getMessage() . "\n\n";
    }
}

echo "🔍 ANÁLISIS ESPECÍFICO DE LA TABLA PRINCIPAL:\n";
echo "=============================================\n";

try {
    $stmt = $conn->query("
        SELECT 
            estado,
            COUNT(*) as total,
            SUM(CASE WHEN compartida_red = 1 THEN 1 ELSE 0 END) as compartidas
        FROM campanas 
        GROUP BY estado
    ");
    $estadisticas = $stmt->fetchAll();
    
    echo "Estado de campañas:\n";
    foreach ($estadisticas as $stat) {
        echo "  - {$stat['estado']}: {$stat['total']} total, {$stat['compartidas']} compartidas\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>