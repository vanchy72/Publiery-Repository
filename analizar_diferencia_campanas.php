<?php
require_once 'config/database.php';

echo "🔍 ANÁLISIS DE CAMPAÑAS: ADMIN vs AFILIADOS\n";
echo "==========================================\n\n";

$conn = getDBConnection();

// 1. Campañas totales en admin
echo "📋 CAMPAÑAS EN PANEL ADMIN:\n";
$stmt = $conn->query("SELECT id, nombre, estado, compartida_red FROM campanas ORDER BY fecha_creacion DESC");
$campanasAdmin = $stmt->fetchAll();

foreach ($campanasAdmin as $i => $campana) {
    $compartida = $campana['compartida_red'] ? '✅ Compartida' : '❌ No compartida';
    echo "  " . ($i+1) . ". ID {$campana['id']}: {$campana['nombre']} - {$compartida}\n";
}
echo "Total Admin: " . count($campanasAdmin) . " campañas\n\n";

// 2. Campañas compartidas para afiliados
echo "🎯 CAMPAÑAS PARA AFILIADOS (compartidas_red = 1):\n";
$stmt = $conn->query("
    SELECT id, nombre, estado, fecha_compartida 
    FROM campanas 
    WHERE compartida_red = 1 
    AND estado IN ('completada', 'programada') 
    ORDER BY fecha_compartida DESC, fecha_creacion DESC
");
$campanasAfiliados = $stmt->fetchAll();

foreach ($campanasAfiliados as $i => $campana) {
    echo "  " . ($i+1) . ". ID {$campana['id']}: {$campana['nombre']} - {$campana['estado']}\n";
    echo "     Compartida: {$campana['fecha_compartida']}\n";
}
echo "Total Afiliados: " . count($campanasAfiliados) . " campañas\n\n";

// 3. Diferencia
$diferencia = count($campanasAdmin) - count($campanasAfiliados);
echo "📊 DIFERENCIA: {$diferencia} campañas\n\n";

if ($diferencia > 0) {
    echo "🔧 CAMPAÑAS NO COMPARTIDAS (aparecen solo en admin):\n";
    $idsCompartidas = array_column($campanasAfiliados, 'id');
    
    foreach ($campanasAdmin as $campana) {
        if (!in_array($campana['id'], $idsCompartidas)) {
            echo "  - ID {$campana['id']}: {$campana['nombre']} (compartida_red = {$campana['compartida_red']})\n";
        }
    }
    
    echo "\n💡 SOLUCIÓN:\n";
    echo "   Para que aparezcan en el dashboard de afiliados, usa:\n";
    echo "   'Compartir con Red' en el panel admin\n";
}
?>