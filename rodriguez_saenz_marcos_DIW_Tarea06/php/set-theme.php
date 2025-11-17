<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? '';

    if (in_array($theme, ['light', 'dark'], true)) {
        // Guardamos la preferencia detectada del sistema
        $_SESSION['system_theme'] = $theme;
        http_response_code(200);
        echo 'ok';
        exit;
    }
}

http_response_code(400);
echo 'invalid';
