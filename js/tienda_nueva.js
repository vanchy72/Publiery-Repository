/**
 * Tienda JavaScript - Versión Corregida
 * Maneja la visualización de libros en la tienda principal
 */

console.log('📚 tienda.js v2.0 - Iniciando...');

// Variables globales
let librosDisponibles = [];
let modalLibroActual = null;

// ========================================
// INICIALIZACIÓN
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 DOM cargado - Iniciando tienda...');
    
    // Verificar que estamos en la página correcta
    if (document.getElementById('libros-container') || document.getElementById('catalogo-libros')) {
        cargarLibros();
    } else {
        console.log('📖 Contenedor de libros no encontrado, esperando...');
        // Intentar de nuevo después de un momento
        setTimeout(() => {
            if (document.getElementById('libros-container') || document.getElementById('catalogo-libros')) {
                cargarLibros();
            }
        }, 1000);
    }
});

// ========================================
// FUNCIONES PRINCIPALES
// ========================================

/**
 * Cargar libros desde la API
 */
async function cargarLibros() {
    console.log('📥 Cargando libros desde API...');
    
    try {
        // Mostrar indicador de carga
        mostrarCargando();
        
        const response = await fetch('/publiery/api/libros/disponibles.php');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('📊 Respuesta de API:', data);
        
        if (data.success && data.libros) {
            librosDisponibles = data.libros;
            mostrarLibros(data.libros);
            console.log(`✅ ${data.libros.length} libros cargados exitosamente`);
        } else {
            throw new Error(data.error || 'Respuesta inválida de la API');
        }
        
    } catch (error) {
        console.error('❌ Error cargando libros:', error);
        mostrarError('Error al cargar los libros: ' + error.message);
    }
}

/**
 * Mostrar indicador de carga
 */
function mostrarCargando() {
    const container = obtenerContenedor();
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p class="mt-3">Cargando libros disponibles...</p>
            </div>
        `;
    }
}

/**
 * Mostrar error
 */
function mostrarError(mensaje) {
    const container = obtenerContenedor();
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger text-center" role="alert">
                <h4 class="alert-heading">Error de Conexión</h4>
                <p>${mensaje}</p>
                <hr>
                <button class="btn btn-outline-danger" onclick="cargarLibros()">
                    <i class="fas fa-sync-alt"></i> Reintentar
                </button>
            </div>
        `;
    }
}

/**
 * Obtener el contenedor de libros
 */
function obtenerContenedor() {
    return document.getElementById('libros-container') || 
           document.getElementById('catalogo-libros') ||
           document.querySelector('.libros-grid') ||
           document.querySelector('#main-content .container');
}

/**
 * Mostrar libros en la interfaz
 */
