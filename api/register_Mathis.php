<?php

session_start();
header('Content-Type: application/json');

require_once '../system/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // hier wollen wir die variablen entpacken

    // entpacke die Daten

    $data = json_decode(file_get_contents("php://input"), true);


    $email = $data['email'];
    $password = $data['password'];

    //checken ob die email schon existiert
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([
        ":email" => $email
    ]);
    if ($stmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "Email already exists"
        ]);
        exit;
    }

    $hashedpassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :pass)");
    $insert->execute([
        ":email" => $email,
        ":pass" => $hashedpassword
    ]);

    echo json_encode([
        "status" => "success",
        "email" => $email,
    ]);

}
?>

