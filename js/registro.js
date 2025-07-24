// registro.js - Manejo del registro de usuarios en TuPlataforma
console.log('✅ registro.js cargado - versión actualizada');

// Función para obtener parámetros de la URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formularioRegistro');
    const selectRol = document.getElementById('rol');
    const campoCodigo = document.getElementById('campoCodigoAfiliado');
    const inputCodigo = document.getElementById('codigo_afiliado');
    const mensajeRef = document.getElementById('mensajeRef');

    // Mostrar/ocultar campo de código de afiliado según el rol seleccionado
    selectRol.addEventListener('change', function() {
        if (this.value === 'afiliado') {
            campoCodigo.style.display = 'block';
            inputCodigo.required = false; // No es obligatorio
        } else {
            campoCodigo.style.display = 'none';
            inputCodigo.value = '';
            inputCodigo.required = false;
            inputCodigo.readOnly = false;
        }
    });

    // Si hay parámetro ref en la URL, selecciona "Afiliado", muestra el campo y bloquea edición
    const ref = getQueryParam('ref');
    if (ref) {
        selectRol.value = 'afiliado';
        campoCodigo.style.display = 'block';
        inputCodigo.value = ref;
        inputCodigo.readOnly = true;
        inputCodigo.required = true; // Solo es obligatorio si viene por URL
        selectRol.disabled = true;
        if (mensajeRef) mensajeRef.style.display = 'block';
    } else {
        // Si no hay ref, deja el campo editable y vacío
        inputCodigo.value = '';
        inputCodigo.readOnly = false;
        inputCodigo.required = false; // No es obligatorio
        // Si el rol es afiliado al cargar, muestra el campo, si no, ocúltalo
        campoCodigo.style.display = (selectRol.value === 'afiliado') ? 'block' : 'none';
        selectRol.disabled = false;
        if (mensajeRef) mensajeRef.style.display = 'none';
    }

    // Manejar envío del formulario
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Quitar mensajes anteriores
        removeAlerts();

        // Obtener datos
        const nombre = form.nombre.value.trim();
        const email = form.email.value.trim();
        const documento = form.documento.value.trim();
        const password = form.password.value;
        let rol = form.rol.value;
        let codigo_afiliado = inputCodigo.value.trim();

        // Logs de diagnóstico
        console.log('Datos del formulario:', {
            nombre,
            email,
            documento,
            password: password ? '***' : 'vacío',
            rol,
            codigo_afiliado: codigo_afiliado || 'vacío'
        });

        // Validaciones básicas
        if (!nombre || !email || !documento || !password || !rol) {
            console.log('Error: Campos obligatorios faltantes');
            showAlert('Todos los campos son obligatorios.', 'error');
            return;
        }
        if (rol === 'autor') rol = 'escritor'; // Normalizar rol

        // Validar email
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
            showAlert('El email no es válido.', 'error');
            return;
        }
        // Validar contraseña
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password)) {
            showAlert('La contraseña debe tener al menos 8 caracteres, incluyendo una mayúscula, una minúscula y un número.', 'error');
            return;
        }
        // Validar documento
        if (documento.length < 5) {
            showAlert('El documento debe tener al menos 5 caracteres.', 'error');
            return;
        }

        // Preparar datos para la API
        const data = {
            nombre,
            email,
            documento,
            password,
            rol,
            codigo_referido: codigo_afiliado
        };

        console.log('Datos a enviar a la API:', {
            ...data,
            password: '***'
        });

        try {
            const response = await fetch('api/auth/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            console.log('Respuesta del servidor:', response.status, response.statusText);
            
            // Obtener el contenido de la respuesta
            const responseText = await response.text();
            console.log('Contenido de la respuesta:', responseText);
            
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                try {
                    const errorData = JSON.parse(responseText);
                    console.log('Error del servidor:', errorData);
                    showAlert(errorData.error || `Error del servidor: ${response.status}`, 'error');
                } catch (parseError) {
                    console.log('Error parseando respuesta:', parseError);
                    showAlert(`Error del servidor: ${response.status} - ${responseText.substring(0, 100)}`, 'error');
                }
                return;
            }
            
            // Intentar parsear como JSON
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('Resultado exitoso:', result);
            } catch (parseError) {
                console.log('Error parseando JSON:', parseError);
                console.log('Respuesta recibida:', responseText);
                showAlert('Error procesando respuesta del servidor', 'error');
                return;
            }

            if (result.success) {
                showAlert(result.message || 'Registro exitoso. Redirigiendo...', 'success');
                // Restaurar la lógica de redirección según el rol que envía el backend
                window.location.href = result.redirect || 'tienda-lectores.html';

                // Supón que recibes el estado en data.afiliado.estado
                if (result.user && result.user.estado === 'pendiente') {
                    // Muestra un mensaje destacado
                    document.getElementById('mensajeActivacion').style.display = 'block';
                } else {
                    document.getElementById('mensajeActivacion').style.display = 'none';
                }
            } else {
                showAlert(result.error || 'Error en el registro.', 'error');
            }
        } catch (err) {
            console.error('Error de conexión:', err);
            showAlert('Error de conexión con el servidor. Verifica tu conexión a internet.', 'error');
        }
    });

    // Función para mostrar alertas
    function showAlert(msg, type) {
        removeAlerts();
        const div = document.createElement('div');
        div.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
        div.textContent = msg;
        form.parentNode.insertBefore(div, form);
    }
    // Quitar alertas previas
    function removeAlerts() {
        document.querySelectorAll('.alert').forEach(el => el.remove());
    }
}); 