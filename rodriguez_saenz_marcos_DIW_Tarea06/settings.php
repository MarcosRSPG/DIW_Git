<?php

session_start();
require_once __DIR__.'/php/config.php';
header('Content-Type: text/html; charset=UTF-8');

$theme = $_SESSION['theme'] ?? 'system';

if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light';
    $theme = $systemTheme;
}

if ($theme === 'dark') {
    $bodyClass = 'theme-dark';
} else {
    $bodyClass = 'theme-light';
}

$bodyClass = $theme === 'dark' ? 'theme-dark' : 'theme-light';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

$stmt = $pdo->prepare(
    'SELECT theme, items_per_page, email_notifications
     FROM user_settings
     WHERE user_id = :user_id
     LIMIT 1'
);
$stmt->execute([':user_id' => $userId]);
$currentSettings = $stmt->fetch();

$theme = $currentSettings['theme'] ?? 'system';
$itemsPerPage = $currentSettings['items_per_page'] ?? 10;
$emailNotifications = isset($currentSettings['email_notifications'])
    ? (bool) $currentSettings['email_notifications']
    : true;

$allowedThemes = ['light', 'dark', 'system'];
if (!in_array($theme, $allowedThemes, true)) {
    $theme = 'system';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $themePost = $_POST['theme'] ?? 'system';
    $itemsPost = $_POST['items_per_page'] ?? 10;
    $emailPost = $_POST['email_notifications'] ?? 0;
    $emailPostBool = $emailPost ? 1 : 0;

    if (!in_array($themePost, $allowedThemes, true)) {
        $errors[] = 'Tema seleccionado no válido.';
    }

    $itemsPost = (int) $itemsPost;
    $validItems = [5, 10, 20, 50];
    if (!in_array($itemsPost, $validItems, true)) {
        $errors[] = 'Número de elementos por página no válido.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO user_settings (user_id, theme, items_per_page, email_notifications)
             VALUES (:user_id, :theme, :items_per_page, :email_notifications)
             ON DUPLICATE KEY UPDATE
                theme = VALUES(theme),
                items_per_page = VALUES(items_per_page),
                email_notifications = VALUES(email_notifications)'
        );
        $_SESSION['theme'] = $themePost;

        $stmt->execute([
            ':user_id' => $userId,
            ':theme' => $themePost,
            ':items_per_page' => $itemsPost,
            ':email_notifications' => $emailPostBool,
        ]);

        $success = 'Ajustes guardados correctamente.';

        $theme = $themePost;
        $itemsPerPage = $itemsPost;
        $emailNotifications = (bool) $emailPostBool;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Ajustes de apariencia y notificaciones | Rutas de Monte</title>

<meta name="description" content="Configura el tema claro u oscuro, el número de rutas por página y las notificaciones por email en tu cuenta de Rutas de Monte.">
<meta name="robots" content="noindex,nofollow">
<link rel="canonical" href="https://rutasmonte.com/settings.php">

    <script defer src="./js/set-theme.js" type="module"></script>

    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link
      href="https:
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styleSettings.css" />
  </head>
  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <form id="settingsForm" method="post" action="settings.php">
      <h2 id="titulo">Ajustes de la cuenta</h2>

      <?php if (!empty($success)) { ?>
        <div class="alert-success">
          <p class="alert-success-text">
            <?php echo htmlspecialchars($success); ?>
          </p>
        </div>
      <?php } ?>

      <?php if (!empty($errors)) { ?>
        <div class="alert-error">
          <ul>
            <?php foreach ($errors as $err) { ?>
              <li><?php echo htmlspecialchars($err); ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>

      <div class="section-title">Apariencia</div>

      <label for="theme">Tema</label>
      <div class="radio-group">
        <label>
          <input
            type="radio"
            name="theme"
            value="light"
            <?php echo $theme === 'light' ? 'checked' : ''; ?>
          />
          Claro
        </label>
        <label>
          <input
            type="radio"
            name="theme"
            value="dark"
            <?php echo $theme === 'dark' ? 'checked' : ''; ?>
          />
          Oscuro
        </label>
        <label>
          <input
            type="radio"
            name="theme"
            value="system"
            <?php echo $theme === 'system' ? 'checked' : ''; ?>
          />
          Usar tema del sistema
        </label>
      </div>

      <label for="items_per_page">Elementos por página</label>
      <select id="items_per_page" name="items_per_page">
        <option value="5"  <?php echo $itemsPerPage == 5 ? 'selected' : ''; ?>>5</option>
        <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
        <option value="20" <?php echo $itemsPerPage == 20 ? 'selected' : ''; ?>>20</option>
        <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
      </select>

      <div class="section-title">Notificaciones</div>

      <label for="email_notifications">Notificaciones por correo</label>
      <div class="checkbox-group">
        <!-- hidden para enviar 0 cuando no se marque -->
        <input type="hidden" name="email_notifications" value="0" />
        <label>
          <input
            type="checkbox"
            id="email_notifications"
            name="email_notifications"
            value="1"
            <?php echo $emailNotifications ? 'checked' : ''; ?>
          />
          Recibir notificaciones por email
        </label>
      </div>

      <button type="submit" id="btnGuardar">Guardar cambios</button>

      <p id="volver">
        <a href="index.php">Volver al inicio</a>
      </p>
    </form>
  </body>
</html>
