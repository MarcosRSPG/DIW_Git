<?php
session_start();
require_once __DIR__.'/php/config.php';
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

$theme = $_SESSION['theme'] ?? 'system';

if ($theme === 'system') {
    $systemTheme = $_SESSION['system_theme'] ?? 'light';
    $theme = $systemTheme;
}

$bodyClass = ($theme === 'dark') ? 'theme-dark' : 'theme-light';

$stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$username = $currentUser['name'];
$email = $currentUser['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'logout') {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }

    if ($action === 'delete') {
        $confirmDelete = trim($_POST['confirm_delete'] ?? '');
        if ($confirmDelete !== 'ELIMINAR') {
            $errors[] = 'Debes escribir "ELIMINAR" para confirmar el borrado de la cuenta.';
        } else {
            $delStmt = $pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
            $delStmt->execute([':id' => $userId]);

            session_unset();
            session_destroy();

            header('Location: index.php');
            exit;
        }
    }

    if ($action === 'save') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($username === '') {
            $errors[] = 'El nombre de usuario no puede estar vacío.';
        }

        if ($email === '') {
            $errors[] = 'El correo electrónico no puede estar vacío.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico no es válido.';
        }

        if (empty($errors)) {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $checkStmt->execute([
                ':email' => $email,
                ':id' => $userId,
            ]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Ese correo electrónico ya está en uso por otro usuario.';
            }
        }

        $wantsPasswordChange = (
            $currentPassword !== ''
            || $newPassword !== ''
            || $confirmPassword !== ''
        );

        if ($wantsPasswordChange && empty($errors)) {
            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Para cambiar la contraseña debes rellenar todos los campos de contraseña.';
            } else {
                if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                    $errors[] = 'La contraseña actual no es correcta.';
                }

                if (strlen($newPassword) < 8) {
                    $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
                }

                if ($newPassword !== $confirmPassword) {
                    $errors[] = 'La nueva contraseña y su repetición no coinciden.';
                }
            }
        }

        if (empty($errors)) {
            if ($wantsPasswordChange) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $sql = 'UPDATE users 
                        SET name = :name,
                            email = :email,
                            password_hash = :password_hash,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id';

                $params = [
                    ':name' => $username,
                    ':email' => $email,
                    ':password_hash' => $newHash,
                    ':id' => $userId,
                ];

                $currentUser['password_hash'] = $newHash;
            } else {
                $sql = 'UPDATE users 
                        SET name = :name,
                            email = :email,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id';

                $params = [
                    ':name' => $username,
                    ':email' => $email,
                    ':id' => $userId,
                ];
            }

            $upd = $pdo->prepare($sql);
            $upd->execute($params);

            $_SESSION['user_name'] = $username;
            $_SESSION['user_email'] = $email;

            $success = 'Datos de la cuenta actualizados correctamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Ajustes de la cuenta</title>

    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link
      href="https:
      rel="stylesheet"
    />
    <link rel="stylesheet" href="perfil.css" />
  </head>
  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <form id="accountForm" method="post" action="perfil.php">
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

      <!-- DATOS DE LA CUENTA -->
      <div class="section-card">
        <div class="section-card-title">Datos de la cuenta</div>

        <label for="username">Nombre de usuario</label>
        <input
          type="text"
          id="username"
          name="username"
          placeholder="Tu nombre visible"
          value="<?php echo htmlspecialchars($username); ?>"
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
      </div>

      <!-- CONTRASEÑA -->
      <div class="section-card">
        <div class="section-card-title">Contraseña</div>

        <label for="current_password">Contraseña actual</label>
        <input
          type="password"
          id="current_password"
          name="current_password"
          placeholder="Déjala en blanco si no quieres cambiarla"
        />

        <label for="new_password">Nueva contraseña</label>
        <input
          type="password"
          id="new_password"
          name="new_password"
          placeholder="Mínimo 8 caracteres (solo si cambias)"
        />

        <label for="confirm_password">Repite la nueva contraseña</label>
        <input
          type="password"
          id="confirm_password"
          name="confirm_password"
          placeholder="Solo si cambias la contraseña"
        />

        <p class="hint">
          Si no rellenas ningún campo de contraseña, se mantendrá la actual.
        </p>
      </div>

      <!-- ELIMINAR CUENTA -->
      <div class="section-card section-card-danger">
        <div class="section-card-title">Eliminar cuenta</div>

        <label for="confirm_delete">Confirmación</label>
        <div class="delete-box">
          <p class="hint">
            Esta acción es permanente. Escribe <strong>ELIMINAR</strong> para
            confirmar que quieres borrar tu usuario y todos tus datos.
          </p>
          <input
            type="text"
            id="confirm_delete"
            name="confirm_delete"
            placeholder="ELIMINAR"
          />
        </div>
      </div>

      <!-- BOTONES PRINCIPALES -->
      <div class="actions-row">
        <button type="submit" id="btnGuardar" name="action" value="save">
          Guardar cambios
        </button>

        <div class="actions-secondary">
          <button
            type="submit"
            class="btn-secondary"
            name="action"
            value="logout"
          >
            Cerrar sesión
          </button>

          <button type="submit" class="btn-danger" name="action" value="delete">
            Eliminar usuario
          </button>
        </div>
      </div>

      <p id="volver">
        <a href="index.php">Volver al inicio</a>
      </p>
    </form>
  </body>
</html>
