<?php
// api/create_family_member.php - API-Endpunkt zum Erstellen eines neuen Familienmitglieds, einschließlich Validierung der Eingabedaten, Transaktionsmanagement und Fehlerbehandlung

require_once '_init.php';

$userId = require_user_id();

$body = read_json_body();

$name = trim($body['name'] ?? '');
$icon = trim($body['icon'] ?? '');
$buzzer = trim($body['buzzer'] ?? '');

if ($name === '' || $icon === '' || $buzzer === '') {
    json_response([
        'status' => 'error',
        'message' => 'Missing fields'
    ], 400);
}

$allowedBuzzers = [
    'blue',
    'pink',
    'green',
    'orange'
];

if (!in_array($buzzer, $allowedBuzzers, true)) {
    json_response([
        'status' => 'error',
        'message' => 'Invalid buzzer'
    ], 400);
}

$allowedIcons = [
    'character_baby',
    'character_bird',
    'character_bunny',
    'character_cat',
    'character_crown',
    'character_fish',
    'character_flower',
    'character_heart',
    'character_lightning',
    'character_moon',
    'character_person',
    'character_personcircle',
    'character_smiley',
    'character_star',
    'character_tree'
];

if (!in_array($icon, $allowedIcons, true)) {
    json_response([
        'status' => 'error',
        'message' => 'Invalid icon'
    ], 400);
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM members
    WHERE id_users = :user_id
      AND is_active = 1
");

$countStmt->execute([
    ':user_id' => $userId
]);

$total = (int) $countStmt->fetchColumn();

if ($total >= 4) {
    json_response([
        'status' => 'error',
        'message' => 'Maximum members reached'
    ], 400);
}

$buzzerStmt = $pdo->prepare("
    SELECT ID
    FROM members
    WHERE id_users = :user_id
      AND buzzer = :buzzer
      AND is_active = 1
    LIMIT 1
");

$buzzerStmt->execute([
    ':user_id' => $userId,
    ':buzzer' => $buzzer
]);

$existingBuzzer = $buzzerStmt->fetch(PDO::FETCH_ASSOC);

if ($existingBuzzer) {
    json_response([
        'status' => 'error',
        'message' => 'Buzzer already assigned'
    ], 400);
}

$insertStmt = $pdo->prepare("
    INSERT INTO members (
        name,
        icon,
        buzzer,
        id_users,
        is_active
    )
    VALUES (
        :name,
        :icon,
        :buzzer,
        :user_id,
        1
    )
");

$insertStmt->execute([
    ':name' => $name,
    ':icon' => $icon,
    ':buzzer' => $buzzer,
    ':user_id' => $userId
]);

json_response([
    'status' => 'success',
    'message' => 'Family member created',
    'member' => [
        'ID' => (int) $pdo->lastInsertId(),
        'name' => $name,
        'icon' => $icon,
        'buzzer' => $buzzer,
        'id_users' => $userId,
        'is_active' => 1
    ]
]);