<?php
session_start();
require "../includes/db.php";
require "../includes/activity_log.php";

if(isset($_SESSION['user'])){

    logActivity(
        $conn,
        $_SESSION['user']['id'],
        $_SESSION['user']['fullname'],
        $_SESSION['user']['role'],
        'LOGOUT',
        'Đăng xuất hệ thống'
    );
}
// Lưu role trước khi xóa session
$role = $_SESSION['user']['role'] ?? 'customer';

session_destroy();

// Redirect về đúng trang login theo role
if ($role === 'admin') {
    header("Location: login-admin.php");
} elseif ($role === 'staff' || $role === 'driver') {
    header("Location: login-nhanvien.php");
} else {
    header("Location: login.php");
}
exit;