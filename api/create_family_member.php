<?php
// api/create_family_member.php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";

$user_id = require_user_id();

$body = read_json_body();

$name = trim($body["name"] ?? "");
$icon = trim($body["icon"] ?? "");
$buzzer = trim($body["buzzer"] ?? "");

if (
  $name === "" ||
  $icon === "" ||
  $buzzer === ""
) {
  json_response([
    "success" => false,
    "message" => "Missing fields."
  ], 400);
}

$allowed_colors = [
  "blue",
  "pink",
  "green",
  "orange"
];

if (!in_array($buzzer, $allowed_colors, true)) {
  json_response([
    "success" => false,
    "message" => "Invalid color."
  ], 400);
}

$count_query = "
  SELECT COUNT(*) AS total
  FROM members
  WHERE id_users = ?
  AND is_active = 1
";

$count_statement = mysqli_prepare($db, $count_query);

if (!$count_statement) {
  json_response([
    "success" => false,
    "message" => "Prepare count failed",
    "error" => mysqli_error($db)
  ], 500);
}

mysqli_stmt_bind_param(
  $count_statement,
  "i",
  $user_id
);

mysqli_stmt_execute($count_statement);

$count_result = mysqli_stmt_get_result($count_statement);

$total = mysqli_fetch_assoc($count_result)["total"];

if ((int) $total >= 4) {
  json_response([
    "success" => false,
    "message" => "Maximum members reached."
  ], 400);
}

$color_query = "
  SELECT ID
  FROM members
  WHERE id_users = ?
  AND buzzer = ?
  AND is_active = 1
";

$color_statement = mysqli_prepare($db, $color_query);

if (!$color_statement) {
  json_response([
    "success" => false,
    "message" => "Prepare color failed",
    "error" => mysqli_error($db)
  ], 500);
}

mysqli_stmt_bind_param(
  $color_statement,
  "is",
  $user_id,
  $buzzer
);

mysqli_stmt_execute($color_statement);

$color_result = mysqli_stmt_get_result($color_statement);

if (mysqli_num_rows($color_result) > 0) {
  json_response([
    "success" => false,
    "message" => "Color already assigned."
  ], 400);
}

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

$insert_statement = mysqli_prepare($db, $insert_query);

if (!$insert_statement) {
  json_response([
    "success" => false,
    "message" => "Prepare insert failed",
    "error" => mysqli_error($db)
  ], 500);
}

mysqli_stmt_bind_param(
  $insert_statement,
  "sssi",
  $name,
  $icon,
  $buzzer,
  $user_id
);

$success = mysqli_stmt_execute($insert_statement);

if (!$success) {
  json_response([
    "success" => false,
    "message" => "Insert failed",
    "error" => mysqli_error($db)
  ], 500);
}

json_response([
  "success" => true,
  "message" => "Family member created."
]);