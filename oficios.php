<?php
session_start();
// 1. Incluir el archivo de conexión a la base de datos
include 'conexion.php';

// 2. Consulta SQL para traer los trabajadores con el nombre de su oficio y sector,
//    más el promedio de estrellas y cuántas reseñas tiene cada uno
$sql = "SELECT t.id, t.nombre, o.nombre AS oficio, s.nombre AS sector, t.tarifa, t.descripcion, t.whatsapp, t.disponible, t.destacado,
        (SELECT ROUND(AVG(r.estrellas), 1) FROM resenas r WHERE r.trabajador_id = t.id) AS promedio,
        (SELECT COUNT(*) FROM resenas r WHERE r.trabajador_id = t.id) AS total_resenas
        FROM trabajadores t 
        JOIN oficios o ON o.id = t.oficio_id 
        JOIN sectores s ON s.id = t.sector_id 
        ORDER BY t.destacado DESC, t.nombre";

// 3. Ejecutar la consulta
$resultado = $conexion->query($sql);

// Perfil del trabajador con sesión iniciada (para no ofrecerle contactarse a sí mismo)
$miTrabajadorId = null;
if (isset($_SESSION['usuario_id'])) {
    $stMi = $conexion->prepare("SELECT trabajador_id FROM usuarios WHERE id = ?");
    $stMi->bind_param("i", $_SESSION['usuario_id']);
    $stMi->execute();
    $miTrabajadorId = $stMi->get_result()->fetch_assoc()['trabajador_id'] ?? null;
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

  <main>
    <section class="cl-hero" id="buscar">
      <div class="container">
        <h1 class="cl-hero-title">Encuentra quién te lo arregle, en tu mismo barrio</h1>
        <p class="cl-hero-text">Gasfíteres, electricistas y oficios locales. Escríbeles directo por WhatsApp.</p>

        <form class="cl-search" id="form-busqueda" role="search">
          <div class="btn-group w-100 mb-3" role="group" aria-label="Tipo de búsqueda">
            <button type="button" class="btn btn-lg btn-primary active">Quiero contratar</button>
            <a href="registro.php" class="btn btn-lg btn-outline-primary">Quiero trabajar</a>
          </div>

          <label for="texto" class="visually-hidden">Qué necesitas</label>
          <input type="search" class="form-control form-control-lg" id="texto" placeholder="¿Qué necesitas? Ej: filtración">

          <label for="sector" class="visually-hidden">Sector</label>
          <select class="form-select form-select-lg" id="sector">
            <option value="">Todo Temuco</option>
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

          <button type="submit" class="btn btn-lg cl-btn-dark">Buscar</button>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="solo-disponibles">
            <label class="form-check-label" for="solo-disponibles">Mostrar solo quienes están disponibles</label>
          </div>
        </form>
      </div>
    </section>
            <section class="cl-section" id="oficios">
      <div class="container">
        <h2 class="cl-title">Explora oficios</h2>
        <div class="cl-chips" id="chips" role="group" aria-label="Filtrar por oficio">
          <button type="button" class="cl-chip activo" data-oficio="Todos" aria-pressed="true">Todos</button>
          <?php
          // Todos los oficios de la BD, tengan o no trabajadores registrados.
          // Si un oficio no tiene tarjetas, el filtro muestra el mensaje de "sin resultados".
          $listaOficios = $conexion->query("SELECT nombre FROM oficios ORDER BY nombre");
          while ($o = $listaOficios->fetch_assoc()) {
              echo '<button type="button" class="cl-chip" data-oficio="' . htmlspecialchars($o['nombre']) . '" aria-pressed="false">' . htmlspecialchars($o['nombre']) . '</button>';
          }
          ?>
        </div>
        <p class="cl-count" id="contador" aria-live="polite"></p>
        <div class="row g-4 mt-4" id="tarjetas">
    <?php
    // Comprobar si hay resultados en la base de datos
    if ($resultado->num_rows > 0) {
        // Recorrer cada trabajador encontrado
        $i = 0; // Posición de la tarjeta, para escalonar la animación de entrada
        while($row = $resultado->fetch_assoc()) {
            $i++;
            
            // Lógica para etiquetas (Badges)
            $badgeDestacado = $row['destacado'] ? '<span class="badge bg-warning text-dark me-1">⭐ Destacado</span>' : '';
            $badgeDispo = $row['disponible'] ? '<span class="badge bg-success">Disponible</span>' : '<span class="badge bg-danger">Ocupado</span>';

            // Estrellas promedio del trabajador (solo si tiene reseñas)
            $estrellasCard = '';
            if ($row['total_resenas'] > 0) {
                $estrellasCard = '<p class="cl-mini-estrellas mb-2">★ ' . number_format((float)$row['promedio'], 1, ',', '')
                               . ' <span class="text-secondary fw-normal">(' . $row['total_resenas'] . ' reseña' . ($row['total_resenas'] != 1 ? 's' : '') . ')</span></p>';
            }
            
            // Botones de la tarjeta según la sesión
            if (!isset($_SESSION['usuario_id'])) {
                // Sin sesión: no se puede ver el perfil ni contactar
                $botones = '<a href="login.php" class="btn cl-btn-dark w-100 fw-bold">Inicia sesión para contactar</a>';
            } else {
                // Si la tarjeta es del trabajador con sesión, no se le ofrece contactarse a sí mismo
                if ($miTrabajadorId && (int)$miTrabajadorId === (int)$row['id']) {
                    $btnContacto = '<span class="btn btn-secondary w-50 fw-bold disabled" aria-disabled="true">Es tu perfil</span>';
                } elseif ($row['disponible']) {
                    $btnContacto = '<a href="https://wa.me/' . htmlspecialchars($row['whatsapp']) . '" target="_blank" class="btn cl-btn-wa w-50 fw-bold">Contactar</a>';
                } else {
                    $btnContacto = '<button class="btn btn-secondary w-50 fw-bold" disabled>No disp.</button>';
                }
                $botones = '<a href="perfil.php?id=' . $row['id'] . '" class="btn btn-outline-dark w-50 fw-bold">Ver Perfil</a>' . $btnContacto;
            }

            // Dibujar la tarjeta HTML inyectando las variables de PHP
            echo '
            <div class="col-12 col-md-6 col-lg-4" data-oficio="' . htmlspecialchars($row['oficio']) . '" data-sector="' . htmlspecialchars($row['sector']) . '" data-disponible="' . ($row['disponible'] ? '1' : '0') . '">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-3 cl-tarjeta" style="--i:' . $i . '">
                    <div class="mb-2">
                        ' . $badgeDestacado . $badgeDispo . '
                    </div>
                    <h5 class="card-title fw-bold mt-2 mb-0" style="color: var(--cl-ink);">' . htmlspecialchars($row['nombre']) . '</h5>
                    <p class="text-secondary small mb-2">' . htmlspecialchars($row['oficio']) . ' • ' . htmlspecialchars($row['sector']) . '</p>
                    ' . $estrellasCard . '
                    <p class="card-text small text-muted mb-3">' . htmlspecialchars($row['descripcion']) . '</p>
                    <div class="mt-auto">
                        <p class="fw-bold mb-3" style="color: var(--cl-ink);">' . htmlspecialchars($row['tarifa']) . '</p>
                        
                        <!-- Botones según la sesión -->
                        <div class="d-flex gap-2">
                            ' . $botones . '
                        </div>

                    </div>
                </div>
            </div>';
        }
    } else {
        echo '<div class="col-12 text-center"><p class="text-muted">Aún no hay trabajadores registrados en la plataforma.</p></div>';
    }
    ?>
</div>

        <div class="cl-vacio d-none" id="vacio">
          <p class="fw-bold mb-1">No encontramos a nadie con esos filtros.</p>
          <p class="mb-0">Prueba con otro sector o desactiva "solo disponibles".</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="cl-footer">
    <div class="container">ConectaLocal · Proyecto de práctica en Agencia GAMA · 2026</div>
  </footer>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/buscar.js"></script>
</body>
</html>