function mostrarLibros(libros) {
    const container = obtenerContenedor();
    
    if (!container) {
        console.error('❌ Contenedor de libros no encontrado');
        return;
    }
    
    if (!libros || libros.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h3>No hay libros disponibles</h3>
                <p class="text-muted">Vuelve pronto para ver nuevas publicaciones</p>
            </div>
        `;
        return;
    }
    
    console.log(`📚 Mostrando ${libros.length} libros`);
    
    // Generar HTML para cada libro
    const librosHTML = libros.map(libro => generarHTMLLibro(libro)).join('');
    
    // Usar grid responsive
    container.innerHTML = `
        <div class="row">
            ${librosHTML}
        </div>
    `;
    
    console.log('✅ Libros mostrados correctamente');
}

/**
 * Generar HTML para un libro individual
 */
function generarHTMLLibro(libro) {
    // Determinar la imagen a usar
    let imagenSrc = '/publiery/images/default-book.jpg';
    if (libro.imagen_portada && libro.imagen_portada !== 'default-book.jpg') {
        imagenSrc = `/publiery/uploads/portadas/${libro.imagen_portada}`;
    }
    
    return `
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 libro-card" data-libro-id="${libro.id}">
                <div class="position-relative">
                    <img src="${imagenSrc}" 
                         class="card-img-top libro-imagen" 
                         alt="${libro.titulo}"
                         onerror="this.src='/publiery/images/default-book.jpg'"
                         style="height: 250px; object-fit: cover;">
                    <div class="card-img-overlay d-flex align-items-end p-0">
                        <button class="btn btn-primary btn-sm m-2" 
                                onclick="mostrarModalLibro(${libro.id})" 
                                style="margin-left: auto;">
                            <i class="fas fa-eye"></i> Ver más
                        </button>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${libro.titulo}</h5>
                    <p class="card-text text-muted">Por ${libro.autor_nombre}</p>
                    <p class="card-text small flex-grow-1">
                        ${libro.descripcion ? libro.descripcion.substring(0, 100) + '...' : 'Descripción no disponible'}
                    </p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-success">$${libro.precio}</span>
                            <button class="btn btn-success btn-sm" onclick="comprarLibro(${libro.id})">
                                <i class="fas fa-shopping-cart"></i> Comprar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Mostrar modal con detalles del libro
 */
function mostrarModalLibro(libroId) {
    console.log(`📖 Mostrando modal para libro ID: ${libroId}`);
    
    const libro = librosDisponibles.find(l => l.id == libroId);
    if (!libro) {
        console.error('❌ Libro no encontrado:', libroId);
        return;
    }
    
    modalLibroActual = libro;
    
    // Determinar imagen
    let imagenSrc = '/publiery/images/default-book.jpg';
    if (libro.imagen_portada && libro.imagen_portada !== 'default-book.jpg') {
        imagenSrc = `/publiery/uploads/portadas/${libro.imagen_portada}`;
    }
    
    // Crear modal si no existe
    let modal = document.getElementById('modalLibro');
    if (!modal) {
        modal = crearModalLibro();
    }
    
    // Llenar contenido del modal
    modal.querySelector('.modal-title').textContent = libro.titulo;
    modal.querySelector('#modal-libro-imagen').src = imagenSrc;
    modal.querySelector('#modal-libro-autor').textContent = libro.autor_nombre;
    modal.querySelector('#modal-libro-precio').textContent = `$${libro.precio}`;
    modal.querySelector('#modal-libro-descripcion').textContent = libro.descripcion || 'Descripción no disponible';
    modal.querySelector('#modal-libro-fecha').textContent = libro.fecha_publicacion ? 
        new Date(libro.fecha_publicacion).toLocaleDateString() : 'Fecha no disponible';
    
    // Actualizar botón de compra
    const btnComprar = modal.querySelector('#modal-btn-comprar');
    btnComprar.onclick = () => comprarLibro(libro.id);
    
    // Mostrar modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

/**
 * Crear modal para libros si no existe
 */
function crearModalLibro() {
    const modalHTML = `
        <div class="modal fade" id="modalLibro" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <img id="modal-libro-imagen" class="img-fluid" style="width: 100%; height: 300px; object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                                <h6>Autor</h6>
                                <p id="modal-libro-autor"></p>
                                
                                <h6>Precio</h6>
                                <p class="h4 text-success" id="modal-libro-precio"></p>
                                
                                <h6>Descripción</h6>
                                <p id="modal-libro-descripcion"></p>
                                
                                <h6>Fecha de Publicación</h6>
                                <p id="modal-libro-fecha"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" id="modal-btn-comprar">
                            <i class="fas fa-shopping-cart"></i> Comprar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    return document.getElementById('modalLibro');
}

/**
 * Función para comprar libro
 */
function comprarLibro(libroId) {
    console.log(`💳 Comprando libro ID: ${libroId}`);
    
    const libro = librosDisponibles.find(l => l.id == libroId);
    if (!libro) {
        alert('Error: Libro no encontrado');
        return;
    }
    
    // Aquí iría la lógica de compra real
    alert(`¡Gracias por tu interés en "${libro.titulo}"!\n\nPronto implementaremos el sistema de compras.`);
}

// ========================================
// EXPORTAR FUNCIONES GLOBALES
// ========================================

window.cargarLibros = cargarLibros;
window.mostrarLibros = mostrarLibros;
window.mostrarModalLibro = mostrarModalLibro;
window.comprarLibro = comprarLibro;

console.log('🌐 Funciones de tienda exportadas globalmente');
console.log('✅ tienda.js cargado completamente');