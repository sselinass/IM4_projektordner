<?php
// api/collect_goal.php
require_once '_init.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

try {
    $pdo->beginTransaction();

    $activeStmt = $pdo->prepare("
        SELECT
            ID,
            goal,
            points_required,
            points_current
        FROM goal
        WHERE Id_users = :user_id
          AND is_active = 1
        ORDER BY ID ASC
        LIMIT 1
        FOR UPDATE
    ");
    $activeStmt->execute([':user_id' => $userId]);
    $activeGoal = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activeGoal) {
        $pdo->rollBack();
        json_response([
            'status' => 'error',
            'message' => 'Kein aktives Goal vorhanden.'
        ], 404);
    }

    $goalId = (int) $activeGoal['ID'];
    $pointsRequired = (int) $activeGoal['points_required'];
    $pointsCurrent = (int) $activeGoal['points_current'];

    if ($pointsCurrent < $pointsRequired) {
        $pdo->rollBack();
        json_response([
            'status' => 'error',
            'message' => 'Dieses Goal hat noch nicht genügend Punkte.'
        ], 409);
    }

    $reduceStmt = $pdo->prepare("
        UPDATE goal
        SET
            points_current = points_current - points_required,
            is_active = 0
        WHERE ID = :goal_id
          AND Id_users = :user_id
          AND is_active = 1
          AND points_current >= points_required
    ");
    $reduceStmt->execute([
        ':goal_id' => $goalId,
        ':user_id' => $userId
    ]);

    $nextStmt = $pdo->prepare("
        SELECT ID
        FROM goal
        WHERE Id_users = :user_id
          AND is_active = 0
          AND ID <> :collected_goal_id
        ORDER BY ID ASC
        LIMIT 1
        FOR UPDATE
    ");
    $nextStmt->execute([
        ':user_id' => $userId,
        ':collected_goal_id' => $goalId
    ]);
    $nextGoal = $nextStmt->fetch(PDO::FETCH_ASSOC);

    if ($nextGoal) {
        $nextGoalId = (int) $nextGoal['ID'];
    } else {
        // Fallback: Falls kein anderes Goal existiert, bleibt das eingelöste Goal aktiv.
        $nextGoalId = $goalId;
    }

    $activateStmt = $pdo->prepare("
        UPDATE goal
        SET is_active = 1
        WHERE ID = :goal_id
          AND Id_users = :user_id
    ");
    $activateStmt->execute([
        ':goal_id' => $nextGoalId,
        ':user_id' => $userId
    ]);

    $pdo->commit();

    json_response([
        'status' => 'success',
        'collected_goal_id' => $goalId,
        'next_goal_id' => $nextGoalId
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Goal konnte nicht eingelöst werden.'
    ], 500);
}
