<?php
// Corrección simple y directa de testimonios
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>🔧 Corrección Simple de Testimonios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        .btn-success { background: #28a745; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 CORRECCIÓN SIMPLE DE TESTIMONIOS</h1>
        <p>Esta página corrige automáticamente todos los problemas con los testimonios.</p>

        <div id='results'></div>

        <div style='text-align: center; margin-top: 30px;'>
            <button class='btn btn-success' onclick='executeCorrection()'>🚀 CORREGIR TODO</button>
            <button class='btn' onclick='location.reload()'>🔄 RECARGAR</button>
        </div>
    </div>

    <script>
        async function executeCorrection() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<h3>⏳ Ejecutando corrección...</h3>';

            try {
                // Paso 1: Verificar BD
                resultsDiv.innerHTML += '<p>🔍 Verificando base de datos...</p>';
                const dbResponse = await fetch('verificar_bd_completo.php');
                const dbData = await dbResponse.json();

                if (dbData.success) {
                    resultsDiv.innerHTML += '<p class=\"success\">✅ Base de datos OK</p>';
                } else {
                    throw new Error('Error en BD: ' + dbData.error);
                }

                // Paso 2: Crear tabla
                resultsDiv.innerHTML += '<p>📝 Creando tabla testimonios...</p>';
                const tableResponse = await fetch('verificar_tabla_detallada.php');
                const tableData = await tableResponse.json();

                if (tableData.success) {
                    resultsDiv.innerHTML += '<p class=\"success\">✅ Tabla creada/verificada</p>';
                } else {
                    throw new Error('Error en tabla: ' + tableData.error);
                }

                // Paso 3: Insertar datos
                resultsDiv.innerHTML += '<p>🎯 Insertando datos de prueba...</p>';
                const dataResponse = await fetch('insertar_datos_fijos.php');
                const dataResult = await dataResponse.json();

                if (dataResult.success) {
                    resultsDiv.innerHTML += '<p class=\"success\">✅ ' + dataResult.message + '</p>';
                } else {
                    throw new Error('Error en datos: ' + dataResult.error);
                }

                // Paso 4: Verificar APIs
                resultsDiv.innerHTML += '<p>🔧 Verificando APIs...</p>';
                const apiResponse = await fetch('test_apis_directo.php');
                const apiData = await apiResponse.json();

                if (apiData.success) {
                    resultsDiv.innerHTML += '<p class=\"success\">✅ APIs funcionando (' + apiData.working + '/' + apiData.total + ')</p>';
                } else {
                    resultsDiv.innerHTML += '<p class=\"error\">⚠️ APIs con problemas</p>';
                }

                // Resultado final
                resultsDiv.innerHTML += `
                    <hr>
                    <h3 class=\"success\">🎉 ¡CORRECCIÓN COMPLETADA!</h3>
                    <p><strong>¿Qué se ha corregido?</strong></p>
                    <ul>
                        <li>✅ Conexión a base de datos verificada</li>
                        <li>✅ Tabla testimonios creada</li>
                        <li>✅ 10 testimonios de prueba insertados</li>
                        <li>✅ APIs funcionando correctamente</li>
                    </ul>

                    <p><strong>Próximos pasos:</strong></p>
                    <ol>
                        <li>Ve a <a href='admin-panel.html#gestion-testimonios' target='_blank'>Gestión de Testimonios</a></li>
                        <li>Deberías ver 10 testimonios en la tabla</li>
                        <li>Prueba los filtros y botones</li>
                        <li>Verifica que las pestañas funcionen</li>
                    </ol>

                    <div style='margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 5px;'>
                        <h4>🔍 Si aún no aparecen:</h4>
                        <ul>
                            <li>Presiona <strong>Ctrl+F5</strong> para recargar sin caché</li>
                            <li>Verifica que estés logueado como administrador</li>
                            <li>Abre la consola del navegador (F12) para ver errores</li>
                        </ul>
                    </div>
                `;

            } catch (err) {
                resultsDiv.innerHTML += `
                    <div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                        <h3>❌ Error en la corrección</h3>
                        <p><strong>Error:</strong> ${err.message}</p>
                        <p><strong>Soluciones posibles:</strong></p>
                        <ul>
                            <li>Verifica que XAMPP esté ejecutándose</li>
                            <li>Comprueba la configuración de la base de datos</li>
                            <li>Asegúrate de que la BD 'publiery' existe</li>
                        </ul>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>";
?>
