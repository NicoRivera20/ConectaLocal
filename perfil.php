<?php
session_start();
include 'conexion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sin sesión iniciada no se puede contactar, pero el perfil sí se muestra
$logueado = isset($_SESSION['usuario_id']);

// El perfil se carga siempre: nombre, oficio, sector, tarifa y descripción son
// datos públicos y son los que le sirven al visitante para decidir si entrar.
// El WhatsApp NO se consulta sin sesión, así nunca viaja al navegador de alguien
// que no entró. Quien no tiene cuenta ve el perfil con el contacto bloqueado.
$t = null;
if ($id > 0) {
    $stmt = $conexion->prepare("SELECT t.nombre, o.nombre AS oficio, s.nombre AS sector, t.tarifa, t.descripcion, t.disponible, t.destacado
            FROM trabajadores t
            JOIN oficios o ON o.id = t.oficio_id
            JOIN sectores s ON s.id = t.sector_id
            WHERE t.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();

    if ($t && $logueado) {
        $stmtW = $conexion->prepare("SELECT whatsapp FROM trabajadores WHERE id = ?");
        $stmtW->bind_param("i", $id);
        $stmtW->execute();
        $filaW = $stmtW->get_result()->fetch_assoc();
        if ($filaW) $t['whatsapp'] = $filaW['whatsapp'];
    }
}

// Guardar la reseña enviada por el formulario (solo clientes con sesión)
$errorResena = '';
if ($t && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estrellas'])) {
    if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
        $errorResena = 'Solo los clientes con cuenta pueden dejar una reseña.';
    } else {
        $estrellas  = (int)($_POST['estrellas'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');
        if ($estrellas < 1 || $estrellas > 5) {
            $errorResena = 'Elige de 1 a 5 estrellas.';
        } elseif (mb_strlen($comentario) > 300) {
            $errorResena = 'El comentario no puede superar los 300 caracteres.';
        } else {
            // Un cliente deja UNA reseña por trabajador; si ya tenía, se actualiza
            $st = $conexion->prepare("INSERT INTO resenas (trabajador_id, usuario_id, estrellas, comentario)
                                      VALUES (?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE estrellas = VALUES(estrellas), comentario = VALUES(comentario), creado_en = CURRENT_TIMESTAMP");
            $st->bind_param("iiis", $id, $_SESSION['usuario_id'], $estrellas, $comentario);
            $st->execute();
            // Redirigir para que al recargar la página no se envíe dos veces
            header('Location: perfil.php?id=' . $id . '#resenas');
            exit;
        }
    }
}

// Reseñas del trabajador, promedio y la reseña del cliente actual (si tiene)
$resenas = null;
$promedio = 0;
$totalResenas = 0;
$resenaMia = null;
if ($t) {
    // El promedio es público: se muestra también en el adelanto para visitantes
    $stP = $conexion->prepare("SELECT ROUND(AVG(estrellas), 1) AS prom, COUNT(*) AS total FROM resenas WHERE trabajador_id = ?");
    $stP->bind_param("i", $id);
    $stP->execute();
    $filaP = $stP->get_result()->fetch_assoc();
    $promedio = (float)($filaP['prom'] ?? 0);
    $totalResenas = (int)$filaP['total'];

    // Los comentarios firmados con nombre de cliente son privados: solo con sesión
    if ($logueado) {
        $stR = $conexion->prepare("SELECT r.estrellas, r.comentario, r.creado_en, u.nombre AS cliente
                                   FROM resenas r JOIN usuarios u ON u.id = r.usuario_id
                                   WHERE r.trabajador_id = ? ORDER BY r.creado_en DESC");
        $stR->bind_param("i", $id);
        $stR->execute();
        $resenas = $stR->get_result();
    }

    if (isset($_SESSION['usuario_id']) && $_SESSION['rol'] === 'cliente') {
        $stM = $conexion->prepare("SELECT estrellas, comentario FROM resenas WHERE trabajador_id = ? AND usuario_id = ?");
        $stM->bind_param("ii", $id, $_SESSION['usuario_id']);
        $stM->execute();
        $resenaMia = $stM->get_result()->fetch_assoc();
    }
}

// ¿El perfil que se mira pertenece al trabajador con sesión? (no tendría sentido que se contacte a sí mismo)
$esMiPerfil = false;
if ($t && isset($_SESSION['usuario_id'])) {
    $stO = $conexion->prepare("SELECT 1 AS ok FROM usuarios WHERE id = ? AND trabajador_id = ?");
    $stO->bind_param("ii", $_SESSION['usuario_id'], $id);
    $stO->execute();
    $esMiPerfil = (bool)$stO->get_result()->fetch_assoc();
}

// Valores para rellenar el formulario: lo que tenía guardado, o lo que intentó enviar si hubo error
$estrellasForm  = $resenaMia ? (int)$resenaMia['estrellas'] : 0;
$comentarioForm = $resenaMia ? $resenaMia['comentario'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estrellasForm  = (int)($_POST['estrellas'] ?? 0);
    $comentarioForm = trim($_POST['comentario'] ?? '');
}

// Dibuja las estrellitas: llenas en dorado y vacías en gris (con texto para lectores de pantalla)
function estrellasHtml($n) {
    $salida = '<span class="cl-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $salida .= $i <= round((float)$n) ? '★' : '☆';
    }
    return $salida . '</span><span class="visually-hidden">' . round((float)$n, 1) . ' de 5 estrellas</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <!-- Clave de "mobile first": el ancho de la página sigue al del celular -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $t ? htmlspecialchars($t['nombre']) . ' · ConectaLocal' : 'ConectaLocal'; ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-icons/css/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
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

  <?php include 'menu.php'; ?>

    <main class="cl-section">
    <div class="container cl-narrow">
      <a class="cl-back" href="oficios.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a la búsqueda</a>
      <?php if (!$t): ?>
      <div class="cl-vacio">
        <p class="fw-bold mb-1">No encontramos este perfil.</p>
        <p class="mb-3">Puede que el enlace esté incompleto o que el perfil ya no exista.</p>
        <a class="btn cl-btn-dark" href="oficios.php">Ir a la búsqueda</a>
      </div>
      <?php elseif (!$logueado): ?>
      <?php
// Iniciales para el avatar, igual que en la vista con sesión
$partes = explode(' ', $t['nombre']);
$ini = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($partes, 0, 2))));
// Al entrar se vuelve a este mismo perfil, no a la cuenta
$vuelta = rawurlencode('perfil.php?id=' . $id);
?>
      <article class="cl-card">
        <div class="d-flex flex-column gap-3 mb-4">
          <div class="cl-avatar cl-avatar-lg" aria-hidden="true"><?php echo htmlspecialchars($ini); ?></div>
          <div>
            <h1 class="cl-title mb-1"><?php echo htmlspecialchars($t['nombre']); ?></h1>
            <p class="text-secondary mb-2"><?php echo htmlspecialchars($t['oficio']); ?> · <?php echo htmlspecialchars($t['sector']); ?></p>
            <?php if ($totalResenas > 0): ?>
            <p class="mb-2"><?php echo estrellasHtml($promedio); ?> <strong><?php echo number_format($promedio, 1, ',', ''); ?></strong> <span class="text-secondary small">(<?php echo $totalResenas; ?> reseña<?php echo $totalResenas != 1 ? 's' : ''; ?>)</span></p>
            <?php else: ?>
            <p class="text-secondary small mb-2">Aún sin reseñas</p>
            <?php endif; ?>
            <span class="cl-estado <?php echo $t['disponible'] ? 'si' : 'no'; ?>"><?php echo $t['disponible'] ? 'Disponible' : 'Ocupado'; ?></span>
          </div>
        </div>

        <h2 class="h6 fw-bold">Sobre su trabajo</h2>
        <p class="mb-4"><?php echo htmlspecialchars($t['descripcion']); ?></p>

        <dl class="cl-datos mb-4">
          <dt>Oficio</dt><dd><?php echo htmlspecialchars($t['oficio']); ?></dd>
          <dt>Sector</dt><dd><?php echo htmlspecialchars($t['sector']); ?></dd>
          <dt>Tarifa</dt><dd><?php echo htmlspecialchars($t['tarifa']); ?></dd>
        </dl>

        <div class="cl-lock">
          <p class="cl-lock-cabecera"><i class="bi bi-lock-fill" aria-hidden="true"></i> Contacto directo por WhatsApp</p>
          <p class="cl-lock-num" aria-hidden="true">+56 9 •••• ••••</p>
          <p class="cl-lock-txt">Por seguridad el número se muestra solo a quienes tienen cuenta en ConectaLocal. Entra y escríbele directo, sin intermediarios.</p>
          <a class="btn btn-lg cl-btn-wa w-100" href="login.php?redir=<?= $vuelta ?>"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Iniciar sesión para contactar</a>
          <p class="cl-lock-alt mb-0">¿No tienes cuenta? <a href="crear_cuenta.php">Crea una gratis</a> y contacta al instante.</p>
        </div>

        <p class="cl-nota mt-3 mb-0">Acuerda el trabajo y el precio por WhatsApp antes de empezar, y evita pagar por adelantado a quien no conoces.</p>
      </article>
      <?php else: ?>
      <article class="cl-card">
        <div class="d-flex flex-column gap-3 mb-4">
          <?php
$partes = explode(' ', $t['nombre']);
$ini = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($partes, 0, 2))));
?>
<div class="cl-avatar cl-avatar-lg" aria-hidden="true"><?php echo htmlspecialchars($ini); ?></div>
          <div>
            <h1 class="cl-title mb-1"><?php echo htmlspecialchars($t['nombre']); ?></h1>
            <p class="text-secondary mb-2"><?php echo htmlspecialchars($t['oficio']); ?> · <?php echo htmlspecialchars($t['sector']); ?></p>
            <?php if ($totalResenas > 0): ?>
            <p class="mb-2"><?php echo estrellasHtml($promedio); ?> <strong><?php echo number_format($promedio, 1, ',', ''); ?></strong> <a href="#resenas" class="text-secondary small">(<?php echo $totalResenas; ?> reseña<?php echo $totalResenas != 1 ? 's' : ''; ?>)</a></p>
            <?php else: ?>
            <p class="text-secondary small mb-2">Aún sin reseñas</p>
            <?php endif; ?>
            <span class="cl-estado <?php echo $t['disponible'] ? 'si' : 'no'; ?>"><?php echo $t['disponible'] ? 'Disponible' : 'Ocupado'; ?></span>
          </div>
        </div>

        <h2 class="h6 fw-bold">Sobre su trabajo</h2>
        <p class="mb-4"><?php echo htmlspecialchars($t['descripcion']); ?></p>

        <dl class="cl-datos mb-4">
          <dt>Oficio</dt><dd><?php echo htmlspecialchars($t['oficio']); ?></dd>
          <dt>Sector</dt><dd><?php echo htmlspecialchars($t['sector']); ?></dd>
          <dt>Tarifa</dt><dd><?php echo htmlspecialchars($t['tarifa']); ?></dd>
        </dl>

        <?php if ($esMiPerfil): ?>
        <div class="alert alert-info mb-0"><strong>Este es tu perfil público.</strong> Así es como lo ven los clientes. <a href="mi_cuenta.php">Ir a mi cuenta</a></div>
        <?php elseif ($t['disponible']): ?>
        <a class="btn btn-lg cl-btn-wa w-100" href="https://wa.me/<?php echo htmlspecialchars($t['whatsapp']); ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Escribir por WhatsApp</a>
        <?php else: ?>
        <span class="btn btn-lg cl-btn-wa w-100 disabled" aria-disabled="true">No disponible hoy</span>
        <?php endif; ?>

        <p class="cl-nota mt-3 mb-0">Acuerda el trabajo y el precio por WhatsApp antes de empezar, y evita pagar por adelantado a quien no conoces.</p>
      </article>

      <!-- Reseñas del trabajador -->
      <section class="cl-card mt-4" id="resenas">
        <h2 class="h5 fw-bold mb-3">Reseñas de clientes</h2>

        <?php if ($totalResenas > 0): ?>
        <p class="mb-4"><?php echo estrellasHtml($promedio); ?> <strong><?php echo number_format($promedio, 1, ',', ''); ?> de 5</strong> · <?php echo $totalResenas; ?> reseña<?php echo $totalResenas != 1 ? 's' : ''; ?></p>
        <?php else: ?>
        <p class="text-secondary mb-4">Este trabajador aún no tiene reseñas. ¡Sé la primera persona en calificar!</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['rol'] === 'cliente'): ?>
        <form method="post" action="perfil.php?id=<?php echo $id; ?>#resenas" class="mb-4">
          <p class="fw-semibold mb-1"><?php echo $resenaMia ? 'Actualiza tu reseña' : 'Deja tu reseña'; ?></p>
          <p class="text-secondary small mb-2">Califica solo si ya recibiste el servicio.</p>
          <?php if ($errorResena): ?>
          <div class="alert alert-danger py-2"><?php echo $errorResena; ?></div>
          <?php endif; ?>
          <div class="cl-rate" role="radiogroup" aria-label="Tu calificación en estrellas">
            <?php for ($e = 5; $e >= 1; $e--): ?>
            <input type="radio" id="est<?php echo $e; ?>" name="estrellas" value="<?php echo $e; ?>" <?php echo $estrellasForm === $e ? 'checked' : ''; ?> required>
            <label for="est<?php echo $e; ?>" title="<?php echo $e; ?> de 5">★</label>
            <?php endfor; ?>
          </div>
          <label for="comentario" class="visually-hidden">Tu comentario sobre el servicio</label>
          <textarea class="form-control mb-2" id="comentario" name="comentario" rows="3" maxlength="300" placeholder="Cuéntanos tu experiencia (opcional)"><?php echo htmlspecialchars($comentarioForm); ?></textarea>
          <button type="submit" class="btn cl-btn-dark"><?php echo $resenaMia ? 'Actualizar reseña' : 'Publicar reseña'; ?></button>
        </form>
        <?php elseif (!isset($_SESSION['usuario_id'])): ?>
        <p class="small mb-4"><a href="login.php">Inicia sesión</a> como cliente para dejar tu reseña.</p>
        <?php endif; ?>

        <?php if ($totalResenas > 0): ?>
        <ul class="cl-resenas">
          <?php while ($r = $resenas->fetch_assoc()): ?>
          <li class="cl-resena">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
              <strong><?php echo htmlspecialchars($r['cliente']); ?></strong>
              <?php echo estrellasHtml($r['estrellas']); ?>
            </div>
            <?php if ($r['comentario'] !== ''): ?>
            <p><?php echo htmlspecialchars($r['comentario']); ?></p>
            <?php endif; ?>
            <small class="text-secondary"><?php echo date('d-m-Y', strtotime($r['creado_en'])); ?></small>
          </li>
          <?php endwhile; ?>
        </ul>
        <?php endif; ?>
      </section>
      <?php endif; ?>
    </div>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

