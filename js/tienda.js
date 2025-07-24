/**
 * JavaScript para la tienda de Publiery
 * Integración completa con PayU y activación automática de afiliados
 */

let carrito = [];
let userDataTienda = null;

function checkAuthenticationTienda() {
    const userDataStr = localStorage.getItem('user_data');
    if (!userDataStr) {
        limpiarSesionTienda();
        return false;
    }
    try {
        const user = JSON.parse(userDataStr);
        if (!["lector", "afiliado", "admin"].includes(user.rol)) {
            limpiarSesionTienda();
            return false;
        }
        sessionStorage.setItem('current_user_id', user.id.toString());
        return true;
    } catch (error) {
        limpiarSesionTienda();
        return false;
    }
}

function limpiarSesionTienda() {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('current_user_id');
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (!checkAuthenticationTienda()) {
        window.location.href = 'login.html';
        return;
    }
    // localStorage.clear(); // Eliminado para no borrar la sesión
    loadUserData();
    loadLibros();
    setupEventListeners();
});

// Cargar datos del usuario
async function loadUserData() {
    try {
        const userDataStr = localStorage.getItem('user_data');
        if (userDataStr) {
            userDataTienda = JSON.parse(userDataStr);
            updateUserInfo();
        }
    } catch (error) {
        console.error('Error cargando datos del usuario:', error);
    }
}

// Actualizar información del usuario en la UI
function updateUserInfo() {
    if (!userDataTienda) return;
    
    const userInfoElement = document.getElementById('userInfo');
    if (userInfoElement) {
        userInfoElement.innerHTML = `
            <p><strong>Usuario:</strong> ${userDataTienda.nombre}</p>
            <p><strong>Rol:</strong> ${userDataTienda.rol}</p>
            ${userDataTienda.rol === 'afiliado' ? `<p><strong>Estado:</strong> ${userDataTienda.estado}</p>` : ''}
        `;
    }
}

