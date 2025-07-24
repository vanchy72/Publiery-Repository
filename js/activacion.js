document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formActivacion");
  const contrato = document.getElementById("contratoAfiliado");
  const listaLibros = document.getElementById("listaLibros");

  const librosDisponibles = [
    { id: "libro1", titulo: "Manual de Ventas", precio: 20000 },
    { id: "paquete1", titulo: "Pack 3 Libros Éxito", precio: 50000 }
  ];

  // Mostrar productos disponibles
  librosDisponibles.forEach(libro => {
    const div = document.createElement("div");
    div.classList.add("libro-item");
    div.innerHTML = `
      <label>
        <input type="radio" name="producto" value="${libro.id}" required>
        ${libro.titulo} - $${libro.precio.toLocaleString()}
      </label>
    `;
    listaLibros.appendChild(div);
  });

  // Mostrar/ocultar contrato si elige opción de red
  form.tipo_activacion.forEach(radio => {
    radio.addEventListener("change", () => {
      if (radio.value === "afiliado" && radio.checked) {
        contrato.classList.remove("oculto");
      } else {
        contrato.classList.add("oculto");
      }
    });
  });

  form.addEventListener("submit", e => {
    e.preventDefault();

    const tipo = form.tipo_activacion.value;
    const producto = form.producto.value;

    if (tipo === "afiliado" && !form.acepta_contrato.checked) {
      alert("Debes aceptar el contrato para unirte a la red.");
      return;
    }

    const activacion = {
      tipo_activacion: tipo,
      producto,
      estado: "activo",
      fecha: new Date().toISOString()
    };

            // Activación completada

    alert("Tu cuenta ha sido activada correctamente.");
    window.location.href = "dashboard-afiliado.html";
  });
});
