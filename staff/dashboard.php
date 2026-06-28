<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";
date_default_timezone_set('Asia/Ho_Chi_Minh');
checkRole('staff');

$fullname = $_SESSION['user']['fullname'];

$total_pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$total_assigned = $conn->query("SELECT COUNT(*) FROM orders WHERE status='assigned'")->fetchColumn();
$total_delivering = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('picked_up','delivering')")->fetchColumn();
$total_completed = $conn->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();

$recent = $conn->query("
 SELECT orders.*, users.fullname AS customer_name
 FROM orders
 JOIN users ON orders.customer_id = users.id
 ORDER BY orders.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$hour = (int)date('H');
$greet = $hour < 12 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');

include "../includes/header-nhanvien.php";
?>

<div class="page-header">
 <div>
 <h1><?= $greet ?>, <span><?= htmlspecialchars(explode(' ', $fullname)[0]) ?>!</span></h1>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Quản lý và điều phối đơn hàng</p>
 </div>
 <a href="dispatch.php" class="btn btn-primary btn-lg">Điều phối đơn</a>
</div>

<div class="stats-grid">
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Chờ điều phối</div>
 <div class="stat-value" style="color:#F59E0B"><?= $total_pending ?></div>
 <div class="stat-sub">Cần gán tài xế</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Đã phân công</div>
 <div class="stat-value" style="color:#3B82F6"><?= $total_assigned ?></div>
 <div class="stat-sub">Đã có tài xế</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Đang giao</div>
 <div class="stat-value" style="color:#8B5CF6"><?= $total_delivering ?></div>
 <div class="stat-sub">Trên đường giao</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Hoàn thành</div>
 <div class="stat-value" style="color:#10B981"><?= $total_completed ?></div>
 <div class="stat-sub">Giao thành công</div>
 </div>
</div>

<div class="nav-cards">
    <a href="orders.php"   class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>Đơn hàng</a>
    <a href="dispatch.php" class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg></span>Điều phối</a>
    <a href="profile.php"  class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Hồ sơ</a>
</div>

<?php if (!empty($recent)): ?>
<div class="table-wrap">
 <div class="table-header">
 <h3>Đơn hàng gần đây</h3>
 <a href="orders.php" class="btn btn-ghost btn-sm">Xem tất cả →</a>
 </div>
 <table>
 <thead>
 <tr>
 <th>Mã đơn</th><th>Khách hàng</th><th>Điểm giao</th>
 <th>Giá tiền</th><th>Trạng thái</th><th>Ngày tạo</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($recent as $o): ?>
 <tr>
 <td><strong>#<?= $o['id'] ?></strong></td>
 <td><?= htmlspecialchars($o['customer_name']) ?></td>
 <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['delivery_address']) ?></td>
 <td><strong style="font-family:'Space Grotesk',sans-serif"><?= number_format($o['price']) ?>đ</strong></td>
 <td><span class="badge badge-<?= $o['status'] ?>"><?= getStatusText($o['status']) ?></span></td>
 <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
</div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
