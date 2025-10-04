document.addEventListener('DOMContentLoaded', () => {
  // Inicializar AOS (animaciones al hacer scroll)
  AOS.init({
    duration: 800,
    once: true,
  });

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
      statsContainer.innerHTML = '<p>No se pudieron cargar las estadísticas.</p>';
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
            <img src="${t.imagen_url || 'images/default-avatar.png'}" alt="Foto de ${t.nombre_autor}">
            <p>"${t.testimonio}"</p>
            <span>${t.nombre_autor}, Escritor</span>
          </div>
        `).join('');
        testimonialsContainer.insertAdjacentHTML('beforeend', testimoniosHTML);
      }
    } catch (error) {
      console.error('Error al cargar testimonios:', error);
    }
  }

  cargarEstadisticas();
  cargarTestimonios();
});

