<?php
require "../includes/auth.php";
require "../includes/db.php";
require "../includes/role.php";

checkRole('admin');

$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$completed_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$cancelled_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$delivering = $conn->query("SELECT COUNT(*) FROM orders WHERE status='delivering'")->fetchColumn();
$total_revenue = $conn->query("SELECT COALESCE(SUM(price),0) FROM orders WHERE status='completed'")->fetchColumn();
$avg_order = $total_orders >0 ? $conn->query("SELECT COALESCE(AVG(price),0) FROM orders")->fetchColumn() : 0;

$completion_rate = $total_orders >0 ? round($completed_orders / $total_orders * 100, 1) : 0;

include "../includes/header-admin.php";
?>

<div class="page-header">
 <div>
 <h2>Báo cáo <span>thống kê</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Tổng quan hiệu suất hệ thống</p>
 </div>
 <span style="font-size:.8rem;color:var(--text-light);">Cập nhật: <?= date('d/m/Y H:i') ?></span>
</div>

<div class="stats-grid">
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Tổng đơn hàng</div>
 <div class="stat-value"><?= number_format($total_orders) ?></div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Hoàn thành</div>
 <div class="stat-value" style="color:#10B981"><?= number_format($completed_orders) ?></div>
 <div class="stat-sub"><?= $completion_rate ?>% tỷ lệ hoàn thành</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Đã hủy</div>
 <div class="stat-value" style="color:#EF4444"><?= number_format($cancelled_orders) ?></div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Chờ xử lý</div>
 <div class="stat-value" style="color:#F59E0B"><?= number_format($pending_orders) ?></div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Đang giao</div>
 <div class="stat-value" style="color:#3B82F6"><?= number_format($delivering) ?></div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Tổng doanh thu</div>
 <div class="stat-value" style="font-size:1.3rem;color:#10B981"><?= number_format($total_revenue) ?>đ</div>
 <div class="stat-sub">TB: <?= number_format($avg_order) ?>đ/đơn</div>
 </div>
</div>

<div class="table-wrap" style="max-width:480px">
<div class="table-header"><h3>Phân bổ trạng thái đơn</h3></div>
<table>
<thead><tr><th>Trạng thái</th><th>Số lượng</th><th>Tỷ lệ</th></tr></thead>
<tbody>
<?php
$statuses = [
 ['pending', 'Chờ xử lý', $pending_orders, '#F59E0B'],
 ['completed', ' Hoàn thành', $completed_orders, '#10B981'],
 ['cancelled', ' Đã hủy', $cancelled_orders, '#EF4444'],
 ['delivering', ' Đang giao', $delivering, '#3B82F6'],
];
foreach ($statuses as $s):
 $pct = $total_orders >0 ? round($s[2] / $total_orders * 100, 1) : 0;
?>
<tr>
 <td><?= $s[1] ?></td>
 <td><strong style="font-family:'Space Grotesk',sans-serif"><?= $s[2] ?></strong></td>
 <td>
 <div style="display:flex;align-items:center;gap:8px">
 <div style="flex:1;height:6px;background:var(--border);border-radius:99px;overflow:hidden">
 <div style="height:100%;width:<?= $pct ?>%;background:<?= $s[3] ?>;border-radius:99px"></div>
 </div>
 <span style="font-size:.8rem;color:var(--text-muted);min-width:36px"><?= $pct ?>%</span>
 </div>
 </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php include "../includes/footer.php"; ?>
