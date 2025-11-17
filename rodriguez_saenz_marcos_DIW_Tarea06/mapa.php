<?php
session_start();
require_once __DIR__.'/php/config.php';
header('Content-Type: text/html; charset=UTF-8');

$theme = $_SESSION['theme'] ?? 'system';

if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light';
    $theme = $systemTheme;
}

$bodyClass = ($theme === 'dark') ? 'theme-dark' : 'theme-light';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Mapa del sitio | Rutas de Monte</title>

    <meta name="description" content="Mapa del sitio de Rutas de Monte con las páginas principales y cómo se enlazan entre sí.">
    <meta name="robots" content="noindex,nofollow">
    <link rel="canonical" href="https://rutasmonte.com/mapa.php">

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

  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <header class="m-0 p-0 position-relative">
      <nav class="navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container">
          <div class="d-flex align-items-center gap-2" aria-label="Navegación mapa del sitio">
            <a
              href="index.php"
              class="icon-link text-light fs-1"
              aria-label="Volver al inicio"
              title="Volver al inicio"
            >
              <i class="bi bi-arrow-left-circle"></i>
            </a>
          </div>

          <span class="ms-auto text-white fw-semibold">
            <i class="bi bi-diagram-3-fill me-1"></i>
            Mapa del sitio
          </span>
        </div>
      </nav>
    </header>

    <main class="sitemap-main container">
      <div class="sitemap-card">
        <h1 class="h4 mb-3">Mapa del sitio</h1>
        <p class="text-muted mb-3">
          Haz clic en cualquier página para ir a ella. Las líneas muestran qué
          página enlaza o redirige a cuál.
        </p>

        <div class="sitemap-tree">
<a href="index.php">index.php</a>
├── <a href="perfil.php">perfil.php</a>  (icono "Perfil" en la barra superior)
│   ├── <a href="login.php">login.php</a>  (si no hay sesión activa, redirección automática)
│   └── <a href="index.php">index.php</a>  ("Volver al inicio" y tras guardar / cerrar sesión / eliminar usuario)
├── <a href="settings.php">settings.php</a>  (icono "Ajustes" en la barra superior)
│   ├── <a href="login.php">login.php</a>  (si no hay sesión activa, redirección automática)
│   └── <a href="index.php">index.php</a>  ("Volver al inicio")
├── <a href="nuevaRuta.php">nuevaRuta.php</a>  (icono "Añadir ruta" en la barra superior)
│   ├── <a href="login.php">login.php</a>  (si no hay sesión activa, redirección automática)
│   ├── <a href="./php/crear_ruta.php">./php/crear_ruta.php</a>  (envío del formulario de nueva ruta)
│   └── <a href="index.php">index.php</a>  ("Volver al inicio")
├── <a href="login.php">login.php</a>
│   ├── <a href="index.php">index.php</a>  (si ya estás logueado o tras iniciar sesión correctamente)
│   └── <a href="registro.php">registro.php</a>  (enlace "Regístrate")
└── <a href="registro.php">registro.php</a>
    ├── <a href="index.php">index.php</a>  (redirección tras crear la cuenta correctamente)
    └── <a href="login.php">login.php</a>  (enlace "Inicia sesión")
        </div>
      </div>
    </main>
  </body>
</html>
