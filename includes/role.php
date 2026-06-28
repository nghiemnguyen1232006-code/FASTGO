<?php
/**
 * includes/role.php
 * checkRole()  – kiểm tra đúng role, redirect nếu sai
 * checkGroup() – kiểm tra thuộc nhóm (vd: nhóm nhân viên gồm staff + driver)
 */

function checkRole(string $required): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user = $_SESSION['user'] ?? null;
    $role = $user['role'] ?? '';

    if (!$user || $role !== $required) {
        // Admin có login riêng
        if ($required === 'admin') {
            header("Location: /FASTGO/auth/login-admin.php");
        } else {
            // staff và driver dùng chung cổng nhân viên
            header("Location: /FASTGO/auth/login-nhanvien.php");
        }
        exit;
    }
}

/**
 * Cho phép nhiều role cùng lúc (nhóm nhân viên = staff | driver)
 */
function checkGroup(array $allowed): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user = $_SESSION['user'] ?? null;
    $role = $user['role'] ?? '';

    if (!$user || !in_array($role, $allowed, true)) {
        if (in_array('admin', $allowed, true)) {
            header("Location: /FASTGO/auth/login-admin.php");
        } else {
            header("Location: /FASTGO/auth/login-nhanvien.php");
        }
        exit;
    }
}
