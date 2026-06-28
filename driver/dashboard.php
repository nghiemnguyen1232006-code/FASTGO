<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";
date_default_timezone_set('Asia/Ho_Chi_Minh');
checkRole('driver');

$driver_id = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'];

$total_assigned = $conn->prepare("SELECT COUNT(*) FROM orders WHERE driver_id=? AND status='assigned'");
$total_assigned->execute([$driver_id]);
$total_assigned = $total_assigned->fetchColumn();

$total_delivering = $conn->prepare("SELECT COUNT(*) FROM orders WHERE driver_id=? AND status IN ('picked_up','delivering')");
$total_delivering->execute([$driver_id]);
$total_delivering = $total_delivering->fetchColumn();

$total_completed = $conn->prepare("SELECT COUNT(*) FROM orders WHERE driver_id=? AND status='completed'");
$total_completed->execute([$driver_id]);
$total_completed = $total_completed->fetchColumn();

$recent_stmt = $conn->prepare("
 SELECT orders.*, users.fullname AS customer_name
 FROM orders
 JOIN users ON orders.customer_id = users.id
 WHERE orders.driver_id = ?
 ORDER BY orders.id DESC LIMIT 5
");
$recent_stmt->execute([$driver_id]);
$recent = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$hour = (int)date('H');
$greet = $hour < 12 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');

include "../includes/header-nhanvien.php";
?>

<div class="page-header">
 <div>
 <h1><?= $greet ?>, <span><?= htmlspecialchars(explode(' ', $fullname)[0]) ?>!</span></h1>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Các đơn hàng được phân công cho bạn</p>
 </div>
 <a href="my-orders.php" class="btn btn-primary btn-lg">Xem đơn của tôi</a>
</div>

<div class="stats-grid">
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Được phân công</div>
 <div class="stat-value" style="color:#3B82F6"><?= $total_assigned ?></div>
 <div class="stat-sub">Chờ lấy hàng</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="m16 8 4-1 3 3-1 2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
 <div class="stat-label">Đang giao</div>
 <div class="stat-value" style="color:#8B5CF6"><?= $total_delivering ?></div>
 <div class="stat-sub">Trên đường giao</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
 <div class="stat-label">Hoàn thành</div>
 <div class="stat-value" style="color:#10B981"><?= $total_completed ?></div>
 <div class="stat-sub">Đã giao thành công</div>
 </div>
</div>

<div class="nav-cards">
    <a href="my-orders.php" class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>Đơn của tôi</a>
    <a href="profile.php"   class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Hồ sơ</a>
</div>

<?php if (!empty($recent)): ?>
<div class="table-wrap">
 <div class="table-header">
 <h3>Đơn hàng gần đây</h3>
 <a href="my-orders.php" class="btn btn-ghost btn-sm">Xem tất cả →</a>
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
<script>

if(navigator.geolocation){

function sendLocation(){

navigator.geolocation.getCurrentPosition(function(position){

let lat = position.coords.latitude;
let lng = position.coords.longitude;

fetch("update-location.php",{

method:"POST",

headers:{
'Content-Type':'application/x-www-form-urlencoded'
},

body:
"latitude="+lat+
"&longitude="+lng

});

});

}

sendLocation();

setInterval(sendLocation,5000);

}

</script>
<?php include "../includes/footer.php"; ?>
