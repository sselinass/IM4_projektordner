<?php
// api/get_family_members.php

require_once '_init.php';

$userId = require_user_id();

$stmt = $pdo->prepare("
    SELECT
        m.ID,
        m.name,
        m.icon,
        m.buzzer,
        m.id_users,
        m.is_active,
        COALESCE(points.total_points, 0) AS total_points
    FROM members m
    LEFT JOIN (
        SELECT
            be.id_members,
            SUM(be.points) AS total_points
        FROM buzzer_events be
        INNER JOIN rounds r ON r.ID = be.id_rounds
        WHERE r.Id_users = :points_user_id
          AND r.status IN ('active', 'completed', 'timeout')
        GROUP BY be.id_members
    ) points ON points.id_members = m.ID
    WHERE m.id_users = :user_id
      AND m.is_active = 1
    ORDER BY m.ID ASC
");

$stmt->execute([
    ':user_id' => $userId,
    ':points_user_id' => $userId
]);

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    'status' => 'success',
    'members' => $members
]);