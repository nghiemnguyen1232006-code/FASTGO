<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
date_default_timezone_set('Asia/Ho_Chi_Minh');
checkRole('admin');

/* =========================
   USERS
========================= */
$total_users = $conn->query("
SELECT COUNT(*) 
FROM users
")->fetchColumn();

/* =========================
   ORDERS
========================= */
$total_orders = $conn->query("
SELECT COUNT(*) 
FROM orders
")->fetchColumn();

$pending_count = $conn->query("
SELECT COUNT(*) 
FROM orders 
WHERE status = 'pending'
")->fetchColumn();

/* =========================
   DRIVERS
========================= */
$total_drivers = $conn->query("
SELECT COUNT(*) 
FROM users 
WHERE role = 'driver'
")->fetchColumn();

/* =========================
   REVENUE (CHUẨN)
   → CHỈ LẤY TỪ PAYMENTS
========================= */
$total_revenue = $conn->query("
SELECT COALESCE(SUM(amount),0)
FROM payments
WHERE payment_status = 'paid'
")->fetchColumn();

/* =========================
   INCLUDE HEADER
========================= */
include "../includes/header-admin.php";
?>

<div class="page-header">
 <div>
 <h1>Dashboard <span>Admin</span></h1>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Tổng quan hệ thống FASTGO</p>
 </div>
 <span style="font-size:.8rem;color:var(--text-light);"><?= date('d/m/Y H:i') ?></span>
</div>

<div class="stats-grid">
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Người dùng</div>
 <div class="stat-value"><?= number_format($total_users) ?></div>
 <div class="stat-sub">Tổng tài khoản</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Đơn hàng</div>
 <div class="stat-value"><?= number_format($total_orders) ?></div>
 <div class="stat-sub">Tổng tất cả thời gian</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Tài xế</div>
 <div class="stat-value"><?= number_format($total_drivers) ?></div>
 <div class="stat-sub">Đang hoạt động</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Chờ xử lý</div>
 <div class="stat-value" style="color:#F59E0B"><?= number_format($pending_count) ?></div>
 <div class="stat-sub">Cần điều phối</div>
 </div>
 <div class="stat-card">
 <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
 <div class="stat-label">Doanh thu</div>
 <div class="stat-value" style="font-size:1.4rem;color:#10B981"><?= number_format($total_revenue) ?>đ</div>
 <div class="stat-sub">Đơn hoàn thành</div>
 </div>
</div>

<div class="nav-cards">
    <a href="users.php"   class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>Người dùng</a>
    <a href="drivers.php" class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="m16 8 4-1 3 3-1 2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span>Tài xế</a>
    <a href="staff.php"   class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Nhân viên</a>
    <a href="orders.php"  class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>Đơn hàng</a>
    <a href="reports.php" class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>Báo cáo</a>
    <a href="profile.php" class="nav-card"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Hồ sơ</a>
</div>
<?php include "../includes/footer.php"; ?>
