<?php
// api/get_members.php - API-Endpunkt zum Abrufen der aktiven Familienmitglieder eines Benutzers, einschließlich Validierung der Benutzersitzung und Fehlerbehandlung
require_once '_init.php';

$userId = require_user_id();

try {
    $stmt = $pdo->prepare("
        SELECT
            ID,
            name,
            icon,
            buzzer,
            is_active
        FROM members
        WHERE id_users = :user_id
          AND is_active = 1
        ORDER BY ID ASC
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'status' => 'success',
        'members' => $members
    ]);

} catch (Throwable $e) {
    json_response([
        'status' => 'error',
        'message' => 'Family Members konnten nicht geladen werden.',
        'debug' => $e->getMessage()
    ], 500);
}