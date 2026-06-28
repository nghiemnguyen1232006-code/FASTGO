<?php

require "../includes/auth.php";
require "../includes/db.php";
require "../includes/role.php";

checkRole('admin');

$order_id = (int)$_GET['order_id'];

$stmt = $conn->prepare("
SELECT
users.latitude,
users.longitude
FROM orders
JOIN users
ON orders.driver_id = users.id
WHERE orders.id=?
");

$stmt->execute([$order_id]);

echo json_encode(
$stmt->fetch(PDO::FETCH_ASSOC)
);