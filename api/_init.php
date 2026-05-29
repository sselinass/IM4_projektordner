<?php
// api/_init.php - Initialisierungsdatei für API-Endpunkte, die gemeinsame Funktionen und Konfigurationen bereitstellt, einschließlich Benutzersitzungsvalidierung, JSON-Antwortformatierung und Fehlerbehandlung
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once '../system/config.php';

function require_user_id(): int
{
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    return (int) $_SESSION['user_id'];
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}
