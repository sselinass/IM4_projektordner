<?php
// api/cancel_round.php
require_once '_init.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

try {
    $stmt = $pdo->prepare("
        UPDATE rounds
        SET status = 'cancelled',
            ended_at = NOW()
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    json_response([
        'status' => 'success'
    ]);

} catch (Throwable $e) {
    json_response([
        'status' => 'error',
        'message' => 'Runde konnte nicht abgebrochen werden.',
        'debug' => $e->getMessage()
    ], 500);
}