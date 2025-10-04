/**
 * JavaScript para Login
 * Maneja la autenticación de usuarios con la API PHP
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('correo');
    const passwordInput = document.getElementById('password');
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    const loadingSpinner = document.createElement('div');
    
    // Crear spinner de carga
    loadingSpinner.className = 'loading-spinner';
    loadingSpinner.innerHTML = '<div class="spinner"></div>';
    loadingSpinner.style.display = 'none';
    submitBtn.parentNode.insertBefore(loadingSpinner, submitBtn);

    // Limpiar cualquier dato de sesión al cargar el login
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    localStorage.removeItem('dashboard_data');
    localStorage.removeItem('afiliado_data');
    localStorage.removeItem('afiliado_token');
    sessionStorage.removeItem('current_user_id');
    sessionStorage.removeItem('afiliado_id');
    sessionStorage.removeItem('afiliado_token');

    // Validación en tiempo real
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // Manejar envío del formulario
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validar campos
        if (!validateForm()) {
            return;
        }

        // Mostrar loading
        showLoading(true);

        try {
            const formData = {
                email: emailInput.value.trim(),
                password: passwordInput.value
            };

            console.log('🚀 Enviando login a: api/auth/login.php');
            console.log('📊 Datos:', formData);
            
            const response = await fetch('api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            console.log('📨 Response status:', response.status);

            const data = await response.json();

            if (data.success) {
                // Login exitoso
                if (data.is_pending_activation) {
                    // Para afiliados pendientes, mostrar mensaje especial
                    showSuccess('Inicio de sesión exitoso. Tu cuenta está pendiente de activación. Realiza una compra para activarte.');
                } else {
                    showSuccess('Inicio de sesión exitoso');
                }
                
                // Limpiar cualquier sesión anterior
                localStorage.removeItem('session_token');
                localStorage.removeItem('user_data');
                sessionStorage.removeItem('current_user_id');
                
                console.log('💾 Guardando datos del usuario:', data.user);
                
                // Guardar session_token, user_data y current_user_id
                if (data.session_token) {
                    localStorage.setItem('session_token', data.session_token);
                    console.log('🔑 Token guardado:', data.session_token);
                }
                localStorage.setItem('user_data', JSON.stringify(data.user));
                sessionStorage.setItem('current_user_id', data.user.id.toString());
                // NUEVO: Guardar userId en localStorage para compatibilidad con compra
                localStorage.setItem('userId', data.user.id.toString());
                
                // Verificar que se guardó correctamente
                const savedData = localStorage.getItem('user_data');
                console.log('✅ Datos verificados en localStorage:', savedData);
                // Redirigir según el rol
                switch (data.user.rol) {
                    case 'afiliado':
                        window.location.href = 'dashboard-afiliado.html';
                        break;
                    case 'escritor':
                        window.location.href = 'dashboard-escritor-mejorado.html';
                        break;
                    case 'admin':
                        window.location.href = 'admin/dashboard.html';
                        break;
                    default: // lector
                        window.location.href = 'tienda-lectores.html';
                        break;
                }
                // Redirigir a la URL que nos da el servidor. ¡Esto es más seguro y correcto!
                window.location.href = data.redirect;
            } else {
                // Error en login
                showError(data.error || 'Error en el inicio de sesión');
            }

        } catch (error) {
            // Log solo en desarrollo
            if (typeof console !== 'undefined' && console.error) {
                console.error('Error:', error);
            }
            showError('Error de conexión. Intenta nuevamente.');
        } finally {
            showLoading(false);
        }
    });

    // Funciones de validación
    function validateEmail() {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email) {
            showFieldError(emailInput, 'El email es requerido');
            return false;
        }
        
        if (!emailRegex.test(email)) {
            showFieldError(emailInput, 'Formato de email inválido');
            return false;
        }
        
        clearFieldError(emailInput);
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        
        if (!password) {
            showFieldError(passwordInput, 'La contraseña es requerida');
            return false;
        }
        
        if (password.length < 6) {
            showFieldError(passwordInput, 'La contraseña debe tener al menos 6 caracteres');
            return false;
        }
        
        clearFieldError(passwordInput);
        return true;
    }

    function validateForm() {
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        return isEmailValid && isPasswordValid;
    }

    // Funciones de UI
    function showLoading(show) {
        submitBtn.disabled = show;
        loadingSpinner.style.display = show ? 'block' : 'none';
        submitBtn.style.display = show ? 'none' : 'block';
    }

    function showSuccess(message) {
        showNotification(message, 'success');
    }

    function showError(message) {
        showNotification(message, 'error');
    }

    function showFieldError(field, message) {
        clearFieldError(field);
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        
        field.parentNode.appendChild(errorDiv);
    }

    function clearFieldError(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    function showNotification(message, type) {
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Estilos de la notificación
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.padding = '1rem 1.5rem';
        notification.style.borderRadius = '8px';
        notification.style.color = 'white';
        notification.style.fontWeight = '500';
        notification.style.zIndex = '1000';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'transform 0.3s ease';
        notification.style.maxWidth = '400px';
        notification.style.wordWrap = 'break-word';
        
        if (type === 'success') {
            notification.style.backgroundColor = '#27ae60';
        } else {
            notification.style.backgroundColor = '#e74c3c';
        }
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover después de 6 segundos para afiliados pendientes
        const duration = type === 'success' ? 6000 : 5000;
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }
});

// Estilos CSS adicionales
const styles = `
    .loading-spinner {
        display: inline-block;
        margin-left: 10px;
    }
    .spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .field-error {
        color: #e74c3c;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    input.error {
        border-color: #e74c3c;
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
        word-wrap: break-word;
    }
    .notification.success {
        background-color: #27ae60;
    }
    .notification.error {
        background-color: #e74c3c;
    }
`;
// Agregar estilos al documento
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);
