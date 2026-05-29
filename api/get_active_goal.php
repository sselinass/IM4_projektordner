<?php
// api/get_active_goal.php - API-Endpunkt zum Abrufen des aktiven Ziels eines Benutzers, einschließlich Validierung der Benutzersitzung und Fehlerbehandlung
require_once '_init.php';

$userId = require_user_id();

$stmt = $pdo->prepare("
    SELECT
        g.ID,
        g.goal,
        g.points_required,
        u.points_balance AS points_current,
        g.is_active
    FROM goal g
    INNER JOIN users u ON u.id = g.Id_users
    WHERE g.Id_users = :user_id
      AND g.is_active = 1
    ORDER BY g.ID ASC
    LIMIT 1
");

$stmt->execute([':user_id' => $userId]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);

json_response([
    'status' => 'success',
    'goal' => $goal ?: null
]);
