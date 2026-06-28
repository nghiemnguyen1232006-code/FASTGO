<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user     = $_SESSION['user'] ?? null;
$fullname = $user['fullname'] ?? 'Admin';
$initials = mb_strtoupper(mb_substr($fullname, 0, 1));
$current  = basename($_SERVER['PHP_SELF']);

function nav_svg($name) {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'users'     => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'driver'    => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>',
        'staff'     => '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'orders'    => '<svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        'reports'   => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'profile'   => '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    ];
    return $icons[$name] ?? '';
}

$nav = [
    'dashboard.php' => ['label' => 'Dashboard',      'icon' => 'dashboard'],
    'users.php'     => ['label' => 'Người dùng',     'icon' => 'users'],
    'drivers.php'   => ['label' => 'Tài xế',         'icon' => 'driver'],
    'staff.php'     => ['label' => 'Nhân viên',      'icon' => 'staff'],
    'orders.php'    => ['label' => 'Đơn hàng',       'icon' => 'orders'],
    'payments.php' => ['label' => 'Thanh toán', 'icon' => 'reports'],
    'reports.php'   => ['label' => 'Báo cáo',        'icon' => 'reports'],
    'activity-logs.php' => ['label' => 'Nhật ký hoạt động', 'icon' => 'reports'],
    'profile.php'   => ['label' => 'Hồ sơ',          'icon' => 'profile'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FASTGO Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/FASTGO/assets/css/style.css">
<style>:root{--role-color:#7C3AED;--role-dark:#5B21B6;--role-mid:#6D28D9;--role-light:#F5F3FF;--role-border:#DDD6FE;--role-glow:rgba(124,58,237,.14);--shadow-brand:0 4px 14px rgba(124,58,237,.25);}</style>
</head>
<body class="has-sidebar">

<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="/FASTGO/admin/dashboard.php" class="sidebar-logo">
            FASTGO
            <span class="sidebar-panel-badge">Admin</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $file => $item): ?>
        <a href="/FASTGO/admin/<?= $file ?>"
           class="sidebar-link <?= $current === $file ? 'active' : '' ?>">
            <?= nav_svg($item['icon']) ?>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $initials ?></div>
            <div class="sidebar-userinfo">
                <div class="sidebar-username"><?= htmlspecialchars($fullname) ?></div>
                <div class="sidebar-role">Admin</div>
            </div>
        </div>
    </div>
</aside>

<div class="sidebar-content">
    <header class="topbar-slim">
        <a href="/FASTGO/auth/logout.php" class="logout-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Đăng xuất
        </a>
    </header>

    <div class="container">
