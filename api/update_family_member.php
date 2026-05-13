<?php
// api/update_family_member.php

require_once '_init.php';

$userId = require_user_id();

$body = read_json_body();

$memberId = (int) ($body['id'] ?? 0);
$name = trim($body['name'] ?? '');
$icon = trim($body['icon'] ?? '');
$buzzer = trim($body['buzzer'] ?? '');

if ($memberId <= 0 || $name === '' || $icon === '' || $buzzer === '') {
    json_response([
        'status' => 'error',
        'message' => 'Missing fields'
    ], 400);
}

$duplicateStmt = $pdo->prepare("
    SELECT ID
    FROM members
    WHERE id_users = :user_id
      AND buzzer = :buzzer
      AND is_active = 1
      AND ID != :id
    LIMIT 1
");

$duplicateStmt->execute([
    ':user_id' => $userId,
    ':buzzer' => $buzzer,
    ':id' => $memberId
]);

if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response([
        'status' => 'error',
        'message' => 'Buzzer already assigned'
    ], 400);
}

$stmt = $pdo->prepare("
    UPDATE members
    SET
        name = :name,
        icon = :icon,
        buzzer = :buzzer
    WHERE ID = :id
      AND id_users = :user_id
      AND is_active = 1
");

$stmt->execute([
    ':name' => $name,
    ':icon' => $icon,
    ':buzzer' => $buzzer,
    ':id' => $memberId,
    ':user_id' => $userId
]);

json_response([
    'status' => 'success',
    'message' => 'Family member updated'
]);