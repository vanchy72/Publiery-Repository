<?php
session_start();
$_SESSION['user_id'] = 80; // Admin

require_once 'config/database.php';

echo "🔄 COMPARTIENDO CAMPAÑAS FALTANTES\n";
echo "==================================\n\n";

$conn = getDBConnection();

// Obtener campañas no compartidas
$stmt = $conn->query("
    SELECT id, nombre, estado 
    FROM campanas 
    WHERE compartida_red = 0 
    ORDER BY fecha_creacion DESC
");

$campanasNoCompartidas = $stmt->fetchAll();

echo "📋 CAMPAÑAS NO COMPARTIDAS:\n";
foreach ($campanasNoCompartidas as $campana) {
    echo "  - ID {$campana['id']}: {$campana['nombre']} ({$campana['estado']})\n";
}

echo "\n🚀 COMPARTIENDO TODAS LAS CAMPAÑAS:\n";

// Compartir todas las campañas no compartidas
foreach ($campanasNoCompartidas as $campana) {
    echo "\n📤 Compartiendo: {$campana['nombre']} (ID: {$campana['id']})\n";
    
    // Actualizar estado si es borrador
    if ($campana['estado'] === 'borrador') {
        $stmt = $conn->prepare("UPDATE campanas SET estado = 'programada' WHERE id = ?");
        $stmt->execute([$campana['id']]);
        echo "  ✅ Estado cambiado de 'borrador' a 'programada'\n";
    }
    
    // Marcar como compartida
    $stmt = $conn->prepare("
        UPDATE campanas 
        SET compartida_red = 1, fecha_compartida = NOW() 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$campana['id']])) {
        echo "  ✅ Marcada como compartida\n";
        
        // Crear notificación para afiliados
        $stmt2 = $conn->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales)
            SELECT 
                u.id,
                'campana_admin',
                CONCAT('Nueva campaña: ', ?),
                CONCAT('Nueva campaña promocional disponible: ', ?),
                JSON_OBJECT('campana_id', ?)
            FROM usuarios u
            INNER JOIN afiliados a ON u.id = a.usuario_id
            WHERE u.estado = 'activo' AND u.rol = 'afiliado'
        ");
        
        if ($stmt2->execute([$campana['nombre'], $campana['nombre'], $campana['id']])) {
            echo "  📢 Notificación enviada a afiliados\n";
        }
    }
}

// Verificar conteo final
echo "\n📊 CONTEO FINAL:\n";

$stmt = $conn->query("SELECT COUNT(*) as total FROM campanas");
$totalAdmin = $stmt->fetch()['total'];

$stmt = $conn->query("
    SELECT COUNT(*) as total 
    FROM campanas 
    WHERE compartida_red = 1 
    AND estado IN ('completada', 'programada')
");
$totalAfiliados = $stmt->fetch()['total'];

echo "  Panel Admin: {$totalAdmin} campañas\n";
echo "  Dashboard Afiliados: {$totalAfiliados} campañas\n";
echo "  Diferencia: " . ($totalAdmin - $totalAfiliados) . " campañas\n";

if ($totalAdmin === $totalAfiliados) {
    echo "\n🎉 ¡TODAS LAS CAMPAÑAS ESTÁN SINCRONIZADAS!\n";
}
?>