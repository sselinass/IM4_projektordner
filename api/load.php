<?php
require_once("../system/config.php");

header('Content-Type: application/json');

// JSON Input lesen
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validierung: JSON korrekt?
if (!$input) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
    ]);
    exit;
}

// Werte extrahieren
$table = $input["table"] ?? null;
$wert = $input["wert"] ?? null;
$timestamp = $input["timestamp"] ?? null;

// Validierung Pflichtfelder
if (!$table || !$wert) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

/**
 * Erlaubte Tabellen (Security Layer)
 * verhindert, dass ESP beliebige Tabellen beschreiben kann
 */
$allowedTables = ["rounds", "buzzer_events"];

if (!in_array($table, $allowedTables)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Table not allowed"
    ]);
    exit;
}

try {

    if ($table === "rounds") {

        // ROUNDS TABLE
        $sql = "
            INSERT INTO rounds (event_type, created_at)
            VALUES (:wert, NOW())
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":wert" => $wert
        ]);
    }

    if ($table === "buzzer_events") {

        // BUZZER EVENTS TABLE
        $sql = "
            INSERT INTO buzzer_events (event_type, created_at)
            VALUES (:wert, NOW())
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":wert" => $wert
        ]);
    }

    echo json_encode([
        "status" => "success"
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed",
        "debug" => $e->getMessage()
    ]);
}