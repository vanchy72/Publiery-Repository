document.addEventListener('DOMContentLoaded', () => {
  // Inicializar AOS (animaciones al hacer scroll)
  if (typeof AOS !== 'undefined') {
    AOS.init({
      duration: 800,
      once: true,
    });
  }

  // Cargar estadísticas dinámicas
  async function cargarEstadisticas() {
    const statsContainer = document.querySelector('.stats-grid');
    if (!statsContainer) return;

    try {
      const response = await fetch('api/stats/publicas.php');
      const data = await response.json();

      if (data.success) {
        statsContainer.innerHTML = `
          <div class="stat" data-aos="fade-up">
            <h4>${data.stats.libros_publicados || 0}</h4>
            <p>Libros Publicados</p>
          </div>
          <div class="stat" data-aos="fade-up" data-aos-delay="100">
            <h4>${data.stats.escritores_activos || 0}</h4>
            <p>Escritores Activos</p>
          </div>
          <div class="stat" data-aos="fade-up" data-aos-delay="200">
            <h4>+${(data.stats.ventas_totales || 0).toLocaleString()}</h4>
            <p>Ventas Realizadas</p>
          </div>
        `;
      }
    } catch (error) {
      console.error('Error al cargar estadísticas:', error);
      // Mostrar estadísticas de ejemplo si falla
      statsContainer.innerHTML = `
        <div class="stat" data-aos="fade-up">
          <h4>15+</h4>
          <p>Libros Publicados</p>
        </div>
        <div class="stat" data-aos="fade-up" data-aos-delay="100">
          <h4>25+</h4>
          <p>Escritores Activos</p>
        </div>
        <div class="stat" data-aos="fade-up" data-aos-delay="200">
          <h4>+500</h4>
          <p>Ventas Realizadas</p>
        </div>
      `;
    }
  }

  // Cargar testimonios dinámicos
  async function cargarTestimonios() {
    const testimonialsContainer = document.querySelector('.testimonials .container');
    if (!testimonialsContainer) return;

    try {
      const response = await fetch('api/testimonios/publicos.php');
      const data = await response.json();

      if (data.success && data.testimonios.length > 0) {
        const testimoniosHTML = data.testimonios.map((t, index) => `
          <div class="testimonial" data-aos="fade-up" data-aos-delay="${index * 100}">
            <img src="${t.imagen_url || 'images/default-author.jpg'}" alt="Foto de ${t.nombre_autor}">
            <p>"${t.testimonio}"</p>
            <span>${t.nombre_autor}, ${t.rol || 'Escritor'}</span>
          </div>
        `).join('');
        
        // Buscar donde insertar los testimonios
        const existingTestimonials = testimonialsContainer.querySelector('.testimonials-grid');
        if (existingTestimonials) {
          existingTestimonials.innerHTML = testimoniosHTML;
        } else {
          testimonialsContainer.insertAdjacentHTML('beforeend', `<div class="testimonials-grid">${testimoniosHTML}</div>`);
        }
      }
    } catch (error) {
      console.error('Error al cargar testimonios:', error);
      // No hacer nada si falla, mantener el contenido estático
    }
  }

  // Cargar datos
  cargarEstadisticas();
  cargarTestimonios();
});