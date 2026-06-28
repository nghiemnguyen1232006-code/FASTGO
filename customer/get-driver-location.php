<?php
require "../includes/db.php";

$order_id = $_GET['order_id'] ?? 0;

$stmt = $conn->prepare("
SELECT
u.latitude,
u.longitude,
u.fullname
FROM orders o
JOIN users u
ON o.driver_id=u.id
WHERE o.id=?
");

$stmt->execute([$order_id]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));