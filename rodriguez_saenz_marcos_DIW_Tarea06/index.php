<?php
session_start();
require_once __DIR__.'/php/config.php';
header('Content-Type: text/html; charset=UTF-8');

/* =========================================================
   TEMA (light / dark / system)
   ========================================================= */
$theme = $_SESSION['theme'] ?? 'system';

if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light';
    $theme = $systemTheme;
}
$bodyClass = ($theme === 'dark') ? 'theme-dark' : 'theme-light';

/* =========================================================
   BÚSQUEDA + FILTROS (GET)
   ========================================================= */

// Búsqueda por texto (título / descripción)
$q = trim($_GET['q'] ?? '');

// Dificultad (array)
$difficulties = $_GET['difficulty'] ?? [];
if (!is_array($difficulties)) {
    $difficulties = [$difficulties];
}

// Fechas
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Duración (horas)
$minHours = $_GET['min_hours'] ?? '';
$maxHours = $_GET['max_hours'] ?? '';

// Tags seleccionados
$tagsFilter = $_GET['tags'] ?? [];
if (!is_array($tagsFilter)) {
    $tagsFilter = [$tagsFilter];
}
/* =========================================================
   LISTA DE TAGS DESDE LA BASE DE DATOS (solo los usados en rutas)
   ========================================================= */
