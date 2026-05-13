<?php
/*******
 * Kapitel 12: Website2DB > Schritt 2: Website -> DB
 * load.php
 * Daten als JSON-String vom ESP empfangen und in die Datenbank einfügen
 ******/

require_once("../system/config.php");

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$buzzer_events = $input["buzzer_events"];
$timestamp = $input["timestamp"];
$id_users = $input["id_users"];

$sql = "INSERT INTO NEU_buzzer_events (buzzer_events, timestamp, id_users) VALUES (?, ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $buzzer_events,
    $timestamp,
    $id_users
]);

?>