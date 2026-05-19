<?php
// api/get_stats.php

require_once '_init.php';

$userId = require_user_id();

try {
    $membersStmt = $pdo->prepare("
        SELECT
            ID,
            name,
            icon,
            buzzer
        FROM members
        WHERE id_users = :user_id
          AND is_active = 1
        ORDER BY ID ASC
    ");

    $membersStmt->execute([
        ':user_id' => $userId
    ]);

    $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

    $currentWeekStart = new DateTimeImmutable('monday this week');
    $trendStart = $currentWeekStart->modify('-2 weeks');
    $trendEnd = $currentWeekStart->modify('+1 week');

    $weeklyStatsStmt = $pdo->prepare("
        SELECT
            be.id_members AS member_id,
            COALESCE(SUM(be.points), 0) AS total_points,
            COUNT(be.ID) AS dinner_count
        FROM buzzer_events be
        INNER JOIN rounds r ON r.ID = be.id_rounds
        WHERE r.Id_users = :user_id
          AND r.status IN ('completed', 'timeout')
          AND r.starttime >= :week_start
          AND r.starttime < :week_end
        GROUP BY be.id_members
    ");

    $weeklyStatsStmt->execute([
        ':user_id' => $userId,
        ':week_start' => $currentWeekStart->format('Y-m-d 00:00:00'),
        ':week_end' => $trendEnd->format('Y-m-d 00:00:00')
    ]);

    $weeklyStatsRaw = $weeklyStatsStmt->fetchAll(PDO::FETCH_ASSOC);

    $weeklyStatsByMember = [];

    foreach ($weeklyStatsRaw as $row) {
        $memberId = (int) $row['member_id'];
        $totalPoints = (int) $row['total_points'];
        $dinnerCount = (int) $row['dinner_count'];

        $weeklyStatsByMember[$memberId] = [
            'total_points' => $totalPoints,
            'dinner_count' => $dinnerCount,
            'average_points' => $dinnerCount > 0
                ? round($totalPoints / $dinnerCount, 1)
                : 0
        ];
    }

    $trendStmt = $pdo->prepare("
        SELECT
            be.id_members AS member_id,
            YEARWEEK(r.starttime, 3) AS week_key,
            COALESCE(SUM(be.points), 0) AS total_points
        FROM buzzer_events be
        INNER JOIN rounds r ON r.ID = be.id_rounds
        WHERE r.Id_users = :user_id
          AND r.status IN ('completed', 'timeout')
          AND r.starttime >= :trend_start
          AND r.starttime < :trend_end
        GROUP BY be.id_members, YEARWEEK(r.starttime, 3)
        ORDER BY week_key ASC
    ");

    $trendStmt->execute([
        ':user_id' => $userId,
        ':trend_start' => $trendStart->format('Y-m-d 00:00:00'),
        ':trend_end' => $trendEnd->format('Y-m-d 00:00:00')
    ]);

    $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    $trendByMemberAndWeek = [];

    foreach ($trendRows as $row) {
        $memberId = (int) $row['member_id'];
        $weekKey = (string) $row['week_key'];

        if (!isset($trendByMemberAndWeek[$memberId])) {
            $trendByMemberAndWeek[$memberId] = [];
        }

        $trendByMemberAndWeek[$memberId][$weekKey] = (int) $row['total_points'];
    }

    $weeks = [];

    for ($i = 0; $i < 3; $i++) {
        $weekStart = $trendStart->modify('+' . $i . ' weeks');

        $weeks[] = [
            'key' => $weekStart->format('oW'),
            'label' => 'Week ' . (int) $weekStart->format('W')
        ];
    }

    $memberResults = [];

    foreach ($members as $member) {
        $memberId = (int) $member['ID'];

        $stats = $weeklyStatsByMember[$memberId] ?? [
            'total_points' => 0,
            'dinner_count' => 0,
            'average_points' => 0
        ];

        $trendPoints = [];

        foreach ($weeks as $week) {
            $weekKey = $week['key'];

            $trendPoints[] = $trendByMemberAndWeek[$memberId][$weekKey] ?? 0;
        }

        $memberResults[] = [
            'ID' => $memberId,
            'name' => $member['name'],
            'icon' => $member['icon'],
            'buzzer' => $member['buzzer'],
            'total_points' => $stats['total_points'],
            'average_points' => $stats['average_points'],
            'dinner_count' => $stats['dinner_count'],
            'trend_points' => $trendPoints
        ];
    }

    json_response([
        'status' => 'success',
        'mode' => 'total',
        'current_week' => [
            'start' => $currentWeekStart->format('Y-m-d'),
            'end' => $trendEnd->modify('-1 day')->format('Y-m-d')
        ],
        'weeks' => $weeks,
        'members' => $memberResults
    ]);

} catch (Throwable $e) {
    json_response([
        'status' => 'error',
        'message' => 'Statistiken konnten nicht geladen werden.',
        'debug' => $e->getMessage()
    ], 500);
}