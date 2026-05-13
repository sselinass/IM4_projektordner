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

    // Aktives Goal laden
    $activeStmt = $pdo->prepare("
        SELECT
            ID,
            goal,
            points_required
        FROM goal
        WHERE Id_users = :user_id
          AND is_active = 1
        ORDER BY ID ASC
        LIMIT 1
        FOR UPDATE
    ");

    $activeStmt->execute([
        ':user_id' => $userId
    ]);

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

    // Globalen Punktestand des Users laden und sperren
    $userStmt = $pdo->prepare("
        SELECT points_balance
        FROM users
        WHERE id = :user_id
        LIMIT 1
        FOR UPDATE
    ");

    $userStmt->execute([
        ':user_id' => $userId
    ]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'User wurde nicht gefunden.'
        ], 404);
    }

    $pointsBalance = (int) $user['points_balance'];

    if ($pointsBalance < $pointsRequired) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'Dieses Goal hat noch nicht genügend Punkte.'
        ], 409);
    }

    // Punkte vom globalen Punktekonto abziehen
    $deductStmt = $pdo->prepare("
        UPDATE users
        SET points_balance = points_balance - :points_required
        WHERE id = :user_id
          AND points_balance >= :points_required
    ");

    $deductStmt->execute([
        ':points_required' => $pointsRequired,
        ':user_id' => $userId
    ]);

    if ($deductStmt->rowCount() === 0) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'Punkte konnten nicht eingelöst werden.'
        ], 409);
    }

    // Eingelöstes Goal in die Warteschlange verschieben
    $deactivateStmt = $pdo->prepare("
        UPDATE goal
        SET is_active = 0
        WHERE ID = :goal_id
          AND Id_users = :user_id
          AND is_active = 1
    ");

    $deactivateStmt->execute([
        ':goal_id' => $goalId,
        ':user_id' => $userId
    ]);

    // Nächstes Future Goal suchen
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
        // Falls kein anderes Goal existiert, bleibt das eingelöste Goal aktiv.
        $nextGoalId = $goalId;
    }

    // Neues aktives Goal setzen
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
        'next_goal_id' => $nextGoalId,
        'points_spent' => $pointsRequired,
        'points_balance_after' => $pointsBalance - $pointsRequired
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Goal konnte nicht eingelöst werden.',
        'debug' => $e->getMessage()
    ], 500);
}