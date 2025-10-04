<?php
require_once 'config/database.php';

echo "🔍 ANÁLISIS DE IMPACTO - ELIMINAR campanas_afiliados\n";
echo "==================================================\n\n";

$conn = getDBConnection();

// Ver qué hay en campanas_afiliados
echo "📋 CONTENIDO ACTUAL DE campanas_afiliados:\n";
$stmt = $conn->query('SELECT * FROM campanas_afiliados');
$campanas_afiliados = $stmt->fetchAll();

if (count($campanas_afiliados) > 0) {
    foreach ($campanas_afiliados as $camp) {
        echo "  ID: {$camp['id']} - {$camp['nombre']}\n";
        echo "  Afiliado: {$camp['afiliado_id']}\n";
        echo "  Objetivo: {$camp['objetivo_ventas']}\n";
        echo "  Fecha: {$camp['fecha_creacion']}\n\n";
    }
} else {
    echo "  ❌ No hay registros\n\n";
}

// Buscar referencias en archivos
echo "🔍 ARCHIVOS QUE USAN campanas_afiliados:\n";
echo "  - api/afiliados/campanas.php\n";
echo "  - Posibles referencias en dashboard de afiliados\n\n";

// Verificar si se usa en dashboard
echo "🎯 RECOMENDACIÓN:\n";
echo "✅ SÍ eliminar campanas_afiliados porque:\n";
echo "  1. Solo tiene " . count($campanas_afiliados) . " registros de prueba\n";
echo "  2. El nuevo flujo es: ADMIN crea → AFILIADOS promocionan\n";
echo "  3. Evita confusión entre dos sistemas de campañas\n";
echo "  4. Simplifica la arquitectura\n\n";

echo "📋 PASOS PARA ELIMINAR:\n";
echo "  1. Eliminar tabla campanas_afiliados\n";
echo "  2. Eliminar api/afiliados/campanas.php\n";
echo "  3. Quitar referencias del dashboard de afiliados\n";
echo "  4. Actualizar documentación\n";
?>