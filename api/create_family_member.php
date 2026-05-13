<?php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";


$user_id = require_user_id();


$data =
    json_decode(
        file_get_contents("php://input"),
        true
    );


$name = trim($data["name"] ?? "");

$icon = trim($data["icon"] ?? "");

$buzzer = trim($data["buzzer"] ?? "");


if (
    $name === "" ||
    $icon === "" ||
    $buzzer === ""
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Missing fields."
    ]);

    exit;
}


$allowed_colors = [
    "blue",
    "pink",
    "green",
    "orange"
];


if (
    !in_array(
        $buzzer,
        $allowed_colors,
        true
    )
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid color."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| max 4 members
|--------------------------------------------------------------------------
*/

$count_query = "
  SELECT COUNT(*) AS total
  FROM members
  WHERE id_users = ?
  AND is_active = 1
";


$count_statement =
    mysqli_prepare(
        $connection,
        $count_query
    );


mysqli_stmt_bind_param(
    $count_statement,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $count_statement
);


$count_result =
    mysqli_stmt_get_result(
        $count_statement
    );


$total =
    mysqli_fetch_assoc(
        $count_result
    )["total"];


if ((int)$total >= 4) {

    echo json_encode([
        "success" => false,
        "message" => "Maximum members reached."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| prevent duplicate colors
|--------------------------------------------------------------------------
*/

$color_query = "
  SELECT ID
  FROM members
  WHERE id_users = ?
  AND buzzer = ?
  AND is_active = 1
";


$color_statement =
    mysqli_prepare(
        $connection,
        $color_query
    );


mysqli_stmt_bind_param(
    $color_statement,
    "is",
    $user_id,
    $buzzer
);


mysqli_stmt_execute(
    $color_statement
);


$color_result =
    mysqli_stmt_get_result(
        $color_statement
    );


if (
    mysqli_num_rows($color_result) > 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Color already assigned."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| insert
|--------------------------------------------------------------------------
*/

$insert_query = "
  INSERT INTO members (
    name,
    icon,
    buzzer,
    id_users,
    is_active
  )
  VALUES (?, ?, ?, ?, 1)
";


$insert_statement =
    mysqli_prepare(
        $connection,
        $insert_query
    );


mysqli_stmt_bind_param(
    $insert_statement,
    "sssi",
    $name,
    $icon,
    $buzzer,
    $user_id
);


$success =
    mysqli_stmt_execute(
        $insert_statement
    );


header("Content-Type: application/json");


echo json_encode([
    "success" => $success
]);
