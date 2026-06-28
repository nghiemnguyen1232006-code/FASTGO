<?php
require "../includes/auth.php";
require "../includes/db.php";
require "../includes/role.php";

checkRole('admin');

$stmt = $conn->query("
 SELECT orders.*, users.fullname
 FROM orders
 JOIN users ON orders.customer_id = users.id
 ORDER BY orders.id DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-admin.php";
?>

<div class="page-header">
 <div>
 <h2>Danh sách <span>đơn hàng</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($orders) ?>đơn hàng tổng cộng</p>
 </div>
</div>

<div class="table-wrap">
<div class="table-header">
 <h3>Tất cả đơn hàng</h3>
</div>
<table>
<thead>
<tr>
 <th>Mã đơn</th>
 <th>Khách hàng</th>
 <th>Điểm lấy</th>
 <th>Điểm giao</th>
 <th>Giá tiền</th>
 <th>Trạng thái</th>
 <th>Ngày tạo</th>
  <th>Thao tác</th>
</tr>
</thead>
<tbody>
<?php if (empty($orders)): ?>
<tr><td colspan="8" class="empty-state"><div class="empty-icon"></div><p>Chưa có đơn hàng nào</p></td></tr>
<?php else: ?>
<?php foreach ($orders as $order): ?>
<tr>
 <td><strong>#<?= $order['id'] ?></strong></td>
 <td>
 <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($order['fullname']) ?></div>
 </td>
 <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($order['pickup_address']) ?></td>
 <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($order['delivery_address']) ?></td>
 <td><strong style="color:var(--text);font-family:'Space Grotesk',sans-serif"><?= number_format($order['price']) ?>đ</strong></td>
 <td><span class="badge badge-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
 <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
<td>
    <a href="order-detail.php?id=<?= $order['id'] ?>"
       class="btn-sm btn-view">
       Chi tiết
    </a>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<?php include "../includes/footer.php"; ?>
