<?php
// api/create_buzzer_event.php
require_once '_init.php';

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

function calculatePoints(float $seconds): int
{
    if ($seconds <= 120) {
        return max(10, (int) round(100 - (90 / 120) * $seconds));
    }

    if ($seconds <= 300) {
        return max(0, (int) round(10 - (10 / 180) * ($seconds - 120)));
    }

    return 0;
}

try {
    $pdo->beginTransaction();

    // Aktive Runde suchen
    $roundStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            expected_member_count,
            TIMESTAMPDIFF(MICROSECOND, starttime, NOW()) AS age_microseconds
        FROM rounds
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
        FOR UPDATE
    ");

    $roundStmt->execute([
        ':user_id' => $userId
    ]);

    $round = $roundStmt->fetch(PDO::FETCH_ASSOC);

    if (!$round) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'Es läuft keine aktive Runde.'
        ], 409);
    }

    $roundId = (int) $round['ID'];
    $reactionTimeMs = (int) round(((int) $round['age_microseconds']) / 1000);
    $reactionSeconds = $reactionTimeMs / 1000;

    // Timeout prüfen
    if ($reactionSeconds >= 300) {
        $timeoutStmt = $pdo->prepare("
            UPDATE rounds
            SET status = 'timeout',
                ended_at = NOW()
            WHERE ID = :round_id
              AND Id_users = :user_id
              AND status = 'active'
        ");

        $timeoutStmt->execute([
            ':round_id' => $roundId,
            ':user_id' => $userId
        ]);

        $pdo->commit();

        json_response([
            'status' => 'error',
            'message' => 'Die Runde ist bereits abgelaufen.'
        ], 409);
    }

    // Member prüfen
    $memberStmt = $pdo->prepare("
        SELECT ID, name
        FROM members
        WHERE ID = :member_id
          AND id_users = :user_id
          AND is_active = 1
        LIMIT 1
    ");

    $memberStmt->execute([
        ':member_id' => $memberId,
        ':user_id' => $userId
    ]);

    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'Family Member wurde nicht gefunden oder ist nicht aktiv.'
        ], 404);
    }

    // Prüfen, ob Member bereits gedrückt hat
    $existingStmt = $pdo->prepare("
        SELECT ID
        FROM buzzer_events
        WHERE id_rounds = :round_id
          AND id_members = :member_id
        LIMIT 1
    ");

    $existingStmt->execute([
        ':round_id' => $roundId,
        ':member_id' => $memberId
    ]);

    if ($existingStmt->fetch()) {
        $pdo->rollBack();

        json_response([
            'status' => 'error',
            'message' => 'Dieser Family Member hat in dieser Runde bereits gedrückt.'
        ], 409);
    }

    $points = calculatePoints($reactionSeconds);

    // Platzierung berechnen
    $placementStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM buzzer_events
        WHERE id_rounds = :round_id
    ");

    $placementStmt->execute([
        ':round_id' => $roundId
    ]);

    $placement = ((int) $placementStmt->fetchColumn()) + 1;

    // Buzzer-Event speichern
    $insertStmt = $pdo->prepare("
        INSERT INTO buzzer_events
            (id_rounds, id_members, reaction_time_ms, points, placement)
        VALUES
            (:round_id, :member_id, :reaction_time_ms, :points, :placement)
    ");

    $insertStmt->execute([
        ':round_id' => $roundId,
        ':member_id' => $memberId,
        ':reaction_time_ms' => $reactionTimeMs,
        ':points' => $points,
        ':placement' => $placement
    ]);

    // Punkte zum aktiven Goal addieren
    $goalStmt = $pdo->prepare("
        UPDATE goal
        SET points_current = points_current + :points
        WHERE Id_users = :user_id
          AND is_active = 1
    ");

    $goalStmt->execute([
        ':points' => $points,
        ':user_id' => $userId
    ]);

    // Prüfen, ob alle erwarteten Members gedrückt haben
    $eventCountStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM buzzer_events
        WHERE id_rounds = :round_id
    ");

    $eventCountStmt->execute([
        ':round_id' => $roundId
    ]);

    $eventCount = (int) $eventCountStmt->fetchColumn();
    $expectedMemberCount = (int) $round['expected_member_count'];

    $roundCompleted = false;

    if ($eventCount >= $expectedMemberCount) {
        $completeStmt = $pdo->prepare("
            UPDATE rounds
            SET status = 'completed',
                ended_at = NOW()
            WHERE ID = :round_id
              AND Id_users = :user_id
              AND status = 'active'
        ");

        $completeStmt->execute([
            ':round_id' => $roundId,
            ':user_id' => $userId
        ]);

        $roundCompleted = true;
    }

    $pdo->commit();

    json_response([
        'status' => 'success',
        'event' => [
            'round_id' => $roundId,
            'member_id' => $memberId,
            'member_name' => $member['name'],
            'reaction_time_ms' => $reactionTimeMs,
            'points' => $points,
            'placement' => $placement
        ],
        'round_completed' => $roundCompleted
    ]);

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