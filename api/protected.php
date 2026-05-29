<?php
// index.php (API that returns JSON about the logged-in user) - API-Endpunkt, der Informationen über den aktuell angemeldeten Benutzer zurückgibt, einschließlich Validierung der Sitzung und Rückgabe von Benutzerinformationen im JSON-Format
session_start();

if (!isset($_SESSION['user_id'])) {
    // Instead of redirect, return a 401 JSON response
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// If they are logged in, return user data
echo json_encode([
    "status" => "success",
    "user_id" => $_SESSION['user_id'],
    "email" => $_SESSION['email']
]);
