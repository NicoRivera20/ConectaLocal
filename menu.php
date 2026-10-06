<!-- Menú a pantalla completa -->
<div class="offcanvas offcanvas-end cl-menu" tabindex="-1" id="menu" aria-label="Menú principal" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" data-bs-scroll="false">
  <div class="container cl-menu-top">
    <span class="cl-brand">Conecta<span>Local</span></span>
    <button type="button" class="cl-burger" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </div>

  <div class="container cl-menu-main">
    <nav class="cl-menu-links" aria-label="Secciones">
      <!-- Oficios del menú, generados desde la BD para no tener que actualizarlos a mano.
           Cada enlace lleva el filtro en la URL (?oficio=), que buscar.js aplica al cargar. -->
      <?php
      $oficiosMenu = [];
      if (isset($conexion)) {
        $resOficios = $conexion->query("SELECT nombre FROM oficios ORDER BY nombre");
        if ($resOficios) $oficiosMenu = $resOficios->fetch_all(MYSQLI_ASSOC);
      }
      ?>
      <?php if ($oficiosMenu): ?>
      <div class="cl-dropdown">
        <button class="cl-menu-toggle" type="button" id="oficios-dropdown"
                data-bs-toggle="dropdown" data-bs-display="static"
                aria-expanded="false" aria-controls="menu-oficios">
          <span>Explorar oficios</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu cl-dropdown-menu" id="menu-oficios" aria-labelledby="oficios-dropdown">
          <li><a class="dropdown-item" href="oficios.php">Ver todos los oficios</a></li>
          <li><hr class="dropdown-divider"></li>
          <?php foreach ($oficiosMenu as $oficioMenu): ?>
          <li>
            <a class="dropdown-item"
               href="oficios.php?oficio=<?= rawurlencode($oficioMenu['nombre']) ?>"><?= htmlspecialchars($oficioMenu['nombre']) ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- data-open-modal lo atiende el script de abajo: cierra el menú y luego abre el modal -->
      <button class="cl-menu-toggle" type="button" data-open-modal="como-funciona-modal">
        <span>Cómo funciona</span>
        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
      </button>

      <button class="cl-menu-toggle" type="button" data-open-modal="ayuda-modal">
        <span>Centro de Ayuda</span>
        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
      </button>

      <button class="cl-menu-toggle" type="button" data-open-modal="faq-modal">
        <span>Preguntas Frecuentes</span>
        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
      </button>

      <?php if (isset($_SESSION['usuario_id'])): ?>
      <a href="mi_cuenta.php"><span>Mi cuenta</span><i class="bi bi-person-circle" aria-hidden="true"></i></a>
      <a href="logout.php"><span>Cerrar sesión</span><i class="bi bi-box-arrow-right" aria-hidden="true"></i></a>
      <?php endif; ?>
    </nav>

    <div class="cl-menu-foot">
      <a class="btn btn-lg cl-btn-blue" href="registro.php">Ofrece tus servicios <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      <p>Oficios de tu ciudad, a un mensaje de distancia.</p>
      <a href="#" class="small text-muted mt-2 d-inline-block" data-open-modal="terminos-modal">Términos y Privacidad</a>
    </div>
  </div>
</div>

<!-- Modal: Cómo funciona -->
<div class="modal fade" id="como-funciona-modal" tabindex="-1" aria-labelledby="como-funciona-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-light">
      <div class="modal-header border-0">
        <h2 class="modal-title fs-4 fw-bold" id="como-funciona-label">Cómo funciona</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <ol class="list-unstyled cl-steps">
          <li class="d-flex align-items-start mb-3">
            <span class="cl-step-num">1</span>
            <span>Busca tu oficio</span>
          </li>
          <li class="d-flex align-items-start mb-3">
            <span class="cl-step-num">2</span>
            <span>Contacta directo por WhatsApp</span>
          </li>
          <li class="d-flex align-items-start">
            <span class="cl-step-num">3</span>
            <span>Acuerda el precio con el maestro</span>
          </li>
        </ol>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Centro de Ayuda -->
<div class="modal fade" id="ayuda-modal" tabindex="-1" aria-labelledby="ayuda-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-light">
      <div class="modal-header border-0">
        <h2 class="modal-title fs-4 fw-bold" id="ayuda-label">Centro de Ayuda</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary">¿Necesitas ayuda con tu cuenta, tu perfil o una reseña? Escríbenos y un asesor te responde a la brevedad.</p>
        <ul class="list-unstyled m-0">
          <li class="d-flex align-items-start mb-3">
            <span class="cl-step-num"><i class="bi bi-whatsapp"></i></span>
            <span><strong>WhatsApp</strong><br><a href="https://wa.me/56912345678" target="_blank" rel="noopener">+56 9 1234 5678</a></span>
          </li>
          <li class="d-flex align-items-start mb-3">
            <span class="cl-step-num"><i class="bi bi-envelope"></i></span>
            <span><strong>Correo</strong><br><a href="mailto:hola@conectalocal.cl">hola@conectalocal.cl</a></span>
          </li>
          <li class="d-flex align-items-start">
            <span class="cl-step-num"><i class="bi bi-clock"></i></span>
            <span><strong>Horario</strong><br>Lunes a sábado, 9:00 a 18:00 hrs.</span>
          </li>
        </ul>
        <div class="alert alert-warning py-2 mb-0"><strong>Recuerda:</strong> nunca pagues por adelantado. Acuerda el trabajo y el precio por escrito antes de empezar.</div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Preguntas Frecuentes -->
