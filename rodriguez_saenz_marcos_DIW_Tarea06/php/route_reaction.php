<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'NO_AUTH']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
$action = $_POST['action'] ?? '';

if ($routeId <= 0 || !in_array($action, ['like', 'dislike', 'remove'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'BAD_REQUEST']);
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, dis_li
    FROM li_dis_route
    WHERE user_id = :uid AND route_id = :rid
    LIMIT 1
');
$stmt->execute([
    ':uid' => $userId,
    ':rid' => $routeId,
]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

$newStatus = 'none';

if ($action === 'remove') {
    if ($current) {
        $del = $pdo->prepare('DELETE FROM li_dis_route WHERE id = :id');
        $del->execute([':id' => $current['id']]);
    }
    $newStatus = 'none';
} elseif ($action === 'like') {
    if ($current && (int) $current['dis_li'] === 1) {
        $del = $pdo->prepare('DELETE FROM li_dis_route WHERE id = :id');
        $del->execute([':id' => $current['id']]);
        $newStatus = 'none';
    } else {
        if ($current) {
            $upd = $pdo->prepare('UPDATE li_dis_route SET dis_li = 1 WHERE id = :id');
            $upd->execute([':id' => $current['id']]);
        } else {
            $ins = $pdo->prepare('
                INSERT INTO li_dis_route (user_id, route_id, dis_li)
                VALUES (:uid, :rid, 1)
            ');
            $ins->execute([
                ':uid' => $userId,
                ':rid' => $routeId,
            ]);
        }
        $newStatus = 'like';
    }
} elseif ($action === 'dislike') {
    if ($current && (int) $current['dis_li'] === 0) {
        $del = $pdo->prepare('DELETE FROM li_dis_route WHERE id = :id');
        $del->execute([':id' => $current['id']]);
        $newStatus = 'none';
    } else {
        if ($current) {
            $upd = $pdo->prepare('UPDATE li_dis_route SET dis_li = 0 WHERE id = :id');
            $upd->execute([':id' => $current['id']]);
        } else {
            $ins = $pdo->prepare('
                INSERT INTO li_dis_route (user_id, route_id, dis_li)
                VALUES (:uid, :rid, 0)
            ');
            $ins->execute([
                ':uid' => $userId,
                ':rid' => $routeId,
            ]);
        }
        $newStatus = 'dislike';
    }
}

echo json_encode([
    'ok' => true,
    'status' => $newStatus,
]);
