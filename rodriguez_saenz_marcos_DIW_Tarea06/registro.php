<?php

session_start();
require_once './php/config.php';
header('Content-Type: text/html; charset=UTF-8');

$theme = $_SESSION['theme'] ?? 'system';

if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light';
    $theme = $systemTheme;
}

$bodyClass = ($theme === 'dark') ? 'theme-dark' : 'theme-light';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($name === '') {
        $errors[] = 'El nombre de usuario es obligatorio.';
    }

    if ($email === '') {
        $errors[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido.';
    }

    if ($password === '' || $password_confirm === '') {
        $errors[] = 'Debes introducir y repetir la contraseña.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT id, email, name 
             FROM users 
             WHERE email = :email OR name = :name 
             LIMIT 1'
        );
        $stmt->execute([
            ':email' => $email,
            ':name' => $name,
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['email'] === $email) {
                $errors[] = 'Ya existe una cuenta con ese correo electrónico.';
            }
            if ($existing['name'] === $name) {
                $errors[] = 'Ese nombre de usuario ya está en uso.';
            }
        }
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash) 
                 VALUES (:name, :email, :password_hash)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $password_hash,
            ]);

            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO user_settings (user_id) 
                 VALUES (:user_id)'
            );
            $stmt->execute([
                ':user_id' => $userId,
            ]);

            $pdo->commit();

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Ha ocurrido un error al crear la cuenta. Inténtalo de nuevo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Crear cuenta | Rutas de Monte</title>

<meta name="description" content="Crea una cuenta gratis para descubrir, guardar y compartir rutas de montaña y senderismo con la comunidad.">
<meta name="robots" content="noindex,nofollow">
<link rel="canonical" href="https://rutasmonte.com/registro.php">

    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link
      href="https:
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./styleRegister.css" />
    <script defer src="./js/set-theme.js" type="module"></script>
  </head>
  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <!-- novalidate para evitar los popups nativos feos del navegador -->
    <form id="formRegister" method="post" action="registro.php" novalidate>
      <h2 id="titulo">Crear cuenta</h2>

      <?php if (!empty($errors)) { ?>
        <div class="alert-error">
          <span class="alert-error-icon">!</span>
          <div class="alert-error-body">
            <p>Por favor, revisa los siguientes errores:</p>
            <ul>
              <?php foreach ($errors as $err) { ?>
                <li><?php echo htmlspecialchars($err); ?></li>
              <?php } ?>
            </ul>
          </div>
        </div>
      <?php } ?>

      <label for="name">Usuario</label>
      <input
        type="text"
        id="name"
        name="name"
        placeholder="Tu usuario"
        value="<?php echo htmlspecialchars($name); ?>"
        required
      />

      <label for="email">Correo electrónico</label>
      <input
        type="email"
        id="email"
        name="email"
        placeholder="tucorreo@ejemplo.com"
        value="<?php echo htmlspecialchars($email); ?>"
        required
      />

      <label for="password">Contraseña</label>
      <input
        type="password"
        id="password"
        name="password"
        placeholder="Mínimo 8 caracteres"
        required
      />

      <label for="password_confirm">Repetir contraseña</label>
      <input
        type="password"
        id="password_confirm"
        name="password_confirm"
        placeholder="Repite la contraseña"
        required
      />

      <button id="btnRegistrar" type="submit">Registrarme</button>

      <p id="loginLink">
        ¿Ya tienes cuenta?
        <a href="login.php">Inicia sesión</a>
      </p>
    </form>
  </body>
</html>
