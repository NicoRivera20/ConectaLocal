<?php
session_start();
include 'conexion.php';

// Datos dinámicos para la portada
$totalTrabajadores = (int)$conexion->query("SELECT COUNT(*) c FROM trabajadores")->fetch_assoc()['c'];
$totalOficios      = (int)$conexion->query("SELECT COUNT(*) c FROM oficios")->fetch_assoc()['c'];
$totalSectores     = (int)$conexion->query("SELECT COUNT(*) c FROM sectores")->fetch_assoc()['c'];
$totalResenas      = (int)$conexion->query("SELECT COUNT(*) c FROM resenas")->fetch_assoc()['c'];

// Trabajadores destacados o mejor calificados
$destacados = $conexion->query("
  SELECT t.id, t.nombre, o.nombre AS oficio, s.nombre AS sector, t.tarifa, t.foto,
         (SELECT ROUND(AVG(r.estrellas),1) FROM resenas r WHERE r.trabajador_id = t.id) AS promedio,
         (SELECT COUNT(*) FROM resenas r WHERE r.trabajador_id = t.id) AS total_resenas
  FROM trabajadores t
  JOIN oficios o ON o.id = t.oficio_id
  JOIN sectores s ON s.id = t.sector_id
  WHERE t.disponible = 1
  ORDER BY t.destacado DESC, promedio DESC, total_resenas DESC
  LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <!-- Clave de "mobile first": el ancho de la página sigue al del celular -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ConectaLocal · Oficios de tu barrio</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-icons/css/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
  <style>
    /* ===== Dinamismo de la portada ===== */
    .cl-hero { position: relative; overflow: hidden; }
    .cl-hero::after {
      content: ""; position: absolute; right: -80px; top: -80px;
      width: 260px; height: 260px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,162,39,.35) 0%, transparent 70%);
      animation: clFloat 6s ease-in-out infinite;
    }
    @keyframes clFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(18px)} }

    .cl-fade { opacity: 0; transform: translateY(18px); animation: clFade .7s ease forwards; }
    .cl-fade.d1 { animation-delay: .1s } .cl-fade.d2 { animation-delay: .25s }
    .cl-fade.d3 { animation-delay: .4s }  .cl-fade.d4 { animation-delay: .55s }
    @keyframes clFade { to { opacity: 1; transform: none; } }

    .cl-stat {
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.14);
      border-radius: 14px; padding: 1rem; text-align: center; color: #fff;
      transition: transform .25s ease, border-color .25s ease;
    }
    .cl-stat:hover { transform: translateY(-4px); border-color: var(--cl-gold); }
    .cl-stat .num { font-family: "Bricolage Grotesque", sans-serif; font-size: 2rem; color: var(--cl-gold); }
    .cl-stat small { color: #b7c0cf; }

    .cl-card { transition: transform .25s ease, box-shadow .25s ease; }
    .cl-card:hover { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(11,13,18,.10); }

    .cl-step { display: flex; gap: 1rem; align-items: flex-start; }
    .cl-step-n {
      flex: 0 0 auto; width: 42px; height: 42px; border-radius: 50%;
      background: var(--cl-ink); color: var(--cl-gold); font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }

    .cl-worker { background: #fff; border: 1px solid var(--cl-line); border-radius: 16px; padding: 1.25rem; transition: transform .25s ease, box-shadow .25s ease; height: 100%; }
    .cl-worker:hover { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(11,13,18,.10); }
    .cl-worker img { width: 64px; height: 64px; object-fit: cover; border-radius: 50%; border: 2px solid var(--cl-gold); }

    .cl-stars { color: var(--cl-gold); letter-spacing: 1px; }
  </style>
</head>
<body>

    <header class="cl-topbar">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
      <a class="cl-brand" href="index.php">Conecta<span>Local</span></a>
      <div class="d-flex align-items-center">
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="mi_cuenta.php" class="btn btn-sm cl-btn-gold me-2">Mi cuenta</a>
        <?php else: ?>
        <a href="login.php" class="btn btn-sm cl-btn-gold me-2">Iniciar sesión</a>
        <?php endif; ?>
        <button class="cl-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu"
                aria-controls="menu" aria-label="Abrir menú"><i class="bi bi-list" aria-hidden="true"></i></button>
      </div>
    </div>
  </header>

  <main>
    <section class="cl-hero" id="buscar">
      <div class="container">
        <h1 class="cl-hero-title cl-fade d1">Tu vecindario, a un mensaje de distancia</h1>
        <div class="cl-fade d2" style="width:90px;height:4px;background:var(--cl-gold);border-radius:2px;margin:0 0 1rem;"></div>
        <p class="cl-hero-text cl-fade d3">ConectaLocal conecta a las personas con trabajadores locales de confianza. Escríbeles directo por WhatsApp, sin intermediarios.</p>
        <div class="d-flex flex-column flex-sm-row gap-2 cl-fade d4">
          <a href="oficios.php" class="btn btn-lg fw-bold" style="background:var(--cl-gold);color:var(--cl-ink);border-radius:999px;">Buscar un servicio</a>
          <a href="registro.php" class="btn btn-lg fw-bold cl-btn-gold" style="padding:.5rem 1.5rem;">Ofrece tus servicios</a>
        </div>

        <div class="row g-3 mt-4 pb-2">
          <div class="col-6 col-md-3"><div class="cl-stat"><div class="num"><?= $totalTrabajadores ?></div><small>Trabajadores</small></div></div>
          <div class="col-6 col-md-3"><div class="cl-stat"><div class="num"><?= $totalOficios ?></div><small>Oficios</small></div></div>
          <div class="col-6 col-md-3"><div class="cl-stat"><div class="num"><?= $totalSectores ?></div><small>Sectores</small></div></div>
          <div class="col-6 col-md-3"><div class="cl-stat"><div class="num"><?= $totalResenas ?></div><small>Reseñas</small></div></div>
        </div>
      </div>
    </section>

    <section class="cl-section">
      <div class="container">
        <div class="row g-5 align-items-start">
          <div class="col-lg-6">
            <h2 class="cl-title">¿Por qué existe ConectaLocal?</h2>
            <p class="lead">Encontrar a alguien que arregle tu casa no debería ser tan difícil. Hoy mucha gente pierde tiempo preguntando por WhatsApp a amigos o buscando en redes sociales, donde es fácil toparse con perfiles falsos o tarifas poco claras.</p>
            <p class="lead">Al mismo tiempo, los trabajadores locales de oficios no tienen dónde mostrar su trabajo de forma ordenada y segura.</p>
          </div>

          <div class="col-lg-6">
            <h2 class="cl-title">¿Por qué usar la página?</h2>
            <div class="row g-4 mt-2">
              <div class="col-12">
                <div class="cl-card">
                  <i class="bi bi-geo-alt fs-2 mb-2" style="color:var(--cl-gold);"></i>
                  <h3 class="h6 fw-bold">Cerca de ti</h3>
                  <p class="small text-secondary mb-0">Trabajadores de tu mismo sector, en Temuco y alrededores.</p>
                </div>
              </div>
              <div class="col-12">
                <div class="cl-card">
                  <i class="bi bi-whatsapp fs-2 mb-2" style="color:var(--cl-gold);"></i>
                  <h3 class="h6 fw-bold">Contacto directo</h3>
                  <p class="small text-secondary mb-0">Escribes por WhatsApp y acuerdas el precio tú mismo.</p>
                </div>
              </div>
              <div class="col-12">
                <div class="cl-card">
                  <i class="bi bi-shield-check fs-2 mb-2" style="color:var(--cl-gold);"></i>
                  <h3 class="h6 fw-bold">Perfiles reales</h3>
                  <p class="small text-secondary mb-0">Perfiles visibles con oficio, tarifa y disponibilidad actualizada.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cl-section" style="padding-top:0;">
      <div class="container">
        <h2 class="cl-title">¿Cómo funciona?</h2>
        <div class="row g-4 mt-1">
          <div class="col-md-4">
            <div class="cl-card h-100">
              <div class="cl-step">
                <div class="cl-step-n">1</div>
                <div><h3 class="h6 fw-bold">Busca tu oficio</h3><p class="small text-secondary mb-0">Filtra por oficio y sector en Temuco.</p></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="cl-card h-100">
              <div class="cl-step">
                <div class="cl-step-n">2</div>
                <div><h3 class="h6 fw-bold">Revisa el perfil</h3><p class="small text-secondary mb-0">Mira tarifa, reseñas y disponibilidad.</p></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="cl-card h-100">
              <div class="cl-step">
                <div class="cl-step-n">3</div>
                <div><h3 class="h6 fw-bold">Escríbele por WhatsApp</h3><p class="small text-secondary mb-0">Acuerda el precio directamente.</p></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php if ($destacados && $destacados->num_rows > 0): ?>
    <section class="cl-section" style="padding-top:0;">
      <div class="container">
        <h2 class="cl-title">Trabajadores destacados</h2>
        <div class="row g-4 mt-1">
          <?php while ($t = $destacados->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="cl-worker d-flex flex-column text-center align-items-center">
              <?php if (!empty($t['foto'])): ?>
                <img src="<?= htmlspecialchars($t['foto']) ?>" alt="Foto de <?= htmlspecialchars($t['nombre']) ?>">
              <?php else: ?>
                <span style="width:64px;height:64px;border-radius:50%;background:var(--cl-ink);color:var(--cl-gold);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                  <i class="bi bi-person"></i>
                </span>
              <?php endif; ?>
              <h3 class="h6 fw-bold mt-2 mb-0"><?= htmlspecialchars($t['nombre']) ?></h3>
              <p class="small text-secondary mb-1"><?= htmlspecialchars($t['oficio']) ?> · <?= htmlspecialchars($t['sector']) ?></p>
              <?php if ($t['promedio']): ?>
                <p class="mb-1"><span class="cl-stars">★</span> <?= $t['promedio'] ?> <small class="text-secondary">(<?= $t['total_resenas'] ?>)</small></p>
              <?php else: ?>
                <p class="small text-secondary mb-1">Sin reseñas aún</p>
              <?php endif; ?>
              <p class="small fw-bold mb-3">Tarifa: <?= htmlspecialchars($t['tarifa']) ?></p>
              <a href="perfil.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm cl-btn-dark">Ver perfil</a>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </main>

  <?php include 'menu.php'; ?>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
