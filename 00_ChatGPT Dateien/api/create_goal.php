<?php
// api/create_goal.php
require_once '_init.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

$data = read_json_body();

$title = trim($data['goal'] ?? '');
$pointsRequired = (int) ($data['points_required'] ?? 0);

if ($title === '' || mb_strlen($title) > 50) {
    json_response([
        'status' => 'error',
        'message' => 'Bitte einen Goal-Titel mit maximal 50 Zeichen eingeben.'
    ], 422);
}

if ($pointsRequired <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Bitte eine gültige Punktezahl eingeben.'
    ], 422);
}

try {
    $pdo->beginTransaction();

    $activeStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM goal
        WHERE Id_users = :user_id
          AND is_active = 1
    ");
    $activeStmt->execute([':user_id' => $userId]);
    $hasActiveGoal = ((int) $activeStmt->fetchColumn()) > 0;

    $isActive = $hasActiveGoal ? 0 : 1;

    $insert = $pdo->prepare("
        INSERT INTO goal
            (goal, points_required, points_current, is_active, Id_users)
        VALUES
            (:goal, :points_required, 0, :is_active, :user_id)
    ");
    $insert->execute([
        ':goal' => $title,
        ':points_required' => $pointsRequired,
        ':is_active' => $isActive,
        ':user_id' => $userId
    ]);

    $goalId = (int) $pdo->lastInsertId();

    $pdo->commit();

    json_response([
        'status' => 'success',
        'goal_id' => $goalId,
        'is_active' => $isActive
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Goal konnte nicht gespeichert werden.'
    ], 500);
}
