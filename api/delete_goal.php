<?php
// api/delete_goal.php
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

$stmt = $pdo->prepare("
    DELETE FROM goal
    WHERE ID = :goal_id
      AND Id_users = :user_id
      AND is_active = 0
");
$stmt->execute([
    ':goal_id' => $goalId,
    ':user_id' => $userId
]);

if ($stmt->rowCount() === 0) {
    json_response([
        'status' => 'error',
        'message' => 'Goal konnte nicht gelöscht werden. Aktive Goals dürfen nicht gelöscht werden.'
    ], 409);
}

json_response([
    'status' => 'success'
]);
