<?php
// login.php
session_start();
require_once './php/config.php';
header('Content-Type: text/html; charset=UTF-8');

// theme viene de la configuración del usuario (tabla user_settings + sesión)
$theme = $_SESSION['theme'] ?? 'system';

// Si el usuario ha elegido "system", usamos lo que detectamos con JS
if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light'; // por defecto claro
    $theme = $systemTheme; // ahora 'light' o 'dark'
}

if ($theme === 'dark') {
    $bodyClass = 'theme-dark';
} else {
    // cualquier cosa que no sea dark -> light
    $bodyClass = 'theme-light';
}

$bodyClass = $theme === 'dark' ? 'theme-dark' : 'theme-light';

$error = '';

// Si ya está logueado, puedes redirigirlo si quieres
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $password === '') {
        $error = 'Por favor, rellena usuario y contraseña.';
    } else {
        // Buscar usuario por nombre (si prefieres por email, se puede adaptar)
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password_hash 
             FROM users 
             WHERE name = :name 
             LIMIT 1'
        );
        $stmt->execute([':name' => $name]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login correcto: guardar datos en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Redirige a la página principal (ajusta si es index.php / index.html)
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
  <meta charset="UTF-8" />
  <link rel="stylesheet" href="./sytleLogin.css" />
  <title>Login</title>
  <script defer src="./js/set-theme.js" type="module"></script>

</head>

  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <form id="formLogin" method="post" action="login.php">
  <h2 id="titulo">Iniciar sesión</h2>

  <?php if (!empty($error)) { ?>
    <div class="alert-error">
      <span class="alert-error-icon">!</span>
      <p class="alert-error-text">
        <?php echo htmlspecialchars($error); ?>
      </p>
    </div>
  <?php } ?>

  <label id="lblUser" for="inputUser">Usuario</label>
  <input
    id="inputUser"
    type="text"
    name="name"
    placeholder="Tu usuario"
    required
  />

  <label id="lblPassword" for="inputPassword">Contraseña</label>
  <input
    id="inputPassword"
    type="password"
    name="password"
    placeholder="Tu contraseña"
    required
  />

  <button id="submitEntrar" type="submit">Entrar</button>

  <p id="registroLink">
    ¿No tienes cuenta?
    <a href="registro.php">Regístrate</a>
  </p>
</form>

  </body>
</html>
