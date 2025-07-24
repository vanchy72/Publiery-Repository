// Función para subir libro
async function subirLibro(formData) {
  try {
    console.log('Iniciando subida de libro...');
    console.log('FormData contenido:');
    for (let [key, value] of formData.entries()) {
      console.log(key + ': ' + value);
    }

    const response = await fetch('api/escritores/subir_libro.php', {
      method: 'POST',
      body: formData
    });

    console.log('Respuesta del servidor:', response);
    console.log('Status:', response.status);
    console.log('Headers:', response.headers);

    const data = await response.json();
    console.log('Datos recibidos:', data);
    
    if (data.success) {
      alert(data.message);
      document.querySelector(".book-form").reset();
      
      // Si la cuenta fue activada, mostrar mensaje adicional
      if (data.cuenta_activada) {
        console.log('¡Cuenta activada automáticamente!');
      }
      
      // Recargar la lista de libros si existe
      if (typeof cargarLibros === 'function') {
        cargarLibros();
      }
    } else {
      alert('Error: ' + (data.error || 'Error desconocido'));
    }
  } catch (error) {
    console.error('Error completo:', error);
    console.error('Error message:', error.message);
    console.error('Error stack:', error.stack);
    alert('Error de conexión. Intenta nuevamente. Revisa la consola para más detalles.');
  }
}

// Manejar envío del formulario
document.querySelector(".book-form").addEventListener("submit", function (e) {
  e.preventDefault();
  
  console.log('Formulario enviado');
  
  const formData = new FormData(this);
  
  // Validar campos obligatorios
  const titulo = formData.get('titulo');
  const descripcion = formData.get('descripcion');
  const precio = formData.get('precio');
  const archivoPdf = formData.get('archivo_pdf');
  
  console.log('Datos del formulario:');
  console.log('Título:', titulo);
  console.log('Descripción:', descripcion);
  console.log('Precio:', precio);
  console.log('Archivo PDF:', archivoPdf);
  
  if (!titulo || !descripcion || !precio || !archivoPdf.name) {
    alert('Por favor completa todos los campos obligatorios.');
    console.error('Validación fallida: campos vacíos');
    return;
  }
  
  // Validar precio
  if (parseFloat(precio) <= 0) {
    alert('El precio debe ser mayor a 0.');
    console.error('Validación fallida: precio inválido');
    return;
  }
  
  // Validar archivo PDF
  if (archivoPdf.size > 10 * 1024 * 1024) {
    alert('El archivo PDF no puede superar 10MB.');
    console.error('Validación fallida: archivo muy grande');
    return;
  }
  
  console.log('Validación exitosa, iniciando subida...');
  
  // Subir libro
  subirLibro(formData);
});

// Gráfico de ventas
const ventasChart = document.getElementById('ventasChart');
if (ventasChart) {
  const ctx = ventasChart.getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Mi Primer Ebook', 'Consejos para Emprender', 'Marketing para Autores'],
    datasets: [{
      label: 'Ventas',
      data: [32, 20, 15],
      backgroundColor: '#5a67d8'
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true
      }
    }
  }
});
}
// Verificar sesión al cargar la página
async function verificarSesion() {
  try {
    const response = await fetch('verificar_sesion_escritor.php');
    const data = await response.json();
    console.log('Estado de sesión:', data);
    
    if (!data.is_authenticated || data.user_role !== 'escritor') {
      alert('No tienes permisos para acceder a esta página. Redirigiendo al login...');
      window.location.href = 'login.html';
    }
  } catch (error) {
    console.error('Error verificando sesión:', error);
  }
}

// Verificar sesión al cargar
verificarSesion();

// Redirigir a la landing page al cerrar sesión
document.getElementById("logoutBtn").addEventListener("click", function () {
  window.location.href = "index.html";
});

// ========================================
// FUNCIONALIDAD DE TESTIMONIOS
// ========================================

// Configurar contador de caracteres para testimonios
document.addEventListener('DOMContentLoaded', function() {
  const textarea = document.getElementById('testimonioTexto');
  const charCount = document.querySelector('.char-count');
  
  if (textarea && charCount) {
    textarea.addEventListener('input', function() {
      const length = this.value.length;
      charCount.textContent = `${length}/500 caracteres`;
      
      // Cambiar color según el límite
      charCount.classList.remove('near-limit', 'at-limit');
      if (length >= 450) {
        charCount.classList.add('near-limit');
        charCount.style.color = '#f59e0b';
      }
      if (length >= 500) {
        charCount.classList.add('at-limit');
        charCount.style.color = '#dc2626';
      }
    });
  }
  
  // Configurar formulario de testimonios
  const formTestimonio = document.getElementById('formTestimonio');
  if (formTestimonio) {
    formTestimonio.addEventListener('submit', handleSubmitTestimonio);
  }
});

async function handleSubmitTestimonio(event) {
  event.preventDefault();
  
  const form = event.target;
  const submitBtn = document.getElementById('btnEnviarTestimonio');
  const mensajeDiv = document.getElementById('mensajeTestimonio');
  
  // Validar formulario
  const testimonio = document.getElementById('testimonioTexto').value.trim();
  const aceptarPublicacion = document.getElementById('aceptarPublicacion').checked;
  
  if (!testimonio) {
    showMensajeTestimonio('Por favor escribe tu testimonio', 'error');
    return;
  }
  
  if (testimonio.length < 50) {
    showMensajeTestimonio('El testimonio debe tener al menos 50 caracteres', 'error');
    return;
  }
  
  if (!aceptarPublicacion) {
    showMensajeTestimonio('Debes aceptar que tu testimonio sea publicado', 'error');
    return;
  }
  
  // Deshabilitar botón y mostrar carga
  submitBtn.disabled = true;
  submitBtn.textContent = 'Enviando...';
  
  try {
    // Crear FormData para enviar archivos
    const formData = new FormData();
    formData.append('testimonio', testimonio);
    
    const imagenInput = document.getElementById('testimonioImagen');
    if (imagenInput.files.length > 0) {
      formData.append('imagen', imagenInput.files[0]);
    }
    
    // Enviar testimonio
    const response = await fetch('api/testimonios/enviar_testimonio.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      showMensajeTestimonio(data.message, 'success');
      form.reset();
      document.querySelector('.char-count').textContent = '0/500 caracteres';
      document.querySelector('.char-count').style.color = '#6b7280';
    } else {
      showMensajeTestimonio(data.error || 'Error al enviar testimonio', 'error');
    }
  } catch (error) {
    console.error('Error enviando testimonio:', error);
    showMensajeTestimonio('Error de conexión', 'error');
  } finally {
    // Restaurar botón
    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar Testimonio';
  }
}

function showMensajeTestimonio(mensaje, tipo) {
  const mensajeDiv = document.getElementById('mensajeTestimonio');
  if (mensajeDiv) {
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = 'mensaje';
    mensajeDiv.style.display = 'block';
    
    // Aplicar estilos según el tipo
    if (tipo === 'success') {
      mensajeDiv.style.background = '#dcfce7';
      mensajeDiv.style.color = '#166534';
      mensajeDiv.style.border = '1px solid #bbf7d0';
    } else if (tipo === 'error') {
      mensajeDiv.style.background = '#fef2f2';
      mensajeDiv.style.color = '#dc2626';
      mensajeDiv.style.border = '1px solid #fecaca';
    } else {
      mensajeDiv.style.background = '#dbeafe';
      mensajeDiv.style.color = '#1e40af';
      mensajeDiv.style.border = '1px solid #bfdbfe';
    }
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
      mensajeDiv.style.display = 'none';
    }, 5000);
  }
}
