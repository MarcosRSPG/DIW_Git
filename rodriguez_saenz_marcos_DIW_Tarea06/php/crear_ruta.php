<?php

session_start();
require_once __DIR__.'/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../nuevaRuta.html');
    exit;
}

$errors = [];

$userId = (int) $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$date = trim($_POST['date'] ?? '');
$difficulty = trim($_POST['difficulty'] ?? '');
$startLocation = trim($_POST['start_location'] ?? '');
$endLocation = trim($_POST['end_location'] ?? '');
$timeHours = trim($_POST['time_hours'] ?? '');
$timeMinutes = trim($_POST['time_minutes'] ?? '');
$tags = $_POST['tags'] ?? [];

if ($title === '') {
    $errors[] = 'El título de la ruta es obligatorio.';
}

if ($userId <= 0) {
    $errors[] = 'Usuario no válido.';
}

$timeHours = ($timeHours === '') ? null : (int) $timeHours;
$timeMinutes = ($timeMinutes === '') ? null : (int) $timeMinutes;

$routeDate = ($date === '') ? null : $date;
$difficultyValue = ($difficulty === '') ? null : $difficulty;

$photoPath = null;

if (!empty($_FILES['route_image']) && $_FILES['route_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['route_image'];
    $errorCode = $file['error'];

    if ($errorCode === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        $name = $file['name'];
        $size = $file['size'];

        $maxSize = 5 * 1024 * 1024;
        if ($size > $maxSize) {
            $errors[] = 'La imagen es demasiado grande (máx. 5 MB).';
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Formato de imagen no permitido. Usa JPG, PNG, GIF o WEBP.';
            } else {
                $uploadDir = dirname(__DIR__).'/uploads';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'route_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $destination = $uploadDir.'/'.$newFileName;

                if (!move_uploaded_file($tmpName, $destination)) {
                    $errors[] = 'No se pudo guardar la imagen en el servidor.';
                } else {
                    $photoPath = 'uploads/'.$newFileName;
                }
            }
        }
    } else {
        $errors[] = 'Error al subir la imagen (código: '.$errorCode.').';
    }
}

if (!empty($errors)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Errores al crear la ruta</title>
    </head>
    <body>
        <h1>Se han producido errores</h1>
        <ul>
            <?php foreach ($errors as $err) { ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php } ?>
        </ul>
        <p><a href="../nuevaRuta.html">Volver al formulario</a></p>
    </body>
    </html>
    <?php
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO routes 
         (user_id, title, description, route_date, start_location, end_location,
          time_hours, time_minutes, difficulty, photo_path)
         VALUES
         (:user_id, :title, :description, :route_date, :start_location, :end_location,
          :time_hours, :time_minutes, :difficulty, :photo_path)'
    );

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);

    if ($routeDate === null) {
        $stmt->bindValue(':route_date', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':route_date', $routeDate, PDO::PARAM_STR);
    }

    $stmt->bindValue(
        ':start_location',
        $startLocation !== '' ? $startLocation : null,
        $startLocation !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );
    $stmt->bindValue(
        ':end_location',
        $endLocation !== '' ? $endLocation : null,
        $endLocation !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    if ($timeHours === null) {
        $stmt->bindValue(':time_hours', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':time_hours', $timeHours, PDO::PARAM_INT);
    }

    if ($timeMinutes === null) {
        $stmt->bindValue(':time_minutes', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':time_minutes', $timeMinutes, PDO::PARAM_INT);
    }

    if ($difficultyValue === null) {
        $stmt->bindValue(':difficulty', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':difficulty', $difficultyValue, PDO::PARAM_STR);
    }

    if ($photoPath === null) {
        $stmt->bindValue(':photo_path', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':photo_path', $photoPath, PDO::PARAM_STR);
    }

    $stmt->execute();
    $routeId = (int) $pdo->lastInsertId();

    if (is_array($tags) && !empty($tags)) {
        foreach ($tags as $tagSlug) {
            $tagSlug = trim($tagSlug);
            if ($tagSlug === '') {
                continue;
            }

            $stmt = $pdo->prepare(
                'SELECT id FROM tags 
                 WHERE slug = :slug OR name = :name
                 LIMIT 1'
            );
            $stmt->execute([
                ':slug' => $tagSlug,
                ':name' => $tagSlug,
            ]);
            $tag = $stmt->fetch();

            if ($tag) {
                $tagId = (int) $tag['id'];
            } else {
                $prettyName = str_replace('_', ' ', $tagSlug);
                $prettyName = mb_convert_case($prettyName, MB_CASE_TITLE, 'UTF-8');

                $stmtInsertTag = $pdo->prepare(
                    'INSERT INTO tags (name, slug)
                     VALUES (:name, :slug)'
                );
                $stmtInsertTag->execute([
                    ':name' => $prettyName,
                    ':slug' => $tagSlug,
                ]);

                $tagId = (int) $pdo->lastInsertId();
            }

            $stmtLink = $pdo->prepare(
                'INSERT IGNORE INTO route_tags (route_id, tag_id)
                 VALUES (:route_id, :tag_id)'
            );
            $stmtLink->execute([
                ':route_id' => $routeId,
                ':tag_id' => $tagId,
            ]);
        }
    }

    $pdo->commit();

    header('Location: ../index.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();

    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error al guardar la ruta</title>
    </head>
    <body>
        <h1>Error al guardar la ruta</h1>
        <p>Ocurrió un problema interno. Inténtalo de nuevo más tarde.</p>
        <p><a href="../nuevaRuta.html">Volver al formulario</a></p>
    </body>
    </html>
    <?php
    exit;
}
