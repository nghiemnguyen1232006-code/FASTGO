<?php

require "../includes/auth.php";
require "../includes/db.php";

$driver=$_SESSION['user']['id'];

$lat=$_POST['lat'];
$lng=$_POST['lng'];

$stmt=$conn->prepare("
UPDATE users
SET latitude=?,
longitude=?
WHERE id=?
");

$stmt->execute([
$lat,
$lng,
$driver
]);