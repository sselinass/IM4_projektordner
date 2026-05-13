<?php
// api/get_family_members.php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";


$user_id = require_user_id();


$query = "
  SELECT
    ID,
    name,
    icon,
    buzzer
  FROM members
  WHERE id_users = ?
  AND is_active = 1
  ORDER BY ID ASC
";


$statement =
  mysqli_prepare($connection, $query);


mysqli_stmt_bind_param(
  $statement,
  "i",
  $user_id
);


mysqli_stmt_execute($statement);


$result =
  mysqli_stmt_get_result($statement);


$members = [];


while (
  $member =
    mysqli_fetch_assoc($result)
) {

  $members[] = $member;
}


header("Content-Type: application/json");

echo json_encode($members);