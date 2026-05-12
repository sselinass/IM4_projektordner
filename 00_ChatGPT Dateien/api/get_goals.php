<?php
// api/get_goals.php
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
    ORDER BY is_active DESC, ID ASC
");
$stmt->execute([':user_id' => $userId]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeGoal = null;
$futureGoals = [];

foreach ($goals as $item) {
    if ((int) $item['is_active'] === 1) {
        $activeGoal = $item;
    } else {
        $futureGoals[] = $item;
    }
}

json_response([
    'status' => 'success',
    'active_goal' => $activeGoal,
    'future_goals' => $futureGoals
]);
