<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h1>Diagnóstico de la Tabla 'ventas'</h1>";

try {
    $conn = getDBConnection();
    echo "<p style='color:green;'>✅ Conexión a la base de datos exitosa.</p>";

    // 1. Verificar el rango de fechas de las ventas
    $stmt_range = $conn->query("SELECT MIN(fecha_venta) as primera_venta, MAX(fecha_venta) as ultima_venta FROM ventas");
    $range = $stmt_range->fetch(PDO::FETCH_ASSOC);

    if ($range && $range['primera_venta']) {
        echo "<h2>Rango de Fechas de Ventas</h2>";
        echo "<p>Primera venta registrada: <strong>" . $range['primera_venta'] . "</strong></p>";
        echo "<p>Última venta registrada: <strong>" . $range['ultima_venta'] . "</strong></p>";
        echo "<p><i>Asegúrate de que el período que seleccionas en el dashboard incluya estas fechas.</i></p>";
    } else {
        echo "<p style='color:red;'>❌ No se encontraron ventas en la tabla 'ventas'. Este es el problema principal.</p>";
        exit;
    }

    // 2. Mostrar las últimas 20 ventas
    $stmt_ventas = $conn->query("
        SELECT 
            v.id, 
            v.libro_id, 
            l.titulo,
            v.afiliado_id,
            u.nombre as nombre_afiliado,
            v.total, 
            v.fecha_venta
        FROM ventas v
        LEFT JOIN libros l ON v.libro_id = l.id
        LEFT JOIN afiliados a ON v.afiliado_id = a.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY v.fecha_venta DESC
        LIMIT 20
    ");

    $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

    if ($ventas) {
        echo "<h2>Últimas 20 Ventas Registradas</h2>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>ID Venta</th>
                <th>Fecha Venta</th>
                <th>Libro ID</th>
                <th>Título Libro</th>
                <th>Afiliado ID</th>
                <th>Nombre Afiliado</th>
                <th>Total</th>
              </tr>";
        
        foreach ($ventas as $venta) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($venta['id']) . "</td>";
            echo "<td>" . htmlspecialchars($venta['fecha_venta']) . "</td>";
            echo "<td>" . htmlspecialchars($venta['libro_id']) . "</td>";
            echo "<td" . (!$venta['titulo'] ? " style='background-color: #ffcccc;'" : "") . ">" . htmlspecialchars($venta['titulo'] ?: 'ERROR: LIBRO NO ENCONTRADO') . "</td>";
            echo "<td>" . htmlspecialchars($venta['afiliado_id']) . "</td>";
            echo "<td" . (!$venta['nombre_afiliado'] ? " style='background-color: #ffcccc;'" : "") . ">" . htmlspecialchars($venta['nombre_afiliado'] ?: 'ERROR: AFILIADO SIN NOMBRE O NO ENCONTRADO') . "</td>";
            echo "<td>" . htmlspecialchars($venta['total']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "<p><i>Si las columnas 'Título Libro' o 'Nombre Afiliado' muestran error, indica un problema de integridad de datos (un ID de libro o afiliado en una venta no existe en su respectiva tabla).</i></p>";

    } else {
        echo "<p style='color:red;'>❌ No se pudo recuperar ninguna venta, aunque el rango de fechas sí existe. Esto es inusual.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error de base de datos: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error general: " . $e->getMessage() . "</p>";
}
?> 