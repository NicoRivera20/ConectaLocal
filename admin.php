<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Eliminar trabajador de ConectaLocal: su perfil, su cuenta de usuario y sus reseñas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_trabajador'])) {
    $idEliminar = (int)$_POST['eliminar_trabajador'];

    // Buscar la cuenta de usuario asociada al perfil
    $stU = $conexion->prepare("SELECT id FROM usuarios WHERE trabajador_id = ?");
    $stU->bind_param("i", $idEliminar);
    $stU->execute();
    $usuarioEliminar = $stU->get_result()->fetch_assoc()['id'] ?? null;

    // 1) Desvincular la cuenta del perfil (para no romper la llave foránea)
    $st1 = $conexion->prepare("UPDATE usuarios SET trabajador_id = NULL WHERE trabajador_id = ?");
    $st1->bind_param("i", $idEliminar);
    $st1->execute();

    // 2) Borrar la cuenta de usuario (sus reseñas se borran en cascada)
    if ($usuarioEliminar) {
        $st2 = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $st2->bind_param("i", $usuarioEliminar);
        $st2->execute();
    }

    // 3) Borrar el perfil de trabajador (sus reseñas recibidas se borran en cascada)
    $st3 = $conexion->prepare("DELETE FROM trabajadores WHERE id = ?");
    $st3->bind_param("i", $idEliminar);
    $st3->execute();

    header('Location: admin.php?eliminado=1');
    exit;
}

