<?php
// api/cancel_round.php - API-Endpunkt zum Abbrechen einer aktiven Runde, einschließlich Validierung der Benutzersitzung, Transaktionsmanagement und Fehlerbehandlung

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

    // Letzte noch zurücksetzbare Runde suchen
    // active = läuft noch
    // completed = wurde gerade durch alle Buzzer abgeschlossen
    $roundStmt = $pdo->prepare("
        SELECT ID, status
        FROM rounds
        WHERE Id_users = :user_id
          AND status IN ('active', 'completed')
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
            'message' => 'Keine zurücksetzbare Runde vorhanden.',
            'removed_points' => 0
        ]);
    }

    $roundId = (int) $round['ID'];

    // Punkte dieser Runde summieren
    $pointsStmt = $pdo->prepare("
        SELECT COALESCE(SUM(points), 0)
        FROM buzzer_events
        WHERE id_rounds = :round_id
    ");

    $pointsStmt->execute([
        ':round_id' => $roundId
    ]);

    $pointsToRemove = (int) $pointsStmt->fetchColumn();

    // Punkte vom globalen Punktekonto abziehen
    if ($pointsToRemove > 0) {
        $userStmt = $pdo->prepare("
            UPDATE users
            SET points_balance = GREATEST(points_balance - :points_to_remove, 0)
            WHERE id = :user_id
        ");

        $userStmt->execute([
            ':points_to_remove' => $pointsToRemove,
            ':user_id' => $userId
        ]);
    }

    // Reset als Rohdaten-Event speichern
    $logStmt = $pdo->prepare("
        INSERT INTO input_events
            (buzzer_events, source, `timestamp`, Id_users)
        VALUES
            ('Cancel', 'web', NOW(), :user_id)
    ");

    $logStmt->execute([
        ':user_id' => $userId
    ]);

    // Runde abbrechen
    $cancelStmt = $pdo->prepare("
        UPDATE rounds
        SET status = 'cancelled',
            ended_at = NOW()
        WHERE ID = :round_id
          AND Id_users = :user_id
          AND status IN ('active', 'completed')
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