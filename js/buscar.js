// Filtra las tarjetas ya renderizadas por PHP (sin recargar la página)
const textoInput = document.querySelector("#texto");
const sectorInput = document.querySelector("#sector");
const soloDisponibles = document.querySelector("#solo-disponibles");
const tarjetas = document.querySelectorAll("#tarjetas > div[data-oficio]");
const chips = document.querySelector("#chips");
const contador = document.querySelector("#contador");
const vacio = document.querySelector("#vacio");
let oficioActivo = "Todos";

// Quita tildes y pasa a minúsculas: así "gasfiteria" también encuentra "Gasfitería"
const normalizar = (t) => t.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

// Si la URL trae ?oficio=Nombre (enlaces del menú "Explorar oficios"), se activa ese
// chip al cargar. Se comparan los nombres normalizados porque llegan con tildes y
// capitalización distintas a las que trae el chip.
const oficioUrl = new URLSearchParams(window.location.search).get("oficio");
if (oficioUrl) {
  const chipUrl = Array.from(chips.querySelectorAll("button[data-oficio]")).find(
    (c) => normalizar(c.dataset.oficio) === normalizar(oficioUrl)
  );
  if (chipUrl) {
    oficioActivo = chipUrl.dataset.oficio;
    chips.querySelectorAll(".cl-chip").forEach((c) => {
      c.classList.toggle("activo", c === chipUrl);
      c.setAttribute("aria-pressed", c === chipUrl);
    });
  }
}

function filtrar() {
  const texto = normalizar(textoInput.value.trim());
  const sector = sectorInput.value;
  const soloDispo = soloDisponibles.checked;
  let visibles = 0;

  tarjetas.forEach((card) => {
    const coincideTexto = !texto || normalizar(card.textContent).includes(texto);
    const coincideSector = !sector || card.dataset.sector === sector;
    const coincideOficio = oficioActivo === "Todos" || card.dataset.oficio === oficioActivo;
    const coincideDispo = !soloDispo || card.dataset.disponible === "1";
    const mostrar = coincideTexto && coincideSector && coincideOficio && coincideDispo;

    card.style.display = mostrar ? "" : "none";
    if (mostrar) visibles++;
  });

  contador.textContent = `${visibles} ${visibles === 1 ? "experto encontrado" : "expertos encontrados"} cerca de ti`;
  vacio.classList.toggle("d-none", visibles > 0);
}

chips.addEventListener("click", (e) => {
  const boton = e.target.closest("button[data-oficio]");
  if (!boton) return;
  oficioActivo = boton.dataset.oficio;
  chips.querySelectorAll(".cl-chip").forEach((c) => {
    c.classList.toggle("activo", c === boton);
    c.setAttribute("aria-pressed", c === boton);
  });
  filtrar();
});

textoInput.addEventListener("input", filtrar);
sectorInput.addEventListener("change", filtrar);
soloDisponibles.addEventListener("change", filtrar);

document.querySelector("#form-busqueda").addEventListener("submit", (e) => {
  e.preventDefault();
  filtrar();
  document.querySelector("#oficios").scrollIntoView({ behavior: "smooth" });
});

filtrar();
