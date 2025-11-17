<?php

session_start();
require_once __DIR__.'/config.php';

$isAjax = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjax = true;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'NO_AUTH']);
    } else {
        header('Location: ../login.php');
    }
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';

$routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($routeId <= 0 || $content === '') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'BAD_DATA']);
    } else {
        $_SESSION['comment_error'] = 'El comentario no puede estar vacío.';
        header('Location: ../index.php');
    }
    exit;
}

if (mb_strlen($content) > 1000) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'TOO_LONG']);
    } else {
        $_SESSION['comment_error'] = 'El comentario es demasiado largo.';
        header('Location: ../index.php');
    }
    exit;
}

$stmt = $pdo->prepare('
    INSERT INTO comments (route_id, user_id, content)
    VALUES (:rid, :uid, :content)
');
$stmt->execute([
    ':rid' => $routeId,
    ':uid' => $userId,
    ':content' => $content,
]);

$commentId = (int) $pdo->lastInsertId();
$createdAt = date('d/m/Y H:i');

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');

    if (function_exists('mb_substr')) {
        $initial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8');
    } else {
        $initial = strtoupper(substr($userName, 0, 1));
    }

    echo json_encode([
        'ok' => true,
        'comment' => [
            'id' => $commentId,
            'route_id' => $routeId,
            'author_name' => $userName,
            'initial' => $initial,
            'content' => $content,
            'created_at' => $createdAt,
        ],
    ]);
} else {
    $_SESSION['comment_ok'] = 'Comentario publicado correctamente.';
    header('Location: ../index.php');
}
