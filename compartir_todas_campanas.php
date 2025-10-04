<?php
session_start();
$_SESSION['user_id'] = 80; // Admin

require_once 'config/database.php';

echo "🔄 COMPARTIENDO CAMPAÑAS CON LA RED\n\n";

$conn = getDBConnection();

// Obtener campañas no compartidas
$stmt = $conn->query("
    SELECT id, nombre 
    FROM campanas 
    WHERE compartida_red = 0 
    ORDER BY fecha_creacion DESC 
    LIMIT 3
");

$campanasNoCompartidas = $stmt->fetchAll();

foreach ($campanasNoCompartidas as $campana) {
    echo "📤 Compartiendo: {$campana['nombre']} (ID: {$campana['id']})\n";
    
    // Marcar como compartida
    $stmt = $conn->prepare("
        UPDATE campanas 
        SET compartida_red = 1, fecha_compartida = NOW() 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$campana['id']])) {
        echo "   ✅ Marcada como compartida\n";
        
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
            echo "   📢 Notificación enviada a afiliados\n";
        }
    }
    echo "\n";
}

// Verificar total compartidas
$stmt = $conn->query("SELECT COUNT(*) as total FROM campanas WHERE compartida_red = 1");
$totalCompartidas = $stmt->fetch()['total'];

echo "🎯 RESUMEN:\n";
echo "   Total campañas compartidas: {$totalCompartidas}\n";
echo "   Las campañas ahora aparecerán en el dashboard de afiliados\n";
?>