<?php
session_start();
include 'conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['password'] ?? '';
    $rol    = $_POST['rol'] ?? '';

    if (mb_strlen($nombre) < 3) $error = 'Escribe tu nombre completo.';
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $error = 'Correo inválido.';
    elseif (strlen($clave) < 8) $error = 'La contraseña debe tener al menos 8 caracteres.';
    elseif (!in_array($rol, ['trabajador', 'cliente'])) $error = 'Elige un tipo de cuenta.';
    else {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $correo, $hash, $rol);
        if ($stmt->execute()) {
            $_SESSION['usuario_id'] = $conexion->insert_id;
            $_SESSION['nombre']     = $nombre;
            $_SESSION['correo']     = $correo;
            $_SESSION['rol']        = $rol;
            header('Location: mi_cuenta.php');
            exit;
        }
        $error = 'Ese correo ya está registrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta · ConectaLocal</title>
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
      <h1 class="cl-title">Crear cuenta</h1>
      <p class="mb-4">Es gratis. Elige cómo quieres usar ConectaLocal.</p>

      <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="crear_cuenta.php" class="cl-card">
        <div class="mb-3">
          <label for="nombre" class="form-label fw-semibold">Nombre completo</label>
          <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
          <label for="correo" class="form-label fw-semibold">Correo</label>
          <input type="email" class="form-control" id="correo" name="correo" required autocomplete="email">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label fw-semibold">Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password">
            <button class="btn btn-outline-secondary" type="button" data-ver-contrasena="password" aria-pressed="false" aria-label="Mostrar contraseña">
              <i class="bi bi-eye" data-icono="mostrar"></i>
              <i class="bi bi-eye-slash d-none" data-icono="ocultar"></i>
            </button>
          </div>
          <div class="form-text">Mínimo 8 caracteres. Pulsa el ojo para ver lo que escribes.</div>
        </div>
        <div class="mb-4">
          <label for="rol" class="form-label fw-semibold">¿Qué quieres hacer?</label>
          <select class="form-select" id="rol" name="rol" required>
            <option value="">Elige una opción</option>
            <option value="cliente">Buscar servicios</option>
            <option value="trabajador">Ofrecer mis servicios</option>
          </select>
        </div>
        <button type="submit" class="btn btn-lg cl-btn-dark w-100">Crear mi cuenta</button>
        <p class="mt-3 mb-0 text-center">¿Ya tienes cuenta? <a href="login.php">Entra aquí</a>.</p>
      </form>
    </div>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>
<script src="js/ver-contrasena.js"></script>
</body>
</html>

