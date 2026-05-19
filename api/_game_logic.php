<?php
// api/_game_logic.php

class GameException extends Exception
{
    public int $statusCode;

    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }
}

function game_buzzer_color_map(): array
{
    return [
        'Buzzer_1' => 'blue',
        'Buzzer_2' => 'pink',
        'Buzzer_3' => 'green',
        'Buzzer_4' => 'orange'
    ];
}

function game_reverse_buzzer_color_map(): array
{
    return array_flip(game_buzzer_color_map());
}

function game_normalize_event_code(string $eventCode): string
{
    return trim($eventCode);
}

function game_parse_event_time(?string $eventTime): string
{
    if (!$eventTime) {
        throw new GameException('Timestamp fehlt.', 422);
    }

    $time = strtotime($eventTime);

    if ($time === false) {
        throw new GameException('Ungültiger Timestamp.', 422);
    }

    return date('Y-m-d H:i:s', $time);
}

function game_db_now(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')");
    return (string) $stmt->fetchColumn();
}

function game_log_input_event(
    PDO $pdo,
    string $eventCode,
    string $eventTime,
    int $userId,
    string $source
): int {
    $stmt = $pdo->prepare("
        INSERT INTO input_events
            (buzzer_events, source, `timestamp`, Id_users)
        VALUES
            (:event_code, :source, :event_time, :user_id)
    ");

    $stmt->execute([
        ':event_code' => $eventCode,
        ':source' => $source,
        ':event_time' => $eventTime,
        ':user_id' => $userId
    ]);

    return (int) $pdo->lastInsertId();
}

function game_calculate_points(int $seconds): int
{
    if ($seconds <= 120) {
        return max(10, (int) round(100 - (90 / 120) * $seconds));
    }

    if ($seconds <= 300) {
        return max(0, (int) round(10 - (10 / 180) * ($seconds - 120)));
    }

    return 0;
}

function game_get_expected_member_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM members
        WHERE id_users = :user_id
          AND is_active = 1
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    return (int) $stmt->fetchColumn();
}