<div class="modal fade" id="faq-modal" tabindex="-1" aria-labelledby="faq-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content bg-light">
      <div class="modal-header border-0">
        <h2 class="modal-title fs-4 fw-bold" id="faq-label">Preguntas Frecuentes</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="accordion accordion-flush" id="faq-acordeon">

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                ¿Qué pasa si un trabajador hace un mal trabajo?
              </button>
            </h3>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">Tu reseña queda visible en su perfil y baja su promedio de estrellas, así otros vecinos saben qué esperar. Si un trabajador acumula malas reseñas, los administradores revisan su cuenta y pueden <strong>eliminar su perfil</strong> de ConectaLocal.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                ¿Usar ConectaLocal tiene algún costo?
              </button>
            </h3>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">No. Buscar, contactar y publicar es gratis tanto para clientes como para trabajadores. El precio del trabajo se acuerda directamente entre ustedes, sin comisiones de por medio.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                ¿Cómo contacto a un trabajador?
              </button>
            </h3>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">Entra a su perfil y presiona «Escribir por WhatsApp». Conversen, acuerden el trabajo y el precio antes de empezar. Te recomendamos <strong>no pagar por adelantado</strong>.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                ¿Cómo dejo una reseña?
              </button>
            </h3>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">Necesitas una cuenta de cliente. Después de recibir el servicio, entra al perfil del trabajador, elige de 1 a 5 estrellas y escribe un comentario. Si ya lo habías calificado, tu reseña se actualiza con la nueva.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                ¿Puedo confiar en los trabajadores de la página?
              </button>
            </h3>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">Cada trabajador tiene estrellas y comentarios de clientes reales, y los administradores revisan los perfiles con malas calificaciones. Aun así, acuerda todo por escrito por WhatsApp y evita pagar por adelantado.</div>
            </div>
          </div>

          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false" aria-controls="faq6">
                Soy trabajador, ¿cómo publico mi servicio?
              </button>
            </h3>
            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faq-acordeon">
              <div class="accordion-body">Crea una cuenta de trabajador, entra a «Mi cuenta» y presiona «Publicar mi perfil». Completa tus datos y tu servicio aparecerá en la búsqueda de tu sector. ¡Es gratis!</div>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Términos y Privacidad -->
<div class="modal fade" id="terminos-modal" tabindex="-1" aria-labelledby="terminos-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content bg-light">
      <div class="modal-header border-0">
        <h2 class="modal-title fs-4 fw-bold" id="terminos-label">Términos y Privacidad</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <h3 class="h6 fw-bold">Uso de ConectaLocal</h3>
        <p class="small">ConectaLocal es un proyecto de práctica que conecta a vecinos con trabajadores locales. Los perfiles los publican los propios usuarios; revisamos las reseñas, pero no garantizamos los trabajos ni los precios publicados.</p>
        <h3 class="h6 fw-bold">Sin pagos por la plataforma</h3>
        <p class="small">No cobramos comisiones ni intervenimos en los pagos. Tú acuerdas el precio directamente con el trabajador por WhatsApp. Nunca pagues por adelantado.</p>
        <h3 class="h6 fw-bold">Tu información</h3>
        <p class="small">Tu correo y tu número de WhatsApp se usan solo para crear tu cuenta y para que otros usuarios te contacten. No los compartimos con terceros ni los usamos para publicidad.</p>
        <h3 class="h6 fw-bold">Tu cuenta</h3>
        <p class="small">Puedes editar tus datos cuando quieras desde «Mi cuenta». Para borrar tu cuenta definitivamente, escríbenos al correo del Centro de Ayuda.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script>
// Abrir modales desde el menú: primero se cierra el menú a pantalla completa y,
// cuando termina de ocultarse (evento hidden.bs.offcanvas), se abre el modal.
// Así el modal nunca queda detrás del menú (el menú usa z-index 1055 y los
// modales 1050). No se usan data-bs-toggle + data-bs-dismiss juntos porque
// Bootstrap resolvería el dismiss con el data-bs-target del modal, no del menú.
document.addEventListener('DOMContentLoaded', function () {
  if (!window.bootstrap) return; // sin Bootstrap no hay menú ni modales que manejar

  var menu = document.getElementById('menu');
  var offcanvas = menu ? bootstrap.Offcanvas.getOrCreateInstance(menu) : null;

  document.querySelectorAll('[data-open-modal]').forEach(function (boton) {
    boton.addEventListener('click', function (e) {
      e.preventDefault();
      var modalEl = document.getElementById(boton.getAttribute('data-open-modal'));
      if (!modalEl) return;

      var abrirModal = function () {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      };

      if (offcanvas && menu.classList.contains('show')) {
        menu.addEventListener('hidden.bs.offcanvas', abrirModal, { once: true });
        offcanvas.hide();
      } else {
        abrirModal();
      }
    });
  });
});
</script>
