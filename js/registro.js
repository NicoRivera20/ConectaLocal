const form = document.querySelector("#form-registro");
const exito = document.querySelector("#exito");
const inputFoto = document.querySelector("#foto");
const vistaPrevia = document.querySelector("#vista-previa");
const descripcion = document.querySelector("#descripcion");
const contadorDesc = document.querySelector("#contador-desc");

// Cada regla recibe el campo y devuelve un mensaje de error ("" = está bien)
const reglas = {
  nombre: (el) => el.value.trim().length < 3 ? "Escribe tu nombre completo." : "",
  whatsapp: (el) => /^9\d{8}$/.test(el.value.replace(/\s/g, ""))
    ? "" : "Escribe 9 dígitos que empiecen con 9. Ej: 9 1234 5678.",
  oficio: (el) => el.value ? "" : "Elige tu oficio.",
  sector: (el) => el.value ? "" : "Elige el sector donde trabajas.",
  tarifa: (el) => el.value.trim().length < 3 ? "Indica una tarifa referencial. Ej: Desde $15.000 la visita." : "",
  descripcion: (el) => el.value.trim().length < 20 ? "Cuéntanos en al menos 20 caracteres qué trabajos haces." : "",
  foto: (el) => {
    const archivo = el.files[0];
    if (!archivo) return "";                                        // la foto es opcional
    if (!["image/jpeg", "image/png"].includes(archivo.type)) return "La foto debe ser JPG o PNG.";
    if (archivo.size > 2 * 1024 * 1024) return "La foto pesa más de 2 MB.";
    return "";
  },
  consentimiento: (el) => el.checked ? "" : "Debes aceptar para publicar tu perfil.",
};

// Marca el campo en rojo (o lo limpia) y escribe el mensaje en su .invalid-feedback
function validarCampo(el) {
  const regla = reglas[el.name];
  if (!regla) return true;
  const mensaje = regla(el);
  const contenedor = el.closest(".mb-3") || el.parentElement;
  contenedor.querySelector(".invalid-feedback").textContent = mensaje;
  el.classList.toggle("is-invalid", mensaje !== "");
  return mensaje === "";
}

// Valida al salir de cada campo y vuelve a validar mientras se corrige
form.querySelectorAll("input, select, textarea").forEach((el) => {
  el.addEventListener("blur", () => validarCampo(el));
  el.addEventListener("input", () => { if (el.classList.contains("is-invalid")) validarCampo(el); });
  el.addEventListener("change", () => { if (el.classList.contains("is-invalid")) validarCampo(el); });
});

descripcion.addEventListener("input", () => {
  contadorDesc.textContent = `${descripcion.value.length} / 200`;
});

// Vista previa de la foto
inputFoto.addEventListener("change", () => {
  if (vistaPrevia.src) URL.revokeObjectURL(vistaPrevia.src);
  const archivo = inputFoto.files[0];
  if (archivo && validarCampo(inputFoto)) {
    vistaPrevia.src = URL.createObjectURL(archivo);
    vistaPrevia.classList.remove("d-none");
  } else {
    vistaPrevia.removeAttribute("src");
    vistaPrevia.classList.add("d-none");
  }
});

form.addEventListener("submit", (e) => {
  // Se validan TODOS los campos (no basta con parar en el primer error)
  const resultados = [...form.elements].filter((el) => el.name).map(validarCampo);
  if (resultados.includes(false)) {
    e.preventDefault();
    form.querySelector(".is-invalid").focus();
    return;
  }

  // Si todo es válido, dejamos que el formulario se envíe por POST a registro.php.
  // El servidor debe volver a validar todo: esta validación se puede saltar desde las herramientas del navegador.
  console.log("Enviando perfil al servidor...");
});