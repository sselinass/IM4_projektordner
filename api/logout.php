<?php
// logout.php - API-Endpunkt zum Abmelden eines Benutzers, einschließlich Ende der Sitzung und Rückgabe einer JSON-Antwort
session_start();
$_SESSION = [];
session_destroy();

// Return a success response instead of redirecting
header('Content-Type: application/json');
echo json_encode(["status" => "success"]);
exit;
?>