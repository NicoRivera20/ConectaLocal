<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['rol'] !== 'trabajador') {
    header('Location: mi_cuenta.php');
    exit;
}

// ¿Ya tiene un perfil de trabajador publicado?
$stmt = $conexion->prepare("SELECT trabajador_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$filaUsuario = $stmt->get_result()->fetch_assoc();
$yaTienePerfil = !empty($filaUsuario['trabajador_id']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $whatsapp    = preg_replace('/\s/', '', $_POST['whatsapp'] ?? '');
    $oficio      = $_POST['oficio'] ?? '';
    $sector      = $_POST['sector'] ?? '';
    $tarifa      = trim($_POST['tarifa'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $disponible  = isset($_POST['disponible']) ? 1 : 0;
    $consent     = isset($_POST['consentimiento']);

    // Validar en el servidor (nunca confiar solo en el navegador)
    if (mb_strlen($nombre) < 3) $error = 'Nombre inválido.';
    elseif (!preg_match('/^9\d{8}$/', $whatsapp)) $error = 'WhatsApp inválido.';
    elseif ($oficio === '' || $sector === '') $error = 'Elige oficio y sector.';
    elseif (mb_strlen($tarifa) < 3 || mb_strlen($descripcion) < 20) $error = 'Tarifa o descripción muy cortas.';
    elseif (!$consent) $error = 'Debes aceptar el consentimiento.';
    else {
        // Buscar los IDs de oficio y sector
        $st = $conexion->prepare("SELECT id FROM oficios WHERE nombre = ?");
        $st->bind_param("s", $oficio); $st->execute();
        $oficio_id = $st->get_result()->fetch_assoc()['id'] ?? null;

        $st = $conexion->prepare("SELECT id FROM sectores WHERE nombre = ?");
        $st->bind_param("s", $sector); $st->execute();
        $sector_id = $st->get_result()->fetch_assoc()['id'] ?? null;

        if (!$oficio_id || !$sector_id) {
            $error = 'Oficio o sector no reconocido.';
        } else {
            // Subir la foto (opcional)
            $foto = null;
            if (!empty($_FILES['foto']['name'])) {
                $permitidas = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
                $tipo = mime_content_type($_FILES['foto']['tmp_name']);
                if (!isset($permitidas[$tipo])) $error = 'La foto debe ser JPG o PNG.';
                elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) $error = 'La foto pesa más de 2 MB.';
                else {
                    $foto = uniqid('perfil_') . '.' . $permitidas[$tipo];
                    move_uploaded_file($_FILES['foto']['tmp_name'], 'img/' . $foto);
                }
            }

            if ($error === '') {
                $wp = '56' . $whatsapp;
                $st = $conexion->prepare("INSERT INTO trabajadores (nombre, whatsapp, oficio_id, sector_id, tarifa, descripcion, foto, disponible) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $st->bind_param("ssiisssi", $nombre, $wp, $oficio_id, $sector_id, $tarifa, $descripcion, $foto, $disponible);
                if ($st->execute()) {
                    // Vincular el perfil al usuario que inició sesión
                    $nuevoId = $conexion->insert_id;
                    $up = $conexion->prepare("UPDATE usuarios SET trabajador_id = ? WHERE id = ?");
                    $up->bind_param("ii", $nuevoId, $_SESSION['usuario_id']);
                    $up->execute();
                    header('Location: registro.php?ok=1');
                    exit;
                }
                $error = 'No se pudo guardar: ' . $conexion->error;
            }
        }
    }
}
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
      <h1 class="cl-title">Ofrece tus servicios</h1>
      <p class="mb-4">Es gratis. Tu perfil aparece en la búsqueda y las personas te escriben directo por WhatsApp.</p>

      <div class="alert alert-success<?php echo isset($_GET['ok']) ? '' : ' d-none'; ?>" id="exito" role="status" tabindex="-1">
        <strong>Perfil listo.</strong> Tu servicio ya está publicado en ConectaLocal.
      </div>

      <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- novalidate: desactiva los mensajes del navegador para usar los nuestros -->
      <?php if ($yaTienePerfil): ?>
        <div class="alert alert-info"><strong>Ya tienes un perfil publicado.</strong> Puedes verlo desde <a href="mi_cuenta.php">mi cuenta</a>.</div>
      <?php else: ?>
      <form id="form-registro" class="cl-card" method="post" action="registro.php" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
          <label for="nombre" class="form-label fw-semibold">Nombre completo</label>
          <input type="text" class="form-control" id="nombre" name="nombre" autocomplete="name">
          <div class="invalid-feedback"></div>
        </div>

        <div class="mb-3">
          <label for="whatsapp" class="form-label fw-semibold">WhatsApp</label>
          <div class="input-group">
            <span class="input-group-text">+56</span>
            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" inputmode="numeric" placeholder="9 1234 5678" autocomplete="tel-national">
            <div class="invalid-feedback"></div>
          </div>
        </div>

        <div class="mb-3">
          <label for="oficio" class="form-label fw-semibold">Oficio</label>
          <select class="form-select" id="oficio" name="oficio">
            <option value="">Elige un oficio</option>
            <option>Gasfitería</option>
            <option>Electricidad</option>
            <option>Pintura</option>
            <option>Carpintería</option>
            <option>Cerrajería</option>
            <option>Jardinería</option>
            <option>Mueblista</option>
            <option>Mecánico</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="mb-3">
          <label for="sector" class="form-label fw-semibold">Sector donde trabajas</label>
          <select class="form-select" id="sector" name="sector">
            <option value="">Elige un sector</option>
            <option>Temuco</option>
            <option>Labranza</option>
            <option>Pedro de Valdivia</option>
            <option>Padre las Casas</option>
            <option>Fundo El Carmen</option>
            <option>Pueblo Nuevo</option>
            <option>Centro</option>
            <option>Amanecer</option>
            <option>Santa Rosa</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="mb-3">
          <label for="tarifa" class="form-label fw-semibold">Tarifa referencial</label>
          <input type="text" class="form-control" id="tarifa" name="tarifa" placeholder="Ej: Desde $15.000 la visita">
          <div class="invalid-feedback"></div>
        </div>

        <div class="mb-3">
          <label for="descripcion" class="form-label fw-semibold">¿Qué trabajos haces?</label>
          <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="200"></textarea>
          <div class="invalid-feedback"></div>
          <div class="form-text text-end" id="contador-desc">0 / 200</div>
        </div>

        <div class="mb-3">
          <label for="foto" class="form-label fw-semibold">Foto (opcional)</label>
          <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png">
          <div class="invalid-feedback"></div>
          <div class="form-text">JPG o PNG, máximo 2 MB.</div>
          <img id="vista-previa" class="cl-foto-preview d-none mt-2" alt="Vista previa de tu foto">
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="disponible" name="disponible" checked>
          <label class="form-check-label" for="disponible">Estoy disponible ahora</label>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="consentimiento" name="consentimiento">
          <label class="form-check-label" for="consentimiento">Acepto que mi nombre, WhatsApp y foto sean visibles públicamente en ConectaLocal.</label>
          <div class="invalid-feedback"></div>
        </div>

        <button type="submit" class="btn btn-lg cl-btn-dark w-100">Publicar mi perfil</button>
      </form>
      <?php endif; ?>
    </div>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/registro.js"></script>
</body>
</html>
