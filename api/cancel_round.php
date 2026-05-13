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
    $pdo->beginTransaction();

    // Aktive Runde des eingeloggten Users suchen
    $roundStmt = $pdo->prepare("
        SELECT ID
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
            'status' => 'success',
            'message' => 'Keine aktive Runde vorhanden.'
        ]);
    }

    $roundId = (int) $round['ID'];

    // Bereits gutgeschriebene Punkte dieser Runde summieren
    $pointsStmt = $pdo->prepare("
        SELECT COALESCE(SUM(points), 0)
        FROM buzzer_events
        WHERE id_rounds = :round_id
    ");

    $pointsStmt->execute([
        ':round_id' => $roundId
    ]);

    $pointsToRemove = (int) $pointsStmt->fetchColumn();

    // Punkte vom aktiven Goal wieder abziehen
    if ($pointsToRemove > 0) {
        $goalStmt = $pdo->prepare("
            UPDATE users
            SET points_balance = GREATEST(points_balance - :points_to_remove, 0)
            WHERE id = :user_id
        ");

        $goalStmt->execute([
            ':points_to_remove' => $pointsToRemove,
            ':user_id' => $userId
        ]);
    }

    // Runde abbrechen
    $cancelStmt = $pdo->prepare("
        UPDATE rounds
        SET status = 'cancelled',
            ended_at = NOW()
        WHERE ID = :round_id
          AND Id_users = :user_id
          AND status = 'active'
    ");

    $cancelStmt->execute([
        ':round_id' => $roundId,
        ':user_id' => $userId
    ]);

    $pdo->commit();

    json_response([
        'status' => 'success',
        'round_id' => $roundId,
        'removed_points' => $pointsToRemove
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'status' => 'error',
        'message' => 'Runde konnte nicht zurückgesetzt werden.',
        'debug' => $e->getMessage()
    ], 500);
}
