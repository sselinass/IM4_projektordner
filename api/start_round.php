<?php
// api/start_round.php - API-Endpunkt zum Starten einer neuen Runde, einschließlich Transaktionsmanagement und Fehlerbehandlung

require_once '_init.php';
require_once '_game_logic.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

try {
    $eventTime = game_db_now($pdo);

    $pdo->beginTransaction();

    $round = game_start_round($pdo, $userId, $eventTime);

    game_log_input_event(
        $pdo,
        'Start',
        $eventTime,
        $userId,
        'web'
    );

    $pdo->commit();

    json_response([
        'status' => 'success',
        'round' => $round
    ]);

} catch (GameException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => $e->getMessage()
    ], $e->statusCode);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Runde konnte nicht gestartet werden.',
        'debug' => $e->getMessage()
    ], 500);
}