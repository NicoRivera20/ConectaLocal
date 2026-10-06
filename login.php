<?php
session_start();
include 'conexion.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: mi_cuenta.php');
    exit;
}

$error = '';

// A dónde volver después de entrar (ej: el perfil desde el que se hizo clic).
// Viene por POST cuando el usuario ya está viendo el formulario, por eso se
// lee de los dos lados.
// Solo se aceptan páginas internas del sitio: así nadie puede usar este
// parámetro para mandar al usuario a un sitio externo (open redirect).
$redir = $_POST['redir'] ?? $_GET['redir'] ?? '';
if (!preg_match('#^[a-zA-Z0-9_-]+\.php(\?[a-zA-Z0-9_=&%.+-]*)?$#', $redir)) {
    $redir = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['password'] ?? '';

    $stmt = $conexion->prepare("SELECT id, nombre, correo, password_hash, rol FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($clave, $u['password_hash'])) {
        $_SESSION['usuario_id'] = $u['id'];
        $_SESSION['nombre']     = $u['nombre'];
        $_SESSION['correo']     = $u['correo'];
        $_SESSION['rol']        = $u['rol'];

        if ($u['rol'] === 'admin') header('Location: admin.php');
        elseif ($redir !== '') header('Location: ' . $redir);
        else header('Location: mi_cuenta.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar · ConectaLocal</title>
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-icons/css/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
</head>
<body>
  <header class="cl-topbar">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
      <a class="cl-brand" href="index.php">Conecta<span>Local</span></a>
    </div>
  </header>

  <main class="cl-section">
    <div class="container cl-narrow">
      <h1 class="cl-title">Entrar</h1>
      <p class="mb-4">Accede a tu cuenta para ofrecer servicios o buscar a alguien de confianza.</p>

      <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="login.php" class="cl-card">
        <!-- Conserva la página de origen para volver a ella tras entrar -->
        <?php if ($redir !== ''): ?>
        <input type="hidden" name="redir" value="<?php echo htmlspecialchars($redir); ?>">
        <?php endif; ?>
        <div class="mb-3">
          <label for="correo" class="form-label fw-semibold">Correo</label>
          <input type="email" class="form-control" id="correo" name="correo" required autocomplete="email">
        </div>
        <div class="mb-4">
          <label for="password" class="form-label fw-semibold">Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" aria-describedby="ayuda-clave">
            <button class="btn btn-outline-secondary" type="button" data-ver-contrasena="password" aria-pressed="false" aria-label="Mostrar contraseña">
              <i class="bi bi-eye" data-icono="mostrar"></i>
              <i class="bi bi-eye-slash d-none" data-icono="ocultar"></i>
            </button>
          </div>
          <div class="form-text" id="ayuda-clave">Pulsa el ojo para ver lo que escribes.</div>
        </div>
        <button type="submit" class="btn btn-lg cl-btn-dark w-100">Entrar</button>
        <p class="mt-3 mb-0 text-center">¿No tienes cuenta? <a href="crear_cuenta.php">Crea una aquí</a>.</p>
      </form>
    </div>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>
<script src="js/ver-contrasena.js"></script>
</body>
</html>

