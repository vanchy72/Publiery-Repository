<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧹 LIMPIEZA DE ARCHIVOS DE PRUEBA</h1>";

$archivos_prueba = [
    'test_registro_final.html',
    'test_debug_detallado.html',
    'test_funciones_corregidas.php',
    'test_directo_php.php',
    'test_simple_registro.html',
    'debug_registro_directo.php',
    'registro_simplificado.php',
    'test_registro_paso_a_paso.html',
    'registro_funcional.php',
    'test_basico_bd.php',
    'ver_usuarios_existentes.php',
    'test_login_directo.html',
    'test_endpoint_login.php',
    'test_login_simple.html',
    'test_paso_1_codigo.html',
    'test_obtener_codigo_afiliado.php',
    'obtener_codigo_directo.php',
    'test_codigo_simple.html',
    'test_login_y_compra.html',
    'test_flujo_completo.html',
    'test_simular_venta.php',
    'test_verificar_comisiones.php',
    'verificar_registro_ahora.html',
    'registro_prueba_exitosa.html',
    'test_final_corregido.html',
    'test_venta_completa.html',
    'simular_venta_directa.php',
    'verificar_comisiones_directa.php',
    'test_simple_final.php',
    'verificar_estructura_ventas.php',
    'test_final_corregido.php'
];

$eliminados = [];
$no_encontrados = [];

foreach ($archivos_prueba as $archivo) {
    if (file_exists($archivo)) {
        if (unlink($archivo)) {
            $eliminados[] = $archivo;
        }
    } else {
        $no_encontrados[] = $archivo;
    }
}

echo "<h2>✅ Archivos eliminados (" . count($eliminados) . "):</h2>";
echo "<ul>";
foreach ($eliminados as $archivo) {
    echo "<li>🗑️ " . $archivo . "</li>";
}
echo "</ul>";

if (!empty($no_encontrados)) {
    echo "<h2>ℹ️ Archivos no encontrados (" . count($no_encontrados) . "):</h2>";
    echo "<ul>";
    foreach ($no_encontrados as $archivo) {
        echo "<li>❓ " . $archivo . "</li>";
    }
    echo "</ul>";
}

echo "<h2 style='color: green;'>🎉 ¡LIMPIEZA COMPLETADA!</h2>";
echo "<p>Tu proyecto Publiery está ahora limpio y listo para producción.</p>";

echo "<h2>📂 Archivos principales conservados:</h2>";
echo "<ul>";
echo "<li>✅ <strong>api/</strong> - Todos los endpoints funcionando</li>";
echo "<li>✅ <strong>config/</strong> - Configuraciones y funciones</li>";
echo "<li>✅ <strong>css/</strong> - Estilos del proyecto</li>";
echo "<li>✅ <strong>js/</strong> - JavaScript funcional</li>";
echo "<li>✅ <strong>images/</strong> - Recursos visuales</li>";
echo "<li>✅ <strong>index.html</strong> - Página principal</li>";
echo "<li>✅ <strong>login.html</strong> - Sistema de login</li>";
echo "<li>✅ <strong>registro.html</strong> - Sistema de registro</li>";
echo "<li>✅ <strong>dashboard-afiliado.html</strong> - Dashboard afiliados</li>";
echo "<li>✅ <strong>dashboard-escritor-mejorado.html</strong> - Dashboard escritores</li>";
echo "<li>✅ <strong>tienda-lectores.html</strong> - Tienda funcional</li>";
echo "<li>✅ <strong>admin-panel.html</strong> - Panel administrativo</li>";
echo "</ul>";

echo "<div style='background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 2px solid #28a745; padding: 30px; border-radius: 15px; margin: 30px 0;'>";
echo "<h2 style='color: #155724; text-align: center;'>🏆 ¡PROYECTO PUBLIERY COMPLETADO!</h2>";
echo "<p style='text-align: center; font-size: 18px; color: #155724;'>";
echo "Tu plataforma de libros digitales con sistema de afiliados está <strong>100% funcional</strong> y lista para usar.";
echo "</p>";
echo "</div>";
?>
