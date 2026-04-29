<?php
    session_start();
    header("content-type: application/json");

    require_once "../system/config.php";
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        // hier wollen wr die variabeln entpacken

        // entpacke die Daten
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data ['email'];
        $password = $data['password'];
        
        //an JS zurückschicken
        echo json_encode([
            "status" => "success",
            "email" => $email,
        ]);
    }
?>