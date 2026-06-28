<?php

require "../includes/auth.php";
require "../includes/db.php";

$user_id = $_SESSION['user']['id'];
$order_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    AND customer_id = ?
");

$stmt->execute([$order_id, $user_id]);

$order = $stmt->fetch();

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

if (
    $order['status'] != 'pending'
    || !empty($order['driver_id'])
) {
    die("Đơn hàng đã được phân công vận chuyển.");
}

$stmt = $conn->prepare("
    UPDATE orders
    SET status='cancelled'
    WHERE id=?
");

$stmt->execute([$order_id]);

header("Location: my-orders.php");
exit;