document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const libroId = params.get("libro"); // Cambiado de "id" a "libro"
  const referidoPor = params.get("ref"); // Código de afiliado referidor
  const campanaId = params.get("campaign"); // ID de campaña si aplica

  console.log('🚀 Página de pago cargada');
  console.log('📦 Parámetros URL:', window.location.search);
  console.log('🔗 ID del libro:', libroId);
  console.log('👥 Referido por:', referidoPor);
  console.log('📢 Campaña:', campanaId);
  console.log('🔗 Tipo de ID:', typeof libroId);

  if (!libroId) {
    alert("❌ No se ha especificado un libro para comprar.");
    window.location.href = "dashboard-afiliado.html";
    return;
  }

  // Cargar libro desde la API en lugar del JSON
  console.log('🌐 Cargando libros desde API...');
  fetch("api/libros/disponibles.php")
    .then(res => {
      console.log('💡 Respuesta de API recibida:', res.status);
      return res.json();
    })
    .then(data => {
      console.log('📚 Datos de API:', data);
      if (!data.success) {
        throw new Error(data.error || 'Error cargando libros');
      }
      console.log('📚 Libros disponibles:', data.libros);
      console.log('🔍 Buscando libro con ID:', libroId);
      // Convertir libroId a número para comparación
      const libroIdNum = parseInt(libroId);
      console.log('🔢 ID convertido a número:', libroIdNum);
      const libro = data.libros.find(l => {
        console.log('🔎 Comparando:', l.id, 'con', libroIdNum, 'Tipo:', typeof l.id, 'vs', typeof libroIdNum);
        return l.id == libroIdNum || l.id == libroId; // Comparar tanto como número como cadena
      });
      if (!libro) {
        console.error('❌ Libro no encontrado. IDs disponibles:', data.libros.map(l => l.id));
        alert("❌ Libro no encontrado.");
        window.location.href = "dashboard-afiliado.html";
        return;
      }
      console.log('📖 Libro encontrado:', libro);
      // Validar que los datos necesarios existen
      if (!libro.titulo) {
        throw new Error('El libro no tiene título');
      }
      if (!libro.precio_afiliado && libro.precio_afiliado !== 0) {
        console.warn('⚠️ precio_afiliado no encontrado, usando precio normal');
        libro.precio_afiliado = libro.precio || 0;
      }
      console.log('💲 Precio afiliado:', libro.precio_afiliado);
      // Rellenar datos en HTML con manejo de errores
      try {
        const portadaElement = document.getElementById("libroPortada");
        const tituloElement = document.getElementById("libroTitulo");
        const descripcionElement = document.getElementById("libroDescripcion");
        const precioElement = document.getElementById("libroPrecio");
        const resumenTituloElement = document.getElementById("resumenTitulo");
        const resumenPrecioElement = document.getElementById("resumenPrecio");
        const btnPagarElement = document.getElementById("btnPagar");
        if (portadaElement) {
          portadaElement.src = `images/${libro.imagen_portada || 'default-book.jpg'}`;
        }
        if (tituloElement) {
          tituloElement.textContent = libro.titulo;
        }
        if (descripcionElement) {
          descripcionElement.textContent = libro.descripcion || 'Sin descripción disponible';
        }
        if (precioElement) {
          precioElement.textContent = `$${libro.precio_afiliado.toLocaleString()}`;
        }
        if (resumenTituloElement) {
          resumenTituloElement.textContent = libro.titulo;
        }
        if (resumenPrecioElement) {
          resumenPrecioElement.textContent = `$${libro.precio_afiliado.toLocaleString()}`;
        }
        // Integrarse con PayU
        if (btnPagarElement) {
          btnPagarElement.addEventListener("click", async () => {
            console.log('🟢 Evento click en btnPagar detectado');
            console.log('Procesando pago para libro:', libroId);
            // Mostrar cargando
            btnPagarElement.disabled = true;
            btnPagarElement.textContent = 'Procesando...';
            try {
              // Obtener ID del usuario desde localStorage
              const userId = localStorage.getItem('userId');
              const afiliadoId = localStorage.getItem('afiliado_id'); // si aplica
              if (!userId) {
                throw new Error('Usuario no autenticado');
              }
              const token = localStorage.getItem('session_token'); // o el nombre que uses
              const headers = {
                'Content-Type': 'application/json'
              };
              if (token) {
                headers['Authorization'] = 'Bearer ' + token;
              }

              // Enviar datos a la API de PayU
              const response = await fetch('api/payu/generar_pago.php', {
                method: 'POST',
                headers,
                credentials: 'include', // para máxima compatibilidad
                body: JSON.stringify({
                  libro_id: libroId,
                  user_id: userId,
                  afiliado_id: afiliadoId,
                  referido_por: referidoPor, // Agregar código de referido
                  campana_id: campanaId, // Agregar ID de campaña
                  cantidad: 1
                })
              });
              const data = await response.json();
              if (data.success) {
                // Crear formulario para enviar a PayU
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = data.payu_url;
                form.style.display = 'none';
                // Agregar campos de PayU
                Object.keys(data.payu_data).forEach(key => {
                  const input = document.createElement('input');
                  input.type = 'hidden';
                  input.name = key;
                  input.value = data.payu_data[key];
                  form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
              } else {
                throw new Error(data.error || 'Error al procesar el pago');
              }
            } catch (error) {
              console.error('Error:', error);
              alert('Error al procesar el pago: ' + error.message);
              // Botón Restaurar
              btnPagarElement.disabled = false;
              btnPagarElement.textContent = 'Pagar Ahora';
            }
          });
        }
        console.log('✅ Datos cargados correctamente en el HTML');
      } catch (error) {
        console.error('❌ Error al cargar datos en HTML:', error);
        throw new Error('Error al cargar la información del libro en la página');
      }
    })
    .catch(err => {
      console.error("❌ Error cargando libro:", err);
      console.error("❌ Seguimiento de la pila:", err.stack);
      alert("No se pudo cargar el libro: " + err.message);
      window.location.href = "dashboard-afiliado.html";
    });
});
