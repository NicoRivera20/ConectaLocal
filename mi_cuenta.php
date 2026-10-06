<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$trabajador = null;
$promedio = 0;
$totalResenas = 0;
$ini = '';
if ($_SESSION['rol'] === 'trabajador') {
    $stmt = $conexion->prepare("SELECT t.*, o.nombre AS oficio, s.nombre AS sector
                                FROM trabajadores t
                                JOIN oficios o ON o.id = t.oficio_id
                                JOIN sectores s ON s.id = t.sector_id
                                WHERE t.id = (SELECT trabajador_id FROM usuarios WHERE id = ?)");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $trabajador = $stmt->get_result()->fetch_assoc();

    if ($trabajador) {
        // Estadísticas de reseñas para el panel
        $stP = $conexion->prepare("SELECT ROUND(AVG(estrellas), 1) AS prom, COUNT(*) AS total FROM resenas WHERE trabajador_id = ?");
        $stP->bind_param("i", $trabajador['id']);
        $stP->execute();
        $filaP = $stP->get_result()->fetch_assoc();
        $promedio = (float)($filaP['prom'] ?? 0);
        $totalResenas = (int)$filaP['total'];

        // Iniciales del nombre para el avatar
        $partes = explode(' ', $trabajador['nombre']);
        $ini = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($partes, 0, 2))));
    }
}

// Datos del panel del cliente: sus reseñas y estadísticas
$misResenas = null;
$totalMisResenas = 0;
$promedioDado = 0;
$disponiblesHoy = 0;
$iniCliente = '';
if ($_SESSION['rol'] === 'cliente') {
    // Reseñas que el cliente ha dejado, con el nombre del trabajador y su oficio
    $stR = $conexion->prepare("SELECT r.estrellas, r.comentario, r.creado_en, t.id AS trabajador_id, t.nombre AS trabajador, o.nombre AS oficio
                               FROM resenas r
                               JOIN trabajadores t ON t.id = r.trabajador_id
                               JOIN oficios o ON o.id = t.oficio_id
                               WHERE r.usuario_id = ?
                               ORDER BY r.creado_en DESC");
    $stR->bind_param("i", $_SESSION['usuario_id']);
    $stR->execute();
    $misResenas = $stR->get_result();
    $totalMisResenas = $misResenas->num_rows;

    // Promedio de estrellas que suele dar
    $stD = $conexion->prepare("SELECT ROUND(AVG(estrellas), 1) AS prom FROM resenas WHERE usuario_id = ?");
    $stD->bind_param("i", $_SESSION['usuario_id']);
    $stD->execute();
    $promedioDado = (float)($stD->get_result()->fetch_assoc()['prom'] ?? 0);

    // Trabajadores disponibles hoy en toda la plataforma
    $disponiblesHoy = (int)$conexion->query("SELECT COUNT(*) AS n FROM trabajadores WHERE disponible = 1")->fetch_assoc()['n'];

    // Iniciales para el avatar
    $partesC = explode(' ', $_SESSION['nombre']);
    $iniCliente = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($partesC, 0, 2))));
}

// Dibuja las estrellitas (misma función que en perfil.php)
function estrellasHtml($n) {
    $salida = '<span class="cl-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $salida .= $i <= round((float)$n) ? '★' : '☆';
    }
    return $salida . '</span><span class="visually-hidden">' . round((float)$n, 1) . ' de 5 estrellas</span>';
}

