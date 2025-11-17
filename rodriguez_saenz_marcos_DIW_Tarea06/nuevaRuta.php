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

$stmt = $pdo->query('SELECT id, name, slug FROM tags ORDER BY name ASC');
$tags = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Crear nueva ruta</title>
    <script defer src="./js/set-theme.js" type="module"></script>

    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link
      href="https:
      rel="stylesheet"
    />
    <script defer src="./js/newRuta.js" type="module"></script>
    <link rel="stylesheet" href="./styleNuevaRuta.css" />
  </head>
  <body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <form
      id="routeForm"
      method="post"
      action="./php/crear_ruta.php"
      enctype="multipart/form-data"
    >
      <h2 id="titulo">Crear nueva ruta</h2>

      <div class="field">
        <label for="title">Título de la ruta</label>
        <input
          type="text"
          id="title"
          name="title"
          placeholder="Ej. Circular al Pico Norte"
          required
        />
      </div>

      <!-- NUEVA SECCIÓN: DESCRIPCIÓN DE LA RUTA -->
      <div class="field">
        <label for="description">Descripción de la ruta</label>
        <textarea
          id="description"
          name="description"
          placeholder="Cuenta brevemente cómo es la ruta, puntos clave, tipo de terreno, vistas, etc."
          required
        ></textarea>
      </div>

      <div class="two-cols">
        <div class="field">
          <label for="date">Fecha</label>
          <input type="date" id="date" name="date" />
        </div>

        <div class="field">
          <label for="difficulty">Dificultad</label>
          <select id="difficulty" name="difficulty">
            <option value="">Selecciona la dificultad</option>
            <option value="facil">Fácil</option>
            <option value="media">Media</option>
            <option value="dificil">Difícil</option>
            <option value="experto">Experto</option>
          </select>
        </div>
      </div>

      <div class="two-cols">
        <div class="field">
          <label for="start_location">Salida</label>
          <input
            type="text"
            id="start_location"
            name="start_location"
            placeholder="Ej. Valle Verde"
          />
        </div>

        <div class="field">
          <label for="end_location">Llegada</label>
          <input
            type="text"
            id="end_location"
            name="end_location"
            placeholder="Ej. Pico Norte"
          />
        </div>
      </div>

      <div class="two-cols">
        <div class="field">
          <label for="time_hours">Duración</label>
          <div class="duration-group">
            <input
              type="number"
              id="time_hours"
              name="time_hours"
              min="0"
              max="48"
              placeholder="Horas"
            />
            <span class="duration-separator">h</span>
            <input
              type="number"
              id="time_minutes"
              name="time_minutes"
              min="0"
              max="59"
              placeholder="Min"
            />
            <span class="duration-separator">m</span>
          </div>
        </div>

        <div class="field">
          <label for="tags">Tags</label>
          <div class="tags-wrapper">
            <select
              id="tags"
              name="tags[]"
              multiple
              size="3"
              class="tags-select"
            >
              <?php if (!empty($tags)) { ?>
                <?php foreach ($tags as $tag) { ?>
                  <?php

                    $slug = $tag['slug'] ?: strtolower(str_replace(' ', '_', $tag['name']));
                    ?>
                  <option value="<?php echo htmlspecialchars($slug); ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                  </option>
                <?php } ?>
              <?php } ?>
            </select>

            <div class="new-tag-group">
              <input type="text" id="new_tag_input" placeholder="Nuevo tag" />
              <button type="button" id="add_tag_button">Añadir</button>
            </div>

            <small class="help-text">
              Ctrl/Cmd para varios. Puedes crear nuevos tags.
            </small>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: IMAGEN DE LA RUTA -->
      <div class="field">
        <label for="route_image">Imagen de la ruta</label>
        <input
          type="file"
          id="route_image"
          name="route_image"
          accept="image/*"
        />

        <div class="image-preview" id="image_preview_wrapper">
          <p class="image-preview-text">Previsualización:</p>
          <img id="image_preview" src="" alt="Previsualización de la ruta" />
        </div>
        <small class="help-text">
          Puedes subir una foto desde tu dispositivo (JPG, PNG, etc.).
        </small>
      </div>

      <button type="submit" id="btnGuardarRuta">Guardar ruta</button>

      <p id="volver">
        <a href="index.php">Volver al inicio</a>
      </p>
    </form>
  </body>
</html>