$allTags = [];
try {
    $stmtAllTags = $pdo->query('
        SELECT DISTINCT t.name
        FROM tags t
        INNER JOIN route_tags rt ON rt.tag_id = t.id
        ORDER BY t.name ASC
    ');
    $allTags = $stmtAllTags->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allTags = [];
}

/* =========================================================
   Paginación: nº rutas por página desde user_settings
   ========================================================= */
$itemsPerPage = 10; // por defecto

if (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    $stmtCfg = $pdo->prepare('SELECT items_per_page FROM user_settings WHERE user_id = :uid');
    $stmtCfg->execute([':uid' => $userId]);
    $cfg = $stmtCfg->fetch(PDO::FETCH_ASSOC);
    if ($cfg && (int) $cfg['items_per_page'] > 0) {
        $itemsPerPage = (int) $cfg['items_per_page'];
    }
}

// Página actual
$page = max(1, (int) ($_GET['page'] ?? 1));

/* =========================================================
   Construir filtros dinámicamente (WHERE / JOIN)
   ========================================================= */
$where = [];
$params = [];
$joinTags = '';

// Búsqueda por texto
if ($q !== '') {
    $where[] = '(r.title LIKE :q OR r.description LIKE :q)';
    $params[':q'] = '%'.$q.'%';
}

// Dificultad
$allowedDiffs = ['facil', 'media', 'dificil', 'experto'];
$difficulties = array_map('strtolower', array_map('trim', $difficulties));
$difficulties = array_values(array_intersect($difficulties, $allowedDiffs));

if (!empty($difficulties)) {
    $diffPlaceholders = [];
    foreach ($difficulties as $idx => $diff) {
        $ph = ':diff'.$idx;
        $diffPlaceholders[] = $ph;
        $params[$ph] = $diff;
    }
    $where[] = 'r.difficulty IN ('.implode(',', $diffPlaceholders).')';
}

// Fechas
if ($dateFrom !== '') {
    $where[] = 'r.route_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'r.route_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

// Duración (usa solo horas, simple)
if ($minHours !== '' && is_numeric($minHours)) {
    $where[] = 'r.time_hours >= :min_hours';
    $params[':min_hours'] = (int) $minHours;
}
if ($maxHours !== '' && is_numeric($maxHours)) {
    $where[] = 'r.time_hours <= :max_hours';
    $params[':max_hours'] = (int) $maxHours;
}

// Tags: rutas que tengan AL MENOS uno de los tags seleccionados
$tagsFilter = array_filter(array_map('trim', $tagsFilter));
if (!empty($tagsFilter)) {
    $joinTags = ' 
        INNER JOIN route_tags rt ON rt.route_id = r.id
        INNER JOIN tags t ON t.id = rt.tag_id
    ';

    $tagPlaceholders = [];
    foreach ($tagsFilter as $idx => $tagName) {
        $ph = ':tag'.$idx;
        $tagPlaceholders[] = $ph;
        $params[$ph] = $tagName;
    }
    $where[] = 't.name IN ('.implode(',', $tagPlaceholders).')';
}

$whereSql = $where ? (' WHERE '.implode(' AND ', $where)) : '';

/* =========================================================
   TOTAL DE RUTAS (para paginación)
   ========================================================= */
$sqlCount = '
    SELECT COUNT(DISTINCT r.id)
    FROM routes r
    INNER JOIN users u ON r.user_id = u.id
    '.$joinTags.'
    '.$whereSql;

$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRoutes = (int) $stmtCount->fetchColumn();

if ($totalRoutes === 0) {
    $totalPages = 1;
} else {
    $totalPages = (int) ceil($totalRoutes / $itemsPerPage);
}

// Ajustar página si se va de rango
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $itemsPerPage;

/* =========================================================
   CONSULTA DE RUTAS CON LIMIT/OFFSET
   ========================================================= */
$sqlRoutes = '
    SELECT 
        r.id,
        r.title,
        r.description,
        r.route_date,
        r.start_location,
        r.end_location,
        r.time_hours,
        r.time_minutes,
        r.difficulty,
        r.photo_path,
        r.created_at,
        u.name AS author_name
    FROM routes r
    INNER JOIN users u ON r.user_id = u.id
    '.$joinTags.'
    '.$whereSql.'
    GROUP BY r.id
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
';

$stmtRoutes = $pdo->prepare($sqlRoutes);

// Vinculamos primero los filtros
foreach ($params as $key => $value) {
    $stmtRoutes->bindValue($key, $value);
}
// Y luego limit / offset como enteros
$stmtRoutes->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmtRoutes->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmtRoutes->execute();
$routes = $stmtRoutes->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   TAGS Y COMENTARIOS POR RUTA
   ========================================================= */
$routeIds = array_column($routes, 'id');
$tagsByRoute = [];
$commentsByRoute = [];

// Reacciones del usuario (like/dislike)
$userReactions = [];

if (!empty($routeIds)) {
    // LIKE / DISLIKE
    if (isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
        $idList = implode(',', array_map('intval', $routeIds));

        $sqlReacts = "
            SELECT route_id, dis_li
            FROM li_dis_route
            WHERE user_id = :uid
              AND route_id IN ($idList)
        ";
        $stmtReacts = $pdo->prepare($sqlReacts);
        $stmtReacts->execute([':uid' => $userId]);

        while ($row = $stmtReacts->fetch(PDO::FETCH_ASSOC)) {
            $userReactions[(int) $row['route_id']] = $row['dis_li'] ? 'like' : 'dislike';
        }
    }

    // TAGS
    $inPlaceholders = implode(',', array_fill(0, count($routeIds), '?'));
    $stmtTags = $pdo->prepare("
        SELECT rt.route_id, t.name
        FROM route_tags rt
        INNER JOIN tags t ON rt.tag_id = t.id
        WHERE rt.route_id IN ($inPlaceholders)
        ORDER BY t.name
    ");
    $stmtTags->execute($routeIds);

    foreach ($stmtTags as $row) {
        $rid = (int) $row['route_id'];
        $tagsByRoute[$rid][] = $row['name'];
    }

    // COMENTARIOS
    $stmtComments = $pdo->prepare("
        SELECT 
            c.id,
            c.route_id,
            c.content,
            c.created_at,
            u.name AS author_name
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.route_id IN ($inPlaceholders)
        ORDER BY c.created_at DESC
    ");
    $stmtComments->execute($routeIds);

    foreach ($stmtComments as $row) {
        $rid = (int) $row['route_id'];
        $commentsByRoute[$rid][] = $row;
    }
}

/* =========================================================
   FUNCIONES AUXILIARES
   ========================================================= */
function formatDateShort(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    try {
        $dt = new DateTime($date);

        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return $date;
    }
}

function formatDuration($h, $m): string
{
    $parts = [];
    if ($h !== null && $h !== '') {
        $parts[] = (int) $h.' h';
    }
    if ($m !== null && $m !== '') {
        $parts[] = (int) $m.' m';
    }

    return $parts ? implode(' ', $parts) : '-';
}

function difficultyClass(?string $difficulty): string
{
    $d = strtolower(trim((string) $difficulty));
    if (in_array($d, ['facil', 'fácil', 'easy'])) {
        return 'route-difficulty-facil';
    }
    if (in_array($d, ['media', 'medio', 'medium'])) {
        return 'route-difficulty-media';
    }
    if (in_array($d, ['dificil', 'difícil', 'hard'])) {
        return 'route-difficulty-dificil';
    }
    if (in_array($d, ['experto', 'expert'])) {
        return 'route-difficulty-experto';
    }

    return '';
}

function initialFromName(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }

    return strtoupper(substr($name, 0, 1));
}

// Error de comentario (si viene de un POST sin AJAX)
$commentError = $_SESSION['comment_error'] ?? '';
unset($_SESSION['comment_error']);

/* =========================================================
   Para mantener parámetros en la paginación (menos page)
   ========================================================= */
$baseQuery = $_GET;
unset($baseQuery['page']);
?>

<?php
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userInitial = $isLoggedIn ? initialFromName($userName) : '';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rutas de montaña</title>

    <script defer src="./js/index.js" type="module"></script>
    <script defer src="./js/set-theme.js" type="module"></script>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      rel="stylesheet"
    />

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="./styles.css" />
  </head>

<body class="<?php echo htmlspecialchars($bodyClass); ?> page-index">
    <header class="m-0 p-0 position-relative">
      <nav class="navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container">
          <div
            class="d-flex align-items-center gap-2"
            role="utilities"
            aria-label="Utilidades"
          >
            <a
  href="perfil.php"
  class="icon-link text-primary fs-1 d-flex align-items-center justify-content-center"
  aria-label="Perfil"
  title="Perfil"
>
  <?php if ($isLoggedIn) { ?>
    <div class="route-comment-avatar">
                      <?php echo htmlspecialchars($userInitial); ?>
                    </div>
  <?php } else { ?>
    <i class="bi bi-person-circle"></i>
  <?php } ?>
</a>


            <a
              href="settings.php"
              class="icon-link text-secondary fs-1"
              aria-label="Ajustes"
              title="Ajustes"
            >
              <i class="bi bi-gear-fill"></i>
            </a>

            <a
              href="nuevaRuta.php"
              class="icon-link text-danger fs-1"
              aria-label="Añadir ruta"
              title="Añadir ruta"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="plus-fat"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <line x1="12" y1="4" x2="12" y2="20"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
              </svg>
            </a>
          </div>

          <!-- BÚSQUEDA POR TEXTO (título/descripcion) -->
          <form
            class="d-flex align-items-center ms-auto"
            role="search"
            aria-label="Búsqueda"
            method="get"
            action="index.php"
          >
            <button
              id="btnFiltros"
              type="button"
              class="icon-link text-info fs-1 me-2"
              aria-label="Filtrar rutas"
              title="Filtrar rutas"
            >
              <i class="bi bi-funnel-fill"></i>
            </button>

            <input
              class="form-control me-2"
              type="search"
              placeholder="Buscar rutas..."
              aria-label="Buscar"
              name="q"
              value="<?php echo htmlspecialchars($q); ?>"
            />

            <button
              class="icon-link text-success fs-1 p-0 border-0 bg-transparent"
              type="submit"
              aria-label="Buscar"
              title="Buscar"
            >
              <i class="bi bi-search"></i>
            </button>
          </form>
        </div>
      </nav>

      <div class="intro position-relative overflow-hidden m-0">
        <div
          id="introCarousel"
          class="carousel slide h-100"
          data-bs-ride="carousel"
          data-bs-interval="5000"
        >
          <div class="carousel-inner h-100">
            <div class="carousel-item active h-100 position-relative">
              <img
                src="./img/vini-intro.jpg"
                alt="Paisaje de las Viniegras"
                class="img-intro position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
              />
            </div>
            <div class="carousel-item h-100 position-relative">
              <img
                src="./img/montanas-intro.jpg"
                alt="Montañas de los Cameros"
                class="img-intro position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
              />
            </div>
            <div class="carousel-item h-100 position-relative">
              <img
                src="./img/trevi-intro.jpg"
                alt="Paisaje de Trevijano"
                class="img-intro position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
              />
            </div>
            <div class="carousel-item h-100 position-relative">
              <img
                src="./img/piarrejas-intro.jpg"
                alt="Pantano de Piarrejas"
                class="img-intro position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
              />
            </div>
          </div>
        </div>

        <div
          class="intro-overlay position-absolute top-0 start-0 w-100 h-100"
        ></div>
        <h1 class="titulo-intro text-white p-4 position-absolute">
          RUTAS DE MONTE
        </h1>
      </div>
    </header>

    <main class="container my-5">
      <?php if ($commentError) { ?>
        <div class="alert-error mb-4">
          <span class="alert-error-icon">!</span>
          <p class="alert-error-text mb-0">
            <?php echo htmlspecialchars($commentError); ?>
          </p>
        </div>
      <?php } ?>

      <?php if (empty($routes)) { ?>
        <p class="text-center text-muted mt-4">
          No se han encontrado rutas con los criterios seleccionados.
        </p>
      <?php } else { ?>
        <?php foreach ($routes as $route) { ?>
          <?php
$routeId = (int) $route['id'];
            $tags = $tagsByRoute[$routeId] ?? [];
            $comments = $commentsByRoute[$routeId] ?? [];
            $commentsCount = count($comments);

            $difficultyClass = difficultyClass($route['difficulty'] ?? null);
            $dateText = formatDateShort($route['route_date'] ?? null);
            $durationText = formatDuration($route['time_hours'] ?? null, $route['time_minutes'] ?? null);
            $photoPath = !empty($route['photo_path']) ? htmlspecialchars($route['photo_path']) : './img/ruta-1.jpg';

            $currentReaction = $userReactions[$routeId] ?? 'none';
            ?>

          <!-- ======================= TARJETA DE RUTA ======================= -->
          <article class="route-card mb-3" data-route-id="<?php echo $routeId; ?>">
            <div class="row g-3 align-items-start route-content">
              <div class="col-md">
                <div class="route-main">
                  <header
                    class="route-header d-flex flex-wrap justify-content-between align-items-baseline p-1"
                  >
                    <h2 class="route-title h5 mb-0">
                      <?php echo htmlspecialchars($route['title']); ?>
                    </h2>

                    <?php if (!empty($route['difficulty'])) { ?>
                      <span class="route-difficulty <?php echo $difficultyClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($route['difficulty'])); ?>
                      </span>
                    <?php } ?>
                  </header>

                  <p class="route-description mb-3">
                    <?php echo nl2br(htmlspecialchars($route['description'])); ?>
                  </p>

                  <div class="route-bottom">
                    <div
                      class="route-tags d-flex flex-wrap align-items-center gap-2"
                    >
                      <span class="fw-semibold me-1 text-muted">Tags</span>
                      <?php if (empty($tags)) { ?>
                        <span class="tag-pill">Sin tags</span>
                      <?php } else { ?>
                        <?php foreach ($tags as $tagName) { ?>
                          <span class="tag-pill">
                            <?php echo htmlspecialchars($tagName); ?>
                          </span>
                        <?php } ?>
                      <?php } ?>
                    </div>

                    <div class="route-actions d-flex align-items-center gap-2">
                      <button
                        class="route-action-btn route-action-btn-comments"
                        type="button"
                        title="Comentarios"
                        data-route-id="<?php echo $routeId; ?>"
                      >
                        <i class="bi bi-chat-left-text"></i>
                      </button>

                      <button
                        class="route-action-btn route-action-btn-like <?php echo $currentReaction === 'like' ? 'route-action-btn--active' : ''; ?>"
                        type="button"
                        title="Me gusta"
                        data-route-id="<?php echo $routeId; ?>"
                      >
                        <i class="bi bi-hand-thumbs-up"></i>
                      </button>

                      <button
                        class="route-action-btn route-action-btn-dislike <?php echo $currentReaction === 'dislike' ? 'route-action-btn--active' : ''; ?>"
                        type="button"
                        title="No me gusta"
                        data-route-id="<?php echo $routeId; ?>"
                      >
                        <i class="bi bi-hand-thumbs-down"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-5 col-md-4 col-lg-3">
                <div class="map-box mb-2">
                  <img
                    src="<?php echo $photoPath; ?>"
                    alt="Mapa o miniatura de la ruta"
                    class="map-img"
                  />
                </div>

                <ul class="list-unstyled small mb-0 route-details">
                  <li>
                    <span class="muted-label">Fecha:</span>
                    <?php echo htmlspecialchars($dateText); ?>
                  </li>
                  <li>
                    <span class="muted-label">Salida:</span>
                    <?php echo htmlspecialchars($route['start_location'] ?? '-'); ?>
                  </li>
                  <li>
                    <span class="muted-label">Llegada:</span>
                    <?php echo htmlspecialchars($route['end_location'] ?? '-'); ?>
                  </li>
                  <li>
                    <span class="muted-label">Tiempo:</span>
                    <?php echo htmlspecialchars($durationText); ?>
                  </li>
                  <li>
                    <span class="muted-label">Autor:</span>
                    <?php echo htmlspecialchars($route['author_name'] ?? '-'); ?>
                  </li>
                </ul>
              </div>
            </div>
          </article>

          <!-- ======================= PANEL DE COMENTARIOS DE ESA RUTA ======================= -->
          <div class="route-comments-panel" data-route-id="<?php echo $routeId; ?>">
            <div class="route-comments-header">
              <div class="route-comments-title">
                <i class="bi bi-chat-left-text"></i>
                <span>
                  Comentarios (<span class="route-comments-count"><?php echo $commentsCount; ?></span>)
                </span>
              </div>
              <span class="route-comments-badge">Abierta</span>
            </div>

            <div class="route-comments-list">
              <?php if (empty($comments)) { ?>
                <p class="route-comment-text small text-muted">
                  Todavía no hay comentarios. Sé el primero en opinar.
                </p>
              <?php } else { ?>
                <?php foreach ($comments as $comment) {
                    $author = $comment['author_name'] ?? 'Usuario';
                    $initial = initialFromName($author);
                    ?>
                  <div class="route-comment">
                    <div class="route-comment-avatar">
                      <?php echo htmlspecialchars($initial); ?>
                    </div>
                    <div class="route-comment-body">
                      <div class="route-comment-meta">
                        <span class="route-comment-author">
                          <?php echo htmlspecialchars($author); ?>
                        </span>
                        <span class="route-comment-date">
                          <?php echo htmlspecialchars($comment['created_at']); ?>
                        </span>
                      </div>
                      <p class="route-comment-text">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                      </p>
                    </div>
                  </div>
                <?php } ?>
              <?php } ?>
            </div>

            <?php if (isset($_SESSION['user_id'])) { ?>
              <!-- Formulario nuevo comentario (AJAX) -->
              <form
                class="route-comment-new comment-form"
                method="post"
                action="./php/crear_comentario.php"
                data-route-id="<?php echo $routeId; ?>"
              >
                <input type="hidden" name="route_id" value="<?php echo $routeId; ?>" />

                <div class="route-comment-avatar route-comment-avatar-sm">
                  <?php echo htmlspecialchars(initialFromName($_SESSION['user_name'] ?? 'U')); ?>
                </div>

                <div class="route-comment-new-main">
                  <textarea
                    class="route-comment-input"
                    name="content"
                    rows="2"
                    placeholder="Escribe un comentario sobre esta ruta..."
                    required
                  ></textarea>

                  <div class="route-comment-new-actions">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary js-comment-cancel"
                    >
                      Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-success">
                      Publicar
                    </button>
                  </div>
                </div>
              </form>
            <?php } else { ?>
              <p class="small text-muted mb-0">
                Inicia sesión para comentar esta ruta.
              </p>
            <?php } ?>
          </div>
        <?php } ?>

        <!-- ======================= PAGINACIÓN ======================= -->
        <?php if ($totalRoutes > 0 && $totalPages > 1) { ?>
          <nav aria-label="Paginación de rutas" class="mt-4 d-flex justify-content-center">
            <ul class="pagination mb-0">
              <!-- Anterior -->
              <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a
                  class="page-link"
                  href="<?php
                        if ($page <= 1) {
                            echo '#';
                        } else {
                            $prevQuery = http_build_query(array_merge($baseQuery, ['page' => $page - 1]));
                            echo 'index.php?'.$prevQuery;
                        }
            ?>"
                  aria-label="Página anterior"
                >
                  &laquo;
                </a>
              </li>

              <?php for ($p = 1; $p <= $totalPages; ++$p) { ?>
                <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                  <a
                    class="page-link"
                    href="<?php
                $pageQuery = http_build_query(array_merge($baseQuery, ['page' => $p]));
                  echo 'index.php?'.$pageQuery;
                  ?>"
                  >
                    <?php echo $p; ?>
                  </a>
                </li>
              <?php } ?>

              <!-- Siguiente -->
              <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a
                  class="page-link"
                  href="<?php
                  if ($page >= $totalPages) {
                      echo '#';
                  } else {
                      $nextQuery = http_build_query(array_merge($baseQuery, ['page' => $page + 1]));
                      echo 'index.php?'.$nextQuery;
                  }
            ?>"
                  aria-label="Página siguiente"
                >
                  &raquo;
                </a>
              </li>
            </ul>
          </nav>
        <?php } ?>
      <?php } ?>
    </main>

    <!-- ======================= OVERLAY FILTROS ======================= -->
    <div id="filtersOverlay" class="filters-overlay">
      <div class="filters-modal">
        <button
          type="button"
          class="filters-modal-close"
          id="cerrarFiltros"
          aria-label="Cerrar filtros"
          title="Cerrar filtros"
        >
          <i class="bi bi-x-lg"></i>
        </button>

        <h2 class="filters-modal-title">Filtrar rutas</h2>

        <form
          id="filtersForm"
          method="get"
          action="index.php"
          class="filters-form"
        >
          <!-- Mantener texto de búsqueda dentro de los filtros -->
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>" />

          <div class="filters-field filters-full">
            <span class="form-label d-block">Dificultad</span>
            <div class="filters-chip-group">
              <label class="filters-chip">
                <input
                  type="checkbox"
                  name="difficulty[]"
                  value="facil"
                  <?php echo in_array('facil', $difficulties, true) ? 'checked' : ''; ?>
                />
                <span>Fácil</span>
              </label>
              <label class="filters-chip">
                <input
                  type="checkbox"
                  name="difficulty[]"
                  value="media"
                  <?php echo in_array('media', $difficulties, true) ? 'checked' : ''; ?>
                />
                <span>Media</span>
              </label>
              <label class="filters-chip">
                <input
                  type="checkbox"
                  name="difficulty[]"
                  value="dificil"
                  <?php echo in_array('dificil', $difficulties, true) ? 'checked' : ''; ?>
                />
                <span>Difícil</span>
              </label>
              <label class="filters-chip">
                <input
                  type="checkbox"
                  name="difficulty[]"
                  value="experto"
                  <?php echo in_array('experto', $difficulties, true) ? 'checked' : ''; ?>
                />
                <span>Experto</span>
              </label>
            </div>
          </div>

          <div class="filters-field">
            <label for="date_from" class="form-label">Fecha desde</label>
            <input
              type="date"
              id="date_from"
              name="date_from"
              class="form-control"
              value="<?php echo htmlspecialchars($dateFrom); ?>"
            />
          </div>

          <div class="filters-field">
            <label for="date_to" class="form-label">Fecha hasta</label>
            <input
              type="date"
              id="date_to"
              name="date_to"
              class="form-control"
              value="<?php echo htmlspecialchars($dateTo); ?>"
            />
          </div>

          <div class="filters-field">
            <label for="min_hours" class="form-label"
              >Duración mínima (horas)</label
            >
            <input
              type="number"
              id="min_hours"
              name="min_hours"
              min="0"
              max="48"
              class="form-control"
              value="<?php echo htmlspecialchars($minHours); ?>"
            />
          </div>

          <div class="filters-field">
            <label for="max_hours" class="form-label"
              >Duración máxima (horas)</label
            >
            <input
              type="number"
              id="max_hours"
              name="max_hours"
              min="0"
              max="48"
              class="form-control"
              value="<?php echo htmlspecialchars($maxHours); ?>"
            />
          </div>

          <div class="filters-field filters-full">
            <label for="tags" class="form-label">Tags</label>
            <select
  id="tags"
  name="tags[]"
  multiple
  size="3"
  class="form-select"
>
  <?php
  foreach ($allTags as $tagName) {
      $selected = in_array($tagName, $tagsFilter, true) ? 'selected' : '';
      echo '<option value="'.htmlspecialchars($tagName).'" '.$selected.'>'.
            htmlspecialchars(ucfirst($tagName)).
           '</option>';
  }
?>
</select>

            <small class="text-muted"
              >Pulsa Ctrl/Cmd para seleccionar varios</small
            >
          </div>

          <div class="filters-buttons filters-full">
  <button type="submit" class="btn btn-success px-4">
    Aplicar filtros
  </button>
  <!-- botón limpiar: recarga index.php SIN parámetros -->
  <button
    type="button"
    id="btnLimpiarFiltros"
    class="btn btn-outline-secondary px-4"
  >
    Limpiar
  </button>
</div>

        </form>
      </div>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
    ></script>
  </body>
</html>
