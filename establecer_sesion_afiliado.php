<?php
session_start();

// Establecer sesión de afiliado para pruebas
$_SESSION['user_id'] = 82; // ID de DORA RAMIREZ ALVAREZ
$_SESSION['usuario_id'] = 82;
$_SESSION['rol'] = 'afiliado';
$_SESSION['user_rol'] = 'afiliado';

echo "Sesión establecida para afiliado DORA RAMIREZ ALVAREZ (ID: 82)\n";
echo "Puedes abrir dashboard-afiliado.html y ir a la pestaña 'Campañas'\n";

// También vamos a probar directamente el API
require_once 'config/database.php';

echo "\n🔍 VERIFICANDO CAMPAÑAS DISPONIBLES PARA AFILIADO:\n";

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.nombre,
        c.descripcion,
        c.tipo,
        c.imagen_promocional,
        c.libro_ids,
        c.fecha_compartida,
        c.fecha_creacion,
        GROUP_CONCAT(l.id, ':', l.titulo, ':', l.precio_afiliado SEPARATOR '|') as libros_info
    FROM campanas c
    LEFT JOIN libros l ON FIND_IN_SET(l.id, c.libro_ids) > 0
    WHERE c.compartida_red = 1 
    AND c.estado IN ('completada', 'programada')
    GROUP BY c.id, c.nombre, c.descripcion, c.tipo, c.imagen_promocional, c.libro_ids, c.fecha_compartida, c.fecha_creacion
    ORDER BY c.fecha_compartida DESC, c.fecha_creacion DESC
");
$stmt->execute();
$campanas = $stmt->fetchAll();

echo "Campañas compartidas encontradas: " . count($campanas) . "\n\n";

foreach ($campanas as $campana) {
    echo "🎯 Campaña: {$campana['nombre']} (ID: {$campana['id']})\n";
    echo "   Tipo: {$campana['tipo']}\n";
    echo "   Descripción: {$campana['descripcion']}\n";
    echo "   Compartida: {$campana['fecha_compartida']}\n";
    
    if ($campana['libros_info']) {
        echo "   Libros incluidos:\n";
        $librosInfo = explode('|', $campana['libros_info']);
        foreach ($librosInfo as $libroInfo) {
            $partes = explode(':', $libroInfo);
            if (count($partes) >= 3) {
                echo "     - {$partes[1]} (${$partes[2]})\n";
            }
        }
    }
    echo "\n";
}
?>