<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('driver');

$driver_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
 SELECT orders.*, users.fullname AS customer_name
 FROM orders
 JOIN users ON orders.customer_id = users.id
 WHERE driver_id=?
 ORDER BY orders.id DESC
");
$stmt->execute([$driver_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-nhanvien.php";
?>

<div class="page-header"><h2>Đơn hàng của tôi</h2></div>

<div class="table-wrap">
<table>
<thead>
<tr>
 <th>Mã đơn</th>
 <th>Khách hàng</th>
 <th>Điểm lấy</th>
 <th>Điểm giao</th>
 <th>Trạng thái</th>
 <th>Hành động</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $order): ?>
<tr>
 <td><strong>#<?= $order['id'] ?></strong></td>
 <td><?= htmlspecialchars($order['customer_name']) ?></td>
 <td><?= htmlspecialchars($order['pickup_address']) ?></td>
 <td><?= htmlspecialchars($order['delivery_address']) ?></td>
 <td><span class="badge badge-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
 <td>
 <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Chi tiết</a>
 <a href="update-status.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">Cập nhật</a>
 </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php include "../includes/footer.php"; ?>
