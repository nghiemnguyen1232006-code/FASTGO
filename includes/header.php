<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user     = $_SESSION['user'] ?? null;
$fullname = $user['fullname'] ?? '';
$words    = explode(' ', trim($fullname));
$initials = mb_strtoupper(mb_substr($words[0], 0, 1));
if (count($words) > 1) $initials .= mb_strtoupper(mb_substr(end($words), 0, 1));

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FASTGO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/FASTGO/assets/css/style.css">
<style>
/* ── Modern Topbar ── */
.topbar {
    background: rgba(255,255,255,.97);
    border-bottom: 1px solid #f0f0f0;
    padding: 0 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 58px;
    position: sticky;
    top: 0;
    z-index: 200;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 1px 0 #f0f0f0, 0 2px 8px rgba(0,0,0,.04);
}
.topbar-brand {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    color: #f97316;
    letter-spacing: -1px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.brand-badge {
    background: #f97316;
    color: #fff;
    font-size: .52rem;
    font-weight: 800;
    letter-spacing: 2px;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
}
.topbar-nav {
    display: flex;
    align-items: center;
    gap: 2px;
}
.topbar-nav a {
    color: #6b7280;
    font-weight: 500;
    font-size: .84rem;
    padding: 6px 12px;
    border-radius: 8px;
    transition: .15s;
    text-decoration: none;
}
.topbar-nav a:hover { background: #fff7ed; color: #f97316; opacity: 1; }
.topbar-nav a.active { color: #f97316; font-weight: 700; background: #fff7ed; }
.topbar-nav a.nav-logout {
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    font-weight: 600;
    margin-left: 8px;
}
.topbar-nav a.nav-logout:hover { background: #fee2e2; opacity: 1; }
.topbar-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Space Grotesk', sans-serif;
    margin-right: 6px;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #fed7aa;
    flex-shrink: 0;
}
.topbar-user {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #374151;
    font-size: .84rem;
    font-weight: 600;
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 8px;
}
.topbar-user:hover { background: #f9fafb; opacity: 1; }
</style>
</head>
<body>

<nav class="topbar">
    <a href="/FASTGO/customer/dashboard.php" class="topbar-brand">
        FASTGO
        <span class="brand-badge">DELIVERY</span>
    </a>
    <div class="topbar-nav">
        <a href="/FASTGO/customer/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="/FASTGO/customer/create-order.php" class="<?= $current === 'create-order.php' ? 'active' : '' ?>">Tạo đơn</a>
        <a href="/FASTGO/customer/my-orders.php" class="<?= $current === 'my-orders.php' ? 'active' : '' ?>">Đơn hàng</a>
        <a href="/FASTGO/customer/profile.php" class="topbar-user <?= $current === 'profile.php' ? 'active' : '' ?>">
            <span class="topbar-avatar"><?= $initials ?></span>
            <?= htmlspecialchars($words[count($words)-1] ?? '') ?>
        </a>
        <a href="/FASTGO/auth/logout.php" class="nav-logout">Đăng xuất</a>
    </div>
</nav>

<div class="container">