<?php
// api/get_active_goal.php
require_once '_init.php';

$userId = require_user_id();

$stmt = $pdo->prepare("
    SELECT
        ID,
        goal,
        points_required,
        points_current,
        is_active
    FROM goal
    WHERE Id_users = :user_id
      AND is_active = 1
    ORDER BY ID ASC
    LIMIT 1
");
$stmt->execute([':user_id' => $userId]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);

json_response([
    'status' => 'success',
    'goal' => $goal ?: null
]);
