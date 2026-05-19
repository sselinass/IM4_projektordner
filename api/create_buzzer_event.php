<?php
// api/create_buzzer_event.php

require_once '_init.php';
require_once '_game_logic.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

$data = read_json_body();
$memberId = (int) ($data['member_id'] ?? 0);

if ($memberId <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Ungültige Member-ID.'
    ], 422);
}

try {
    $eventTime = game_db_now($pdo);

    $pdo->beginTransaction();

    $event = game_create_buzzer_event_from_member(
        $pdo,
        $userId,
        $memberId,
        $eventTime
    );

    game_log_input_event(
        $pdo,
        $event['event_code'],
        $eventTime,
        $userId,
        'web'
    );

    $pdo->commit();

    json_response([
        'status' => 'success',
        'event' => $event,
        'round_completed' => $event['round_completed']
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
        'message' => 'Buzzer-Event konnte nicht gespeichert werden.',
        'debug' => $e->getMessage()
    ], 500);
}