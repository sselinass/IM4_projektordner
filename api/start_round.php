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

try {
    $pdo->beginTransaction();

    // Alte aktive Runde prüfen
    $activeStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            TIMESTAMPDIFF(SECOND, starttime, NOW()) AS age_seconds
        FROM rounds
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
        FOR UPDATE
    ");

    $activeStmt->execute([
        ':user_id' => $userId
    ]);

    $activeRound = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRound) {
        $ageSeconds = (int) $activeRound['age_seconds'];

        // Runde läuft noch
        if ($ageSeconds < 300) {
            $pdo->rollBack();

            json_response([
                'status' => 'error',
                'message' => 'Es läuft bereits eine Runde.',
                'active_round' => [
                    'round_id' => (int) $activeRound['ID'],
                    'starttime' => $activeRound['starttime'],
                    'seconds_elapsed' => $ageSeconds,
                    'seconds_remaining' => 300 - $ageSeconds
                ]
            ], 409);
        }

        // Runde ist älter als 5 Minuten: automatisch als Timeout beenden
        $timeoutStmt = $pdo->prepare("
            UPDATE rounds
            SET status = 'timeout',
                ended_at = NOW()
            WHERE ID = :round_id
              AND Id_users = :user_id
              AND status = 'active'
        ");

        $timeoutStmt->execute([
            ':round_id' => $activeRound['ID'],
            ':user_id' => $userId
        ]);
    }

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM members
    WHERE id_users = :user_id
      AND is_active = 1
");

$countStmt->execute([
    ':user_id' => $userId
]);

$expectedMemberCount = (int) $countStmt->fetchColumn();

if ($expectedMemberCount === 0) {
    $pdo->rollBack();

    json_response([
        'status' => 'error',
        'message' => 'Es sind keine aktiven Family Members vorhanden.'
    ], 409);
}

    // Neue Runde erstellen
    $insertStmt = $pdo->prepare("
        INSERT INTO rounds
            (Id_users, status, expected_member_count)
        VALUES
            (:user_id, 'active', :expected_member_count)
    ");

    $insertStmt->execute([
        ':user_id' => $userId,
        ':expected_member_count' => $expectedMemberCount
    ]);

    $roundId = (int) $pdo->lastInsertId();

    $roundStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            status,
            expected_member_count
        FROM rounds
        WHERE ID = :round_id
          AND Id_users = :user_id
        LIMIT 1
    ");

    $roundStmt->execute([
        ':round_id' => $roundId,
        ':user_id' => $userId
    ]);

    $round = $roundStmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    json_response([
        'status' => 'success',
        'round' => [
            'round_id' => (int) $round['ID'],
            'starttime' => $round['starttime'],
            'status' => $round['status'],
            'expected_member_count' => (int) $round['expected_member_count']
        ]
    ]);

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