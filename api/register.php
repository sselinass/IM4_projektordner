<?php

session_start();
    header('Content-Type: application/json');

    require_once '../system/config.php';

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
        // hier wollen wir die Variablen entpacken

        //entpacke die Daten
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'];
        $password = $data['password'];

        // in 2 Blöcken irgendwas mit Passwort sicherhiet stuff - so machen damit safe sonst kann jemand weitere Variablen in die Liste schreiben
        $insert =$pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :pass)");
        $insert->execute([
            ":email" => $email,
            ":pass" => $password
        ]);

        //an JS zurückschicken
        echo json_encode([
            "status" => "success", 
            "email" => $email
        ]);

    }
   
?>