function game_start_round(PDO $pdo, int $userId, string $eventTime): array
{
    $activeStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            TIMESTAMPDIFF(SECOND, starttime, :event_time) AS age_seconds
        FROM rounds
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
        FOR UPDATE
    ");

    $activeStmt->execute([
        ':user_id' => $userId,
        ':event_time' => $eventTime
    ]);

    $activeRound = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRound) {
        $ageSeconds = (int) $activeRound['age_seconds'];

        if ($ageSeconds >= 0 && $ageSeconds < 300) {
            throw new GameException('Es läuft bereits eine Runde.', 409);
        }

        $timeoutStmt = $pdo->prepare("
            UPDATE rounds
            SET status = 'timeout',
                ended_at = DATE_ADD(starttime, INTERVAL 300 SECOND)
            WHERE ID = :round_id
              AND Id_users = :user_id
              AND status = 'active'
        ");

        $timeoutStmt->execute([
            ':round_id' => $activeRound['ID'],
            ':user_id' => $userId
        ]);
    }

    $expectedMemberCount = game_get_expected_member_count($pdo, $userId);

    if ($expectedMemberCount === 0) {
        throw new GameException('Es sind keine aktiven Family Members vorhanden.', 409);
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO rounds
            (Id_users, starttime, status, expected_member_count)
        VALUES
            (:user_id, :starttime, 'active', :expected_member_count)
    ");

    $insertStmt->execute([
        ':user_id' => $userId,
        ':starttime' => $eventTime,
        ':expected_member_count' => $expectedMemberCount
    ]);

    $roundId = (int) $pdo->lastInsertId();

    return [
        'round_id' => $roundId,
        'starttime' => $eventTime,
        'status' => 'active',
        'expected_member_count' => $expectedMemberCount
    ];
}

function game_end_round(PDO $pdo, int $userId, string $eventTime): array
{
    $stmt = $pdo->prepare("
        SELECT ID
        FROM rounds
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $round = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$round) {
        return [
            'round_completed' => false,
            'message' => 'Keine aktive Runde vorhanden.'
        ];
    }

    $roundId = (int) $round['ID'];

    $updateStmt = $pdo->prepare("
        UPDATE rounds
        SET status = 'completed',
            ended_at = :event_time
        WHERE ID = :round_id
          AND Id_users = :user_id
          AND status = 'active'
    ");

    $updateStmt->execute([
        ':event_time' => $eventTime,
        ':round_id' => $roundId,
        ':user_id' => $userId
    ]);

    return [
        'round_completed' => true,
        'round_id' => $roundId
    ];
}

function game_create_buzzer_event_from_code(
    PDO $pdo,
    int $userId,
    string $eventCode,
    string $eventTime
): array {
    $map = game_buzzer_color_map();

    if (!isset($map[$eventCode])) {
        throw new GameException('Unbekannter Buzzer-Code.', 422);
    }

    $buzzerColor = $map[$eventCode];

    $memberStmt = $pdo->prepare("
        SELECT
            ID,
            name,
            buzzer
        FROM members
        WHERE id_users = :user_id
          AND buzzer = :buzzer
          AND is_active = 1
        LIMIT 1
    ");

    $memberStmt->execute([
        ':user_id' => $userId,
        ':buzzer' => $buzzerColor
    ]);

    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        throw new GameException('Für diesen Buzzer ist kein aktiver Family Member vorhanden.', 404);
    }

    return game_create_buzzer_event($pdo, $userId, $member, $eventCode, $eventTime);
}

function game_create_buzzer_event_from_member(
    PDO $pdo,
    int $userId,
    int $memberId,
    string $eventTime
): array {
    $memberStmt = $pdo->prepare("
        SELECT
            ID,
            name,
            buzzer
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
        throw new GameException('Family Member wurde nicht gefunden oder ist nicht aktiv.', 404);
    }

    $reverseMap = game_reverse_buzzer_color_map();
    $buzzerColor = $member['buzzer'];

    if (!isset($reverseMap[$buzzerColor])) {
        throw new GameException('Für diese Farbe ist kein physischer Buzzer definiert.', 422);
    }

    $eventCode = $reverseMap[$buzzerColor];

    return game_create_buzzer_event($pdo, $userId, $member, $eventCode, $eventTime);
}

function game_create_buzzer_event(
    PDO $pdo,
    int $userId,
    array $member,
    string $eventCode,
    string $eventTime
): array {
    $roundStmt = $pdo->prepare("
        SELECT
            ID,
            starttime,
            expected_member_count,
            TIMESTAMPDIFF(SECOND, starttime, :event_time) AS reaction_seconds
        FROM rounds
        WHERE Id_users = :user_id
          AND status = 'active'
        ORDER BY starttime DESC
        LIMIT 1
        FOR UPDATE
    ");

    $roundStmt->execute([
        ':user_id' => $userId,
        ':event_time' => $eventTime
    ]);

    $round = $roundStmt->fetch(PDO::FETCH_ASSOC);

    if (!$round) {
        throw new GameException('Es läuft keine aktive Runde.', 409);
    }

    $roundId = (int) $round['ID'];
    $memberId = (int) $member['ID'];
    $reactionSeconds = (int) $round['reaction_seconds'];

    if ($reactionSeconds < 0) {
        throw new GameException('Der Buzzer-Zeitpunkt liegt vor dem Startzeitpunkt.', 422);
    }

    if ($reactionSeconds >= 300) {
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

        throw new GameException('Die Runde ist bereits abgelaufen.', 409);
    }

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
        throw new GameException('Dieser Family Member hat in dieser Runde bereits gedrückt.', 409);
    }

    $points = game_calculate_points($reactionSeconds);

    $placementStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM buzzer_events
        WHERE id_rounds = :round_id
    ");

    $placementStmt->execute([
        ':round_id' => $roundId
    ]);

    $placement = ((int) $placementStmt->fetchColumn()) + 1;

    $insertStmt = $pdo->prepare("
        INSERT INTO buzzer_events
            (
                id_rounds,
                id_members,
                event_code,
                buzzer_code,
                pressed_at,
                reaction_time_seconds,
                points,
                placement
            )
        VALUES
            (
                :round_id,
                :member_id,
                :event_code,
                :buzzer_code,
                :pressed_at,
                :reaction_time_seconds,
                :points,
                :placement
            )
    ");

    $insertStmt->execute([
        ':round_id' => $roundId,
        ':member_id' => $memberId,
        ':event_code' => $eventCode,
        ':buzzer_code' => $eventCode,
        ':pressed_at' => $eventTime,
        ':reaction_time_seconds' => $reactionSeconds,
        ':points' => $points,
        ':placement' => $placement
    ]);

    $pointsStmt = $pdo->prepare("
        UPDATE users
        SET points_balance = points_balance + :points
        WHERE id = :user_id
    ");

    $pointsStmt->execute([
        ':points' => $points,
        ':user_id' => $userId
    ]);

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
                ended_at = :event_time
            WHERE ID = :round_id
              AND Id_users = :user_id
              AND status = 'active'
        ");

        $completeStmt->execute([
            ':event_time' => $eventTime,
            ':round_id' => $roundId,
            ':user_id' => $userId
        ]);

        $roundCompleted = true;
    }

    return [
        'round_id' => $roundId,
        'member_id' => $memberId,
        'member_name' => $member['name'],
        'event_code' => $eventCode,
        'reaction_time_seconds' => $reactionSeconds,
        'points' => $points,
        'placement' => $placement,
        'round_completed' => $roundCompleted
    ];
}