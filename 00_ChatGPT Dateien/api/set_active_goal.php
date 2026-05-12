<?php
// api/set_active_goal.php
require_once '_init.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

$data = read_json_body();
$goalId = (int) ($data['goal_id'] ?? 0);

if ($goalId <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Ungültige Goal-ID.'
    ], 422);
}

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare("
        SELECT ID
        FROM goal
        WHERE ID = :goal_id
          AND Id_users = :user_id
        LIMIT 1
    ");
    $check->execute([
        ':goal_id' => $goalId,
        ':user_id' => $userId
    ]);

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        json_response([
            'status' => 'error',
            'message' => 'Goal wurde nicht gefunden.'
        ], 404);
    }

    $clear = $pdo->prepare("
        UPDATE goal
        SET is_active = 0
        WHERE Id_users = :user_id
    ");
    $clear->execute([':user_id' => $userId]);

    $activate = $pdo->prepare("
        UPDATE goal
        SET is_active = 1
        WHERE ID = :goal_id
          AND Id_users = :user_id
    ");
    $activate->execute([
        ':goal_id' => $goalId,
        ':user_id' => $userId
    ]);

    $pdo->commit();

    json_response([
        'status' => 'success'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Aktives Goal konnte nicht geändert werden.'
    ], 500);
}
