<?php
// api/start_round.php
require_once '_init.php';

$userId = require_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], 405);
}

// Aktuell blockieren wir konservativ für 5 Minuten.
// Sobald die definitive buzzer_events- und members-Struktur fix ist,
// kann zusätzlich geprüft werden, ob alle Family Members bereits gedrückt haben.
$latestStmt = $pdo->prepare("
    SELECT
        ID,
        starttime,
        TIMESTAMPDIFF(SECOND, starttime, NOW()) AS age_seconds
    FROM rounds
    WHERE Id_users = :user_id
    ORDER BY starttime DESC
    LIMIT 1
");
$latestStmt->execute([':user_id' => $userId]);
$latestRound = $latestStmt->fetch(PDO::FETCH_ASSOC);

if ($latestRound && (int) $latestRound['age_seconds'] < 300) {
    json_response([
        'status' => 'error',
        'message' => 'Es läuft bereits eine Runde.',
        'active_round' => [
            'round_id' => (int) $latestRound['ID'],
            'starttime' => $latestRound['starttime'],
            'seconds_elapsed' => (int) $latestRound['age_seconds'],
            'seconds_remaining' => 300 - (int) $latestRound['age_seconds']
        ]
    ], 409);
}

$insert = $pdo->prepare("
    INSERT INTO rounds (Id_users)
    VALUES (:user_id)
");
$insert->execute([':user_id' => $userId]);

$roundId = (int) $pdo->lastInsertId();

$getRound = $pdo->prepare("
    SELECT ID, starttime
    FROM rounds
    WHERE ID = :round_id
      AND Id_users = :user_id
    LIMIT 1
");
$getRound->execute([
    ':round_id' => $roundId,
    ':user_id' => $userId
]);
$round = $getRound->fetch(PDO::FETCH_ASSOC);

json_response([
    'status' => 'success',
    'round' => [
        'round_id' => (int) $round['ID'],
        'starttime' => $round['starttime']
    ]
]);
