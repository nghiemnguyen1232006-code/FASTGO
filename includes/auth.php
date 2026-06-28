<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: /FASTGO/auth/login-admin.php");
    exit;
}
?>
