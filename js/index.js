// Funciones para cargar contenido dinámico en index.html

// Función para cargar testimonios dinámicamente
async function cargarTestimonios() {
    try {
        const response = await fetch('api/testimonios/obtener_testimonios.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const testimoniosContainer = document.querySelector('.testimonials .container');
            
            // Limpiar testimonios existentes (mantener el título)
            const titulo = testimoniosContainer.querySelector('h3');
            testimoniosContainer.innerHTML = '';
            testimoniosContainer.appendChild(titulo);
            
            // Crear testimonios dinámicos
            data.data.forEach((testimonio, index) => {
                const testimonioElement = document.createElement('div');
                testimonioElement.className = 'testimonial';
                testimonioElement.setAttribute('data-aos', index % 2 === 0 ? 'fade-right' : 'fade-left');
                
                let imagenHTML = '';
                if (testimonio.imagen) {
                    imagenHTML = `<img src="${testimonio.imagen}" alt="${testimonio.nombre}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px; float: left;">`;
                }
                
                testimonioElement.innerHTML = `
                    ${imagenHTML}
                    <p>"${testimonio.texto}"</p>
                    <span>- ${testimonio.nombre}, ${testimonio.rol}</span>
                `;
                
                testimoniosContainer.appendChild(testimonioElement);
            });
        }
    } catch (error) {
        console.error('Error al cargar testimonios:', error);
    }
}

// Función para cargar estadísticas dinámicamente
async function cargarEstadisticas() {
    try {
        const response = await fetch('api/estadisticas/obtener_estadisticas.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const statsContainer = document.querySelector('.stats-grid');
            
            // Limpiar estadísticas existentes
            statsContainer.innerHTML = '';
            
            // Crear estadísticas dinámicas
            data.data.forEach((estadistica, index) => {
                const statElement = document.createElement('div');
                statElement.className = 'stat';
                statElement.setAttribute('data-aos', 'zoom-in');
                statElement.setAttribute('data-aos-delay', (index + 1) * 100);
                
                statElement.innerHTML = `
                    <h4>
                        <span class="counter" data-target="${estadistica.valor}">${estadistica.valor}</span>${estadistica.sufijo}
                    </h4>
                    <p>${estadistica.titulo}</p>
                `;
                
                statsContainer.appendChild(statElement);
            });
            
            // Reinicializar animaciones de contadores
            inicializarContadores();
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// Función para animar contadores
function inicializarContadores() {
    const counters = document.querySelectorAll('.counter');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60 FPS
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        // Iniciar animación cuando el elemento sea visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
}

// Función para manejar errores de carga
function mostrarError(mensaje) {
    console.error(mensaje);
    // Aquí podrías mostrar un mensaje de error en la UI si es necesario
}

// Función principal para inicializar todo el contenido dinámico
async function inicializarContenidoDinamico() {
    try {
        // Cargar testimonios y estadísticas en paralelo
        await Promise.all([
            cargarTestimonios(),
            cargarEstadisticas()
        ]);
        
        console.log('Contenido dinámico cargado exitosamente');
    } catch (error) {
        console.error('Error al cargar contenido dinámico:', error);
        mostrarError('Error al cargar algunos elementos de la página');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar AOS (Animate On Scroll) si está disponible
    if (typeof AOS !== 'undefined') {
        AOS.init();
    }
    
    // Cargar contenido dinámico
    inicializarContenidoDinamico();
});

// Exportar funciones para uso global si es necesario
window.cargarTestimonios = cargarTestimonios;
window.cargarEstadisticas = cargarEstadisticas;
window.inicializarContenidoDinamico = inicializarContenidoDinamico; 