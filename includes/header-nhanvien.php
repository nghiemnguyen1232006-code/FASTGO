<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user     = $_SESSION['user'] ?? null;
$role     = $user['role'] ?? 'staff';
$fullname = $user['fullname'] ?? '';
$initials = mb_strtoupper(mb_substr($fullname, 0, 1));
$current  = basename($_SERVER['PHP_SELF']);

function nav_svg_nv($name) {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'orders'    => '<svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        'dispatch'  => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>',
        'myorders'  => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'profile'   => '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    ];
    return $icons[$name] ?? '';
}

$is_driver = ($role === 'driver');

if ($is_driver) {
    $nav = [
        'dashboard.php'  => ['label' => 'Dashboard',    'icon' => 'dashboard', 'path' => '/FASTGO/driver/'],
        'my-orders.php'  => ['label' => 'Đơn của tôi',  'icon' => 'myorders',  'path' => '/FASTGO/driver/'],
        'profile.php'    => ['label' => 'Hồ sơ',        'icon' => 'profile',   'path' => '/FASTGO/driver/'],
    ];
    $role_label = 'Tài xế';
    $role_color_css = ':root{--role-color:#0EA5E9;--role-dark:#0369A1;--role-mid:#0284C7;--role-light:#F0F9FF;--role-border:#BAE6FD;--role-glow:rgba(14,165,233,.14);--shadow-brand:0 4px 14px rgba(14,165,233,.25);}';
} else {
    $nav = [
        'dashboard.php'  => ['label' => 'Dashboard',    'icon' => 'dashboard', 'path' => '/FASTGO/staff/'],
        'orders.php'     => ['label' => 'Đơn hàng',     'icon' => 'orders',    'path' => '/FASTGO/staff/'],
        'dispatch.php'   => ['label' => 'Điều phối',    'icon' => 'dispatch',  'path' => '/FASTGO/staff/'],
        'profile.php'    => ['label' => 'Hồ sơ',        'icon' => 'profile',   'path' => '/FASTGO/staff/'],
    ];
    $role_label = 'Nhân viên';
    $role_color_css = ':root{--role-color:#0EA5E9;--role-dark:#0369A1;--role-mid:#0284C7;--role-light:#F0F9FF;--role-border:#BAE6FD;--role-glow:rgba(14,165,233,.14);--shadow-brand:0 4px 14px rgba(14,165,233,.25);}';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FASTGO <?= $role_label ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/FASTGO/assets/css/style.css">
<style><?= $role_color_css ?></style>
</head>
<body class="has-sidebar">

<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="sidebar-logo">
            FASTGO
            <span class="sidebar-panel-badge"><?= $role_label ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $file => $item): ?>
        <a href="<?= $item['path'] . $file ?>"
           class="sidebar-link <?= $current === $file ? 'active' : '' ?>">
            <?= nav_svg_nv($item['icon']) ?>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $initials ?></div>
            <div class="sidebar-userinfo">
                <div class="sidebar-username"><?= htmlspecialchars($fullname) ?></div>
                <div class="sidebar-role"><?= $role_label ?></div>
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