$usuarios = $conexion->query("SELECT id, nombre, correo, rol, creado_en FROM usuarios ORDER BY id DESC");
$trabajadores = $conexion->query("SELECT t.id, t.nombre, t.whatsapp, o.nombre AS oficio, s.nombre AS sector, t.tarifa, t.disponible, t.destacado, t.creado_en,
        (SELECT ROUND(AVG(r.estrellas), 1) FROM resenas r WHERE r.trabajador_id = t.id) AS promedio,
        (SELECT COUNT(*) FROM resenas r WHERE r.trabajador_id = t.id) AS total_resenas
        FROM trabajadores t JOIN oficios o ON o.id = t.oficio_id JOIN sectores s ON s.id = t.sector_id ORDER BY t.id DESC");

$totalUsuarios = $usuarios->num_rows;
$totalTrabajadores = $trabajadores->num_rows;
$disponibles = $conexion->query("SELECT COUNT(*) AS n FROM trabajadores WHERE disponible = 1")->fetch_assoc()['n'];
$clientes = $conexion->query("SELECT COUNT(*) AS n FROM usuarios WHERE rol = 'cliente'")->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administración · ConectaLocal</title>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-icons/css/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6fb; font-family: "Source Sans 3", system-ui, sans-serif; }
    .admin-topbar { background: #0b0d12; color: #fff; padding: 1rem 0; }
    .admin-topbar .brand { font-family: "Bricolage Grotesque", sans-serif; font-weight: 800; font-size: 1.4rem; color: #fff; text-decoration: none; letter-spacing: -0.03em; }
    .admin-topbar .brand span { color: #8ea6ff; }
    .stat-card { background: #fff; border: 1px solid #e1e5ec; border-radius: 16px; padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 8px rgba(11,13,18,.05); }
    .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 1.4rem; }
    .stat-card h3 { font-size: 1.75rem; font-weight: 800; margin: 0; font-family: "Bricolage Grotesque", sans-serif; }
    .stat-card p { margin: 0; color: #525c6b; font-size: .9rem; }
    .panel { background: #fff; border: 1px solid #e1e5ec; border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(11,13,18,.05); }
    .panel h2 { font-family: "Bricolage Grotesque", sans-serif; font-weight: 800; font-size: 1.25rem; margin-bottom: 1rem; }
    .table thead th { background: #f4f6fb; color: #525c6b; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e1e5ec; }
    .badge-rol { padding: .35rem .8rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
    .badge-admin { background: #e6ebff; color: #2450ff; }
    .badge-trabajador { background: #e1f4e8; color: #0f5c30; }
    .badge-cliente { background: #fde8e8; color: #b02a37; }
  </style>
</head>
<body>

  <div class="admin-topbar">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
      <a class="brand" href="index.php">Conecta<span>Local</span> · Admin</a>
      <div class="d-flex align-items-center gap-3">
        <span class="small text-secondary d-none d-sm-inline">Sesión de <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Salir</a>
      </div>
    </div>
  </div>

  <main class="container py-4">
    <h1 class="mb-4" style="font-family: 'Bricolage Grotesque', sans-serif; font-weight: 800;">Panel de administración</h1>

    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e6ebff;color:#2450ff;"><i class="bi bi-people"></i></div>
          <div><h3><?php echo $totalUsuarios; ?></h3><p>Usuarios</p></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e1f4e8;color:#0f5c30;"><i class="bi bi-tools"></i></div>
          <div><h3><?php echo $totalTrabajadores; ?></h3><p>Trabajadores</p></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff3cd;color:#9c6f00;"><i class="bi bi-check-circle"></i></div>
          <div><h3><?php echo $disponibles; ?></h3><p>Disponibles hoy</p></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fde8e8;color:#b02a37;"><i class="bi bi-person-heart"></i></div>
          <div><h3><?php echo $clientes; ?></h3><p>Clientes</p></div>
        </div>
      </div>
    </div>

    <div class="panel mb-4">
      <h2><i class="bi bi-people me-2"></i>Usuarios registrados</h2>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Registrado</th></tr>
          </thead>
          <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
            <tr>
              <td class="text-muted">#<?php echo $u['id']; ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars($u['nombre']); ?></td>
              <td><?php echo htmlspecialchars($u['correo']); ?></td>
              <td><span class="badge-rol badge-<?php echo $u['rol']; ?>"><?php echo htmlspecialchars($u['rol']); ?></span></td>
              <td class="text-muted small"><?php echo date('d-m-Y', strtotime($u['creado_en'] ?? 'now')); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <h2><i class="bi bi-tools me-2"></i>Perfiles de trabajadores</h2>
      <?php if (isset($_GET['eliminado'])): ?>
      <div class="alert alert-success py-2"><strong>Listo.</strong> El trabajador fue eliminado de ConectaLocal junto con su cuenta y sus reseñas.</div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr><th>ID</th><th>Nombre</th><th>Oficio</th><th>Sector</th><th>Tarifa</th><th>Disponible</th><th>Destacado</th><th>Calificación</th><th>Acción</th></tr>
          </thead>
          <tbody>
            <?php while ($t = $trabajadores->fetch_assoc()): ?>
            <tr>
              <td class="text-muted">#<?php echo $t['id']; ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars($t['nombre']); ?></td>
              <td><?php echo htmlspecialchars($t['oficio']); ?></td>
              <td><?php echo htmlspecialchars($t['sector']); ?></td>
              <td class="small"><?php echo htmlspecialchars($t['tarifa']); ?></td>
              <td><span class="badge bg-<?php echo $t['disponible'] ? 'success' : 'danger'; ?>"><?php echo $t['disponible'] ? 'Sí' : 'No'; ?></span></td>
              <td><?php echo $t['destacado'] ? '⭐' : '—'; ?></td>
              <td>
                <?php if ($t['total_resenas'] > 0): ?>
                <span class="fw-bold <?php echo $t['promedio'] < 3 ? 'text-danger' : ''; ?>">⭐ <?php echo number_format((float)$t['promedio'], 1, ',', ''); ?></span>
                <span class="text-muted small">(<?php echo $t['total_resenas']; ?>)</span>
                <?php else: ?>
                <span class="text-muted small">Sin reseñas</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" action="admin.php" class="m-0" onsubmit="return confirm('¿Eliminar a este trabajador de ConectaLocal? Se borrará su perfil, su cuenta y sus reseñas. Esta acción no se puede deshacer.');">
                  <button type="submit" name="eliminar_trabajador" value="<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <footer class="text-center text-muted small py-4">ConectaLocal · Panel de administración · 2026</footer>
</body>
</html>

