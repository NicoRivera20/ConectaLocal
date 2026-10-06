// Muestra u oculta lo que se escribe en los campos de contraseña.
// El botón que activa esto se marca con data-ver-contrasena="id-del-input".
document.querySelectorAll("[data-ver-contrasena]").forEach((boton) => {
  const input = document.getElementById(boton.dataset.verContrasena);
  if (!input) return;

  const iconoMostrar = boton.querySelector("[data-icono='mostrar']");
  const iconoOcultar = boton.querySelector("[data-icono='ocultar']");

  boton.addEventListener("click", () => {
    const visible = input.type === "text";

    input.type = visible ? "password" : "text";
    boton.setAttribute("aria-pressed", String(!visible));
    boton.setAttribute(
      "aria-label",
      visible ? "Mostrar contraseña" : "Ocultar contraseña"
    );
    iconoMostrar.classList.toggle("d-none", !visible);
    iconoOcultar.classList.toggle("d-none", visible);

    // Al cambiar de tipo, el navegador puede vaciar la selección: se deja el
    // cursor al final para poder seguir escribiendo sin interrupciones.
    input.focus();
  });
});