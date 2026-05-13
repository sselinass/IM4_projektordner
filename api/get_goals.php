<?php
// api/get_goals.php
require_once '_init.php';

$userId = require_user_id();

$stmt = $pdo->prepare("
    SELECT
        g.ID,
        g.goal,
        g.points_required,
        u.points_balance AS points_current,
        g.is_active
    FROM goal g
    INNER JOIN users u ON u.id = g.Id_users
    WHERE g.Id_users = :user_id
    ORDER BY g.is_active DESC, g.ID ASC
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