// Cambiar disponibilidad (solo trabajador con perfil publicado)
if ($trabajador && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_disponible'])) {
    $nuevo = $trabajador['disponible'] ? 0 : 1;
    $st = $conexion->prepare("UPDATE trabajadores SET disponible = ? WHERE id = ?");
    $st->bind_param("ii", $nuevo, $trabajador['id']);
    $st->execute();
    // Redirigir para que al recargar no se repita el cambio
    header('Location: mi_cuenta.php?ok=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi cuenta · ConectaLocal</title>
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-icons/css/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
</head>
<body>
  <header class="cl-topbar">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
      <a class="cl-brand" href="index.php">Conecta<span>Local</span></a>
      <a href="logout.php" class="btn btn-sm cl-btn-gold">Cerrar sesión</a>
    </div>
  </header>

  <main class="cl-section">
    <div class="container cl-narrow">
      <h1 class="cl-title">Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h1>
      <p class="text-secondary mb-4">Cuenta: <?php echo htmlspecialchars($_SESSION['correo']); ?> · Rol: <?php echo htmlspecialchars($_SESSION['rol']); ?></p>

      <?php if ($_SESSION['rol'] === 'admin'): ?>
        <a href="admin.php" class="btn cl-btn-dark me-2">Ir al panel de administración</a>
        <a href="index.php" class="btn btn-outline-dark">Ver el sitio</a>
      <?php elseif ($_SESSION['rol'] === 'cliente'): ?>
        <!-- Resumen de la cuenta -->
        <div class="cl-panel mb-3">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="cl-avatar" aria-hidden="true"><?php echo htmlspecialchars($iniCliente); ?></div>
            <div class="flex-grow-1">
              <h2 class="h5 fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
              <p class="text-secondary small mb-0"><?php echo htmlspecialchars($_SESSION['correo']); ?></p>
            </div>
            <a href="oficios.php" class="btn cl-btn-dark">Buscar servicios</a>
          </div>
        </div>

        <!-- Estadísticas del cliente -->
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-4">
            <div class="cl-stat">
              <div class="cl-stat-icon" style="background:#e6ebff;color:#2450ff;"><i class="bi bi-chat-left-text"></i></div>
              <div><h3><?php echo $totalMisResenas; ?></h3><p>Reseña<?php echo $totalMisResenas != 1 ? 's' : ''; ?> escrita<?php echo $totalMisResenas != 1 ? 's' : ''; ?></p></div>
            </div>
          </div>
          <div class="col-6 col-md-4">
            <div class="cl-stat">
              <div class="cl-stat-icon" style="background:#fff3cd;color:#9c6f00;"><i class="bi bi-star-fill"></i></div>
              <div><h3><?php echo $totalMisResenas > 0 ? number_format($promedioDado, 1, ',', '') : '—'; ?></h3><p>Promedio que das</p></div>
            </div>
          </div>
          <div class="col-6 col-md-4">
            <div class="cl-stat">
              <div class="cl-stat-icon" style="background:#e1f4e8;color:#0f5c30;"><i class="bi bi-people"></i></div>
              <div><h3><?php echo $disponiblesHoy; ?></h3><p>Disponibles hoy</p></div>
            </div>
          </div>
        </div>

        <!-- Reseñas que el cliente ha dejado -->
        <div class="cl-panel">
          <h2 class="h6 fw-bold mb-3">Mis reseñas</h2>
          <?php if ($totalMisResenas > 0): ?>
          <ul class="cl-resenas">
            <?php while ($r = $misResenas->fetch_assoc()): ?>
            <li class="cl-resena">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <div>
                  <strong><?php echo htmlspecialchars($r['trabajador']); ?></strong>
                  <span class="text-secondary small">· <?php echo htmlspecialchars($r['oficio']); ?></span>
                </div>
                <?php echo estrellasHtml($r['estrellas']); ?>
              </div>
              <?php if ($r['comentario'] !== ''): ?>
              <p><?php echo htmlspecialchars($r['comentario']); ?></p>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-secondary"><?php echo date('d-m-Y', strtotime($r['creado_en'])); ?></small>
                <a href="perfil.php?id=<?php echo $r['trabajador_id']; ?>#resenas" class="small fw-semibold">Ver / editar reseña</a>
              </div>
            </li>
            <?php endwhile; ?>
          </ul>
          <?php else: ?>
          <p class="text-secondary mb-2">Aún no has dejado reseñas.</p>
          <p class="small text-secondary mb-0">Después de contratar un servicio, entra al perfil del trabajador y califícalo con estrellas: ayudas a los demás vecinos a elegir mejor.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php if ($trabajador): ?>
          <?php if (isset($_GET['ok'])): ?>
          <div class="alert alert-success py-2"><strong>Listo.</strong> Tu disponibilidad se actualizó.</div>
          <?php endif; ?>

          <!-- Resumen del perfil -->
          <div class="cl-panel mb-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <div class="cl-avatar" aria-hidden="true"><?php echo htmlspecialchars($ini); ?></div>
              <div class="flex-grow-1">
                <h2 class="h5 fw-bold mb-0"><?php echo htmlspecialchars($trabajador['nombre']); ?></h2>
                <p class="text-secondary small mb-1"><?php echo htmlspecialchars($trabajador['oficio']); ?> · <?php echo htmlspecialchars($trabajador['sector']); ?></p>
                <?php if ($trabajador['destacado']): ?>
                <span class="badge bg-warning text-dark">⭐ Destacado</span>
                <?php endif; ?>
              </div>
              <a href="perfil.php?id=<?php echo $trabajador['id']; ?>" class="btn cl-btn-dark">Ver mi perfil público</a>
            </div>
          </div>

          <!-- Estadísticas del trabajador -->
          <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
              <div class="cl-stat">
                <div class="cl-stat-icon" style="background:<?php echo $trabajador['disponible'] ? '#e1f4e8' : '#fde8e8'; ?>;color:<?php echo $trabajador['disponible'] ? '#0f5c30' : '#b02a37'; ?>;"><i class="bi <?php echo $trabajador['disponible'] ? 'bi-check-circle' : 'bi-pause-circle'; ?>"></i></div>
                <div><h3><?php echo $trabajador['disponible'] ? 'Disponible' : 'Ocupado'; ?></h3><p>Tu estado</p></div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="cl-stat">
                <div class="cl-stat-icon" style="background:#fff3cd;color:#9c6f00;"><i class="bi bi-star-fill"></i></div>
                <div><h3><?php echo $totalResenas > 0 ? number_format($promedio, 1, ',', '') : '—'; ?></h3><p>Calificación</p></div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="cl-stat">
                <div class="cl-stat-icon" style="background:#e6ebff;color:#2450ff;"><i class="bi bi-chat-left-text"></i></div>
                <div><h3><?php echo $totalResenas; ?></h3><p>Reseña<?php echo $totalResenas != 1 ? 's' : ''; ?></p></div>
              </div>
            </div>
          </div>

          <!-- Control de disponibilidad -->
          <div class="cl-panel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h2 class="h6 fw-bold mb-1">Tu disponibilidad</h2>
                <p class="small text-secondary mb-0">
                  <?php echo $trabajador['disponible'] ? 'Los clientes pueden escribirte por WhatsApp.' : 'Tu tarjeta muestra "Ocupado" y los clientes no pueden contactarte.'; ?>
                </p>
              </div>
              <span class="cl-estado <?php echo $trabajador['disponible'] ? 'si' : 'no'; ?>"><?php echo $trabajador['disponible'] ? 'Disponible' : 'Ocupado'; ?></span>
            </div>
            <form method="post" action="mi_cuenta.php" class="mt-3 mb-0">
              <button type="submit" name="toggle_disponible" class="btn fw-bold <?php echo $trabajador['disponible'] ? 'btn-outline-danger' : 'cl-btn-wa'; ?>">
                <?php echo $trabajador['disponible'] ? 'Cambiar a no disponible' : 'Cambiar a disponible'; ?>
              </button>
            </form>
          </div>
        <?php else: ?>
          <div class="cl-panel text-center py-4">
            <div class="cl-stat-icon mx-auto mb-3" style="background:#e6ebff;color:#2450ff;"><i class="bi bi-megaphone"></i></div>
            <h2 class="h5 fw-bold">Aún no has publicado tu perfil de trabajador</h2>
            <p class="text-secondary">Publica tu servicio gratis y aparece en la búsqueda de tu barrio.</p>
            <a href="registro.php" class="btn cl-btn-dark">Publicar mi perfil</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>
</body>
</html>

