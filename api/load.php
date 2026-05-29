<?php
// api/load.php - API-Endpunkt zum Verarbeiten von Events von physischen Computern, einschließlich Validierung der Eingabedaten, Transaktionsmanagement und Fehlerbehandlung

require_once '_init.php';
require_once '_game_logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

$data = read_json_body();

$eventCode = game_normalize_event_code((string) ($data['buzzer_events'] ?? ''));
$userId = (int) ($data['id_users'] ?? $data['Id_users'] ?? 0);
$eventTimeRaw = (string) ($data['timestamp'] ?? '');

if ($eventCode === '') {
    json_response([
        'status' => 'error',
        'message' => 'Event-Code fehlt.'
    ], 422);
}

if ($userId <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'User-ID fehlt.'
    ], 422);
}

try {
    $eventTime = game_parse_event_time($eventTimeRaw);

    $pdo->beginTransaction();

    $inputEventId = game_log_input_event(
        $pdo,
        $eventCode,
        $eventTime,
        $userId,
        'esp'
    );

    $result = [
        'input_event_id' => $inputEventId
    ];

    if ($eventCode === 'Start') {
        $round = game_start_round($pdo, $userId, $eventTime);
        $result['action'] = 'round_started';
        $result['round'] = $round;
    } elseif ($eventCode === 'End') {
        $endResult = game_end_round($pdo, $userId, $eventTime);
        $result['action'] = 'round_ended';
        $result = array_merge($result, $endResult);
    } else {
        $event = game_create_buzzer_event_from_code(
            $pdo,
            $userId,
            $eventCode,
            $eventTime
        );

        $result['action'] = 'buzzer_saved';
        $result['event'] = $event;
        $result['round_completed'] = $event['round_completed'];
    }

    $pdo->commit();

    json_response([
        'status' => 'success',
        'result' => $result
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
        'message' => 'Physical-Computing-Event konnte nicht verarbeitet werden.',
        'debug' => $e->getMessage()
    ], 500);
}