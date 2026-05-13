<?php
// api/get_family_members.php

require_once '_init.php';

$userId = require_user_id();

$stmt = $pdo->prepare("
    SELECT
        ID,
        name,
        icon,
        buzzer,
        id_users,
        is_active
    FROM members
    WHERE id_users = :user_id
      AND is_active = 1
    ORDER BY ID ASC
");

$stmt->execute([
    ':user_id' => $userId
]);

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    'status' => 'success',
    'members' => $members
]);