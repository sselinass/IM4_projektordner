<?php
// api/get_round_state.php

require_once '_init.php';

$userId = require_user_id();

try {
    $pdo->beginTransaction();

    $roundStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            status,
            expected_member_count,
            TIMESTAMPDIFF(SECOND, starttime, NOW()) AS seconds_elapsed
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
        $pdo->commit();

        json_response([
            'status' => 'success',
            'active_round' => null,
            'events' => []
        ]);
    }

    $roundId = (int) $round['ID'];
    $secondsElapsed = (int) $round['seconds_elapsed'];

    if ($secondsElapsed >= 300) {
        $timeoutStmt = $pdo->prepare("
            UPDATE rounds
            SET status = 'timeout',
                ended_at = DATE_ADD(starttime, INTERVAL 300 SECOND)
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
            'status' => 'success',
            'active_round' => null,
            'events' => [],
            'last_status' => 'timeout'
        ]);
    }

    $eventStmt = $pdo->prepare("
        SELECT
            be.ID,
            be.id_members AS member_id,
            be.event_code,
            be.buzzer_code,
            be.pressed_at,
            be.reaction_time_seconds,
            be.points,
            be.placement,
            m.name,
            m.icon,
            m.buzzer
        FROM buzzer_events be
        INNER JOIN members m ON m.ID = be.id_members
        WHERE be.id_rounds = :round_id
        ORDER BY be.placement ASC
    ");

    $eventStmt->execute([
        ':round_id' => $roundId
    ]);

    $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    json_response([
        'status' => 'success',
        'active_round' => [
            'round_id' => $roundId,
            'starttime' => $round['starttime'],
            'status' => $round['status'],
            'expected_member_count' => (int) $round['expected_member_count'],
            'seconds_elapsed' => $secondsElapsed,
            'seconds_remaining' => max(300 - $secondsElapsed, 0)
        ],
        'events' => $events
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Rundenstatus konnte nicht geladen werden.',
        'debug' => $e->getMessage()
    ], 500);
}