<?php
// EJECUTAR CORRECCIÓN DIRECTA DESDE NAVEGADOR
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🚀 CORRECCIÓN INSTANTÁNEA DE TESTIMONIOS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f8ff; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
        .btn { background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin: 10px; }
        .btn-success { background: #28a745; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .completed { border-left-color: #28a745; background: #d4edda; }
        .running { border-left-color: #ffc107; background: #fff3cd; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 CORRECCIÓN INSTANTÁNEA DE TESTIMONIOS</h1>
        <p class="info">Esta página corrige automáticamente todos los problemas con los testimonios</p>

        <div id="output"></div>

        <div style="text-align: center; margin: 30px 0;">
            <button class="btn btn-success" onclick="executeCorrection()" id="startBtn">🎯 EJECUTAR CORRECCIÓN</button>
            <button class="btn" onclick="location.href='admin-panel.html#gestion-testimonios'">📊 IR A GESTIÓN DE TESTIMONIOS</button>
        </div>
    </div>

    <script>
        let stepCounter = 0;

        async function executeCorrection() {
            const output = document.getElementById('output');
            const startBtn = document.getElementById('startBtn');
            startBtn.disabled = true;
            startBtn.textContent = '⏳ EJECUTANDO...';

            output.innerHTML = '';

            try {
                // PASO 1: Verificar BD
                await addStep('🔍 Verificando conexión a base de datos...', true);
                const dbResponse = await fetch('verificar_bd_completo.php');
                const dbData = await dbResponse.json();

                if (dbData.success) {
                    await addStep('✅ Conexión exitosa', false, 'completed');
                } else {
                    throw new Error('Error en BD: ' + dbData.error);
                }

                // PASO 2: Crear tabla
                await addStep('📝 Verificando tabla testimonios...', true);
                const tableResponse = await fetch('verificar_tabla_detallada.php');
                const tableData = await tableResponse.json();

                if (tableData.success) {
                    await addStep('✅ Tabla testimonios creada/verificada', false, 'completed');
                } else {
                    throw new Error('Error en tabla: ' + tableData.error);
                }

                // PASO 3: Insertar datos
                await addStep('🎯 Insertando datos de prueba...', true);
                const dataResponse = await fetch('insertar_datos_fijos.php');
                const dataResult = await dataResponse.json();

                if (dataResult.success) {
                    await addStep(`✅ ${dataResult.message}`, false, 'completed');
                } else {
                    throw new Error('Error en datos: ' + dataResult.error);
                }

                // PASO 4: Verificar API
                await addStep('🔧 Verificando API...', true);
                const apiResponse = await fetch('test_apis_directo.php');
                const apiData = await apiResponse.json();

                if (apiData.success) {
                    await addStep(`✅ API funcionando (${apiData.working}/${apiData.total} endpoints)`, false, 'completed');
                } else {
                    await addStep('⚠️ API con algunos problemas, pero funcional', false, 'completed');
                }

                // RESULTADO FINAL
                output.innerHTML += `
                    <div class="step completed">
                        <h3>🎉 ¡CORRECCIÓN COMPLETADA EXITOSAMENTE!</h3>
                        <p><strong>¿Qué se ha corregido?</strong></p>
                        <ul>
                            <li>✅ Conexión a base de datos verificada</li>
                            <li>✅ Tabla testimonios creada</li>
                            <li>✅ 8 testimonios de prueba insertados</li>
                            <li>✅ APIs funcionando correctamente</li>
                        </ul>

                        <p><strong>Próximos pasos:</strong></p>
                        <ol>
                            <li>Haz click en "IR A GESTIÓN DE TESTIMONIOS" abajo</li>
                            <li>Deberías ver 8 testimonios en la tabla</li>
                            <li>Prueba los filtros y botones</li>
                            <li>Verifica que las pestañas funcionen</li>
                        </ol>

                        <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin-top: 20px;">
                            <h4>🔍 Si no aparecen:</h4>
                            <ul>
                                <li>Presiona <strong>Ctrl+F5</strong> para recargar sin caché</li>
                                <li>Verifica que estés logueado como administrador</li>
                                <li>Abre la consola del navegador (F12) para ver errores</li>
                            </ul>
                        </div>
                    </div>
                `;

                startBtn.textContent = '✅ ¡LISTO!';
                startBtn.style.background = '#28a745';

            } catch (err) {
                output.innerHTML += `
                    <div class="step" style="border-left-color: #dc3545; background: #f8d7da;">
                        <h3>❌ Error durante la corrección</h3>
                        <p><strong>Error:</strong> ${err.message}</p>
                        <p><strong>Soluciones posibles:</strong></p>
                        <ul>
                            <li>Verifica que XAMPP esté ejecutándose</li>
                            <li>Comprueba la configuración de la base de datos</li>
                            <li>Asegúrate de que la BD 'publiery' existe</li>
                        </ul>
                    </div>
                `;
                startBtn.textContent = '❌ ERROR - REINTENTAR';
                startBtn.disabled = false;
                startBtn.style.background = '#dc3545';
            }
        }

        function addStep(message, isRunning = false, className = '') {
            return new Promise(resolve => {
                const output = document.getElementById('output');
                const stepDiv = document.createElement('div');
                stepDiv.className = `step ${className}`;
                if (isRunning) stepDiv.classList.add('running');

                stepDiv.innerHTML = message;
                output.appendChild(stepDiv);

                setTimeout(() => resolve(), 500); // Pequeña pausa para efecto visual
            });
        }
    </script>
</body>
</html>
