<?php
require_once 'config/database.php';

echo "🔍 INVESTIGANDO CAMPAÑA ID 12\n";
echo "=============================\n";

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT * FROM campanas WHERE id = 12');
$stmt->execute();
$campana = $stmt->fetch();

echo "ID: {$campana['id']}\n";
echo "Nombre: {$campana['nombre']}\n";
echo "Estado: {$campana['estado']}\n";
echo "Compartida: {$campana['compartida_red']}\n";
echo "Fecha compartida: {$campana['fecha_compartida']}\n\n";

echo "🤔 RAZÓN POR LA QUE NO APARECE:\n";
if ($campana['estado'] === 'borrador') {
    echo "❌ Estado 'borrador' - El API afiliados filtra por estado IN ('completada', 'programada')\n";
    echo "💡 Cambiar estado a 'programada' o 'completada' para que aparezca\n";
}

// Contar realmente las que aparecen en dashboard afiliados
echo "\n📊 CONTEO REAL PARA AFILIADOS:\n";
$stmt = $conn->query("
    SELECT COUNT(*) as total
    FROM campanas 
    WHERE compartida_red = 1 
    AND estado IN ('completada', 'programada')
");
$totalReal = $stmt->fetch()['total'];
echo "Total que ven afiliados: {$totalReal}\n";

// Si hay diferencia entre lo que reportas vs lo que muestra el script
echo "\n🎯 DISCREPANCIA:\n";
echo "- Reportas: 5 campañas en dashboard afiliados\n";
echo "- Script muestra: 4 campañas compartidas válidas\n";
echo "- Panel admin: 10 campañas totales\n";
?>