// Cargar libros desde la API
async function loadLibros() {
    try {
        showLoading('Cargando libros...');
        
        const response = await fetch('api/libros/disponibles.php');
        const data = await response.json();
        
        if (data.success) {
            mostrarLibros(data.libros);
        } else {
            showError('Error cargando libros');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión');
    } finally {
        hideLoading();
    }
}

// Mostrar libros en la tienda
function mostrarLibros(libros) {
    const contenedor = document.getElementById('contenedorLibros');
    if (!contenedor) return;
    contenedor.innerHTML = '';
    if (!libros || libros.length === 0) {
        contenedor.innerHTML = '<p>No hay libros disponibles.</p>';
        return;
    }
    libros.forEach(libro => {
        const card = document.createElement('div');
        card.className = 'libro-card';
        card.innerHTML = `
            <img src="images/${libro.imagen_portada}" alt="${libro.titulo}" class="libro-portada">
            <h3>${libro.titulo}</h3>
            <p class="libro-autor">${libro.autor_nombre}</p>
            <p class="libro-precio">$${libro.precio.toLocaleString('es-CO')}</p>
        `;
        card.addEventListener('click', function() {
            mostrarModalLibro(libro);
        });
        contenedor.appendChild(card);
    });
}

function mostrarModalLibro(libro) {
    const modal = document.getElementById('detalleLibro');
    if (!modal) return;
    // Imagen grande del libro
    modal.querySelector('.modal-portada').src = 'images/' + libro.imagen_portada;
    // Precio
    modal.querySelector('.modal-precio').textContent = `$${libro.precio.toLocaleString('es-CO')}`;
    // Título y descripción
    modal.querySelector('.modal-titulo').textContent = libro.titulo;
    modal.querySelector('.modal-descripcion').textContent = libro.descripcion;
    // Foto y nombre del autor
    modal.querySelector('.modal-autor-foto').src = 'images/' + libro.autor_foto;
    modal.querySelector('.modal-autor-nombre').textContent = libro.autor_nombre;
    // Bio del autor
    modal.querySelector('.modal-autor-bio').textContent = libro.autor_bio;
    // Mostrar modal
    modal.style.display = 'block';
    // Cerrar modal
    const cerrarBtn = modal.querySelector('#cerrarDetalle');
    if (cerrarBtn) {
        cerrarBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    // Cerrar al hacer clic fuera del modal
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

// Agregar libro al carrito
function agregarAlCarrito(libroId, titulo, precio) {
    const item = {
        id: libroId,
        titulo: titulo,
        precio: precio,
        cantidad: 1
    };
    
    // Verificar si ya está en el carrito
    const existingItem = carrito.find(item => item.id === libroId);
    if (existingItem) {
        existingItem.cantidad++;
    } else {
        carrito.push(item);
    }
    
    updateCarrito();
    showSuccess('Libro agregado al carrito');
}

// Actualizar visualización del carrito
function updateCarrito() {
    const carritoContainer = document.getElementById('carritoContainer');
    if (!carritoContainer) return;
    
    if (carrito.length === 0) {
        carritoContainer.innerHTML = '<p>Tu carrito está vacío</p>';
        return;
    }
    
    let html = '<h3>Tu Carrito</h3>';
    let total = 0;
    
    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        
        html += `
            <div class="carrito-item">
                <h4>${item.titulo}</h4>
                <p>Cantidad: ${item.cantidad}</p>
                <p>Precio: $${item.precio.toLocaleString()} COP</p>
                <p>Subtotal: $${subtotal.toLocaleString()} COP</p>
                <button onclick="removerDelCarrito(${item.id})" class="btn-remover">Remover</button>
            </div>
        `;
    });
    
    html += `
        <div class="carrito-total">
            <h4>Total: $${total.toLocaleString()} COP</h4>
            <button onclick="procesarCompra()" class="btn-procesar">Procesar Compra</button>
        </div>
    `;
    
    carritoContainer.innerHTML = html;
}

// Remover item del carrito
function removerDelCarrito(libroId) {
    carrito = carrito.filter(item => item.id !== libroId);
    updateCarrito();
    showSuccess('Libro removido del carrito');
}

// Procesar compra con PayU
async function procesarCompra() {
    if (!userDataTienda) {
        showError('Debes iniciar sesión para comprar');
        return;
    }
    
    if (carrito.length === 0) {
        showError('Tu carrito está vacío');
        return;
    }
    
    try {
        showLoading('Procesando compra...');
        
        // Por ahora procesamos solo el primer libro del carrito
        // En el futuro se puede expandir para múltiples libros
        const item = carrito[0];
        
        const orderData = {
            libro_id: item.id,
            user_id: userDataTienda.id,
            afiliado_id: getAfiliadoIdFromUrl(), // Obtener de URL si hay referido
            cantidad: item.cantidad
        };
        
        const response = await fetch('api/payu/generar_pago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Crear y enviar formulario a PayU
            const formContainer = document.createElement('div');
            formContainer.innerHTML = data.form_html;
            document.body.appendChild(formContainer);
            
            // El formulario se envía automáticamente
        } else {
            showError(data.error || 'Error procesando la compra');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión');
    } finally {
        hideLoading();
    }
}

// Obtener ID de afiliado desde la URL (si hay referido)
function getAfiliadoIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const ref = urlParams.get('ref');
    
    if (ref) {
        // Aquí podrías hacer una consulta para obtener el afiliado_id por código
        // Por ahora retornamos null
        return null;
    }
    
    return null;
}

// Configurar event listeners
function setupEventListeners() {
    // Event listeners adicionales si los necesitas
}

// Funciones de utilidad para UI
function showLoading(message) {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.textContent = message;
        loadingElement.style.display = 'block';
    }
}

function hideLoading() {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
}

function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'error');
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type}`;
    alertElement.textContent = message;
    
    alertContainer.appendChild(alertElement);
    
    // Remover alerta después de 5 segundos
    setTimeout(() => {
        alertElement.remove();
    }, 5000);
}
