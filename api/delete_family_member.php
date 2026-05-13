<?php
// api/delete_family_member.php

require_once '_init.php';

$userId = require_user_id();

$body = read_json_body();

$memberId = (int) ($body['id'] ?? 0);

if ($memberId <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Invalid member ID'
    ], 400);
}

$stmt = $pdo->prepare("
    UPDATE members
    SET is_active = 0
    WHERE ID = :id
      AND id_users = :user_id
");

$stmt->execute([
    ':id' => $memberId,
    ':user_id' => $userId
]);

json_response([
    'status' => 'success',
    'message' => 'Family member deleted'
]);