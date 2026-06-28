<?php
require "../includes/auth.php";
require "../includes/db.php";
require "../includes/role.php";
require "../includes/functions.php";

checkRole('staff');

// Xử lý hủy đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
 $order_id = (int)$_POST['order_id'];
 $action = $_POST['action'];

 if ($action === 'cancel') {
 $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=?");
 $stmt->execute([$order_id]);
 } elseif ($action === 'complete') {
 $stmt = $conn->prepare("UPDATE orders SET status='completed' WHERE id=?");
 $stmt->execute([$order_id]);
 }
 header("Location: orders.php");
 exit;
}

// Lọc theo trạng thái
$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'assigned', 'picked_up', 'delivering', 'completed', 'cancelled'];
if (!in_array($filter, $allowed)) $filter = 'all';

if ($filter === 'all') {
 $stmt = $conn->query("
 SELECT orders.*, users.fullname AS customer_name
 FROM orders
 JOIN users ON orders.customer_id = users.id
 ORDER BY orders.id DESC
 ");
} else {
 $stmt = $conn->prepare("
 SELECT orders.*, users.fullname AS customer_name
 FROM orders
 JOIN users ON orders.customer_id = users.id
 WHERE orders.status=?
 ORDER BY orders.id DESC
 ");
 $stmt->execute([$filter]);
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-nhanvien.php";
?>

<style>
 .filter-bar {
 display: flex;
 gap: 6px;
 flex-wrap: wrap;
 margin-bottom: 20px;
 }

 .filter-btn {
 padding: 6px 14px;
 border-radius: 20px;
 font-size: .78rem;
 font-weight: 700;
 border: 1.5px solid var(--border);
 background: var(--surface);
 color: var(--text-muted);
 text-decoration: none;
 transition: var(--transition);
 }

 .filter-btn:hover {
 border-color: var(--role-color);
 color: var(--role-color);
 opacity: 1;
 }

 .filter-btn.active {
 background: var(--role-color);
 color: #fff;
 border-color: var(--role-color);
 }

 .action-btn {
 padding: 5px 12px;
 border-radius: 6px;
 font-size: .75rem;
 font-weight: 700;
 cursor: pointer;
 border: none;
 transition: var(--transition);
 }

 .btn-cancel {
 background: #FEF2F2;
 color: #EF4444;
 }

 .btn-cancel:hover {
 background: #FEE2E2;
 }

 .btn-complete {
 background: #F0FDF4;
 color: #16A34A;
 }

 .btn-complete:hover {
 background: #DCFCE7;
 }
</style>

<div class="page-header">
 <div>
 <h2>Danh sách <span>đơn hàng</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($orders) ?>đơn hàng<?= $filter !== 'all' ? " · lọc: $filter" : '' ?></p>
 </div>
</div>

<div class="filter-bar">
 <?php
 $filters = [
 'all' =>'Tất cả',
 'pending' =>'Chờ xử lý',
 'assigned' =>'Đã phân công',
 'delivering' =>'Đang giao',
 'completed' =>'Hoàn thành',
 'cancelled' =>'Đã hủy',
 ];
 foreach ($filters as $val =>$label):
 ?>
 <a href="?status=<?= $val ?>" class="filter-btn <?= $filter === $val ? 'active' : '' ?>"><?= $label ?></a>
 <?php endforeach; ?>
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
 <tr>
 <td colspan="8" class="empty-state">
 <p>Chưa có đơn hàng nào</p>
 </td>
 </tr>
 <?php else: ?>
 <?php foreach ($orders as $order): ?>
 <tr>
 <td><strong>#<?= $order['id'] ?></strong></td>
 <td style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($order['customer_name']) ?></td>
 <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($order['pickup_address']) ?></td>
 <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($order['delivery_address']) ?></td>
 <td><strong style="font-family:'Space Grotesk',sans-serif"><?= number_format($order['price']) ?>đ</strong></td>
 <td><span class="badge badge-<?= $order['status'] ?>"><?= getStatusText($order['status']) ?></span></td>
 <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
 <td>
 <div style="display:flex;gap:6px;flex-wrap:wrap">
 <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
 <?php if ($order['status'] !== 'completed'): ?>
 <form method="POST" onsubmit="return confirm('Xác nhận hoàn thành đơn #<?= $order['id'] ?>?')">
 <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
 <input type="hidden" name="action" value="complete">
 <button type="submit" class="action-btn btn-complete">Hoàn thành</button>
 </form>
 <?php endif; ?>
 <form method="POST" onsubmit="return confirm('Xác nhận hủy đơn #<?= $order['id'] ?>?')">
 <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
 <input type="hidden" name="action" value="cancel">
 <button type="submit" class="action-btn btn-cancel">Hủy đơn</button>
 </form>
 <?php else: ?>
 <span style="font-size:.75rem;color:var(--text-muted);font-style:italic">Không có</span>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php endif; ?>
 </tbody>
 </table>
</div>

<?php include "../includes/footer.php"; ?>