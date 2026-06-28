<?php
session_start();
require "../includes/db.php";
require "../includes/activity_log.php";
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 $email = trim($_POST['email']);
 $password = $_POST['password'];

 $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
 $stmt->execute([$email]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

 if ($user) {

    // Kiểm tra tài khoản bị khóa
    if (($user['status'] ?? 'active') === 'locked') {

        $message = "Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.";

    }
    elseif (!$user['email_verified']) {

        $message = "Vui lòng xác thực email trước khi đăng nhập.";

    }
    elseif (password_verify($password, $user['password'])) {
 // Chỉ cho phép customer đăng nhập ở trang này
 if ($user['role'] !== 'customer') {
 $message = "Tài khoản này không phải khách hàng. Vui lòng dùng trang quản lý.";
 } else {
 $_SESSION['user'] = $user;

logActivity(
    $conn,
    $user['id'],
    $user['fullname'],
    $user['role'],
    'LOGIN',
    'Khách hàng đăng nhập hệ thống'
);

header("Location: ../customer/dashboard.php");
exit;
 }
 } else {
 $message = "Sai mật khẩu. Vui lòng thử lại.";
 }
 } else {
 $message = "Email không tồn tại trong hệ thống.";
 }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Đăng nhập Khách hàng – FASTGO</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
 <style>
 .auth-meta { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 28px; }
 .auth-meta-item { display: flex; align-items: center; gap: 5px; font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }
 .show-pw-btn { background: none; border: none; box-shadow: none; padding: 4px 8px; color: var(--text-muted); cursor: pointer; font-size: 0.8rem; font-weight: 600; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }
 .show-pw-btn:hover { background: none; transform: translateY(-50%); box-shadow: none; color: var(--role-color); }
 .input-wrap { position: relative; }
 .input-wrap input { padding-right: 56px; }
 </style>
</head>
<body>
<div class="auth-wrapper">
<div class="auth-card" style="animation:fadeInUp .4s ease both">

 <div class="brand">FASTGO</div>
 <p class="subtitle">Cổng đăng nhập dành cho <strong>Khách hàng</strong></p>

 <div class="auth-meta">
 <div class="auth-meta-item"><span></span>Giao hàng nhanh</div>
 <div class="auth-meta-item" style="color:var(--border)">|</div>
 <div class="auth-meta-item"><span></span>Theo dõi realtime</div>
 <div class="auth-meta-item" style="color:var(--border)">|</div>
 <div class="auth-meta-item"><span></span>Bảo mật cao</div>
 </div>

 <?php if ($message): ?>
 <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-group">
 <label>Địa chỉ email</label>
 <input type="email" name="email" placeholder="email@example.com" required
 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Mật khẩu</label>
 <div class="input-wrap">
 <input type="password" name="password" id="pw" placeholder="Nhập mật khẩu" required>
 <button type="button" class="show-pw-btn" onclick="togglePw()">Hiện</button>
 </div>
 </div>
 <button type="submit" style="width:100%;padding:13px;font-size:0.95rem;margin-top:4px;">
 Đăng nhập →
 </button>
 </form>

 <div class="auth-links" style="margin-top:22px;">
 <a href="register.php">Đăng ký tài khoản</a>
 <a href="forgot-password.php">Quên mật khẩu?</a>
 </div>
<div style="margin-top:15px;text-align:center">
    <a href="../index.php">
        ← Quay lại trang chủ
    </a>
</div>


</div>
</div>
<script>
function togglePw() {
 const inp = document.getElementById('pw');
 const btn = inp.nextElementSibling;
 if (inp.type === 'password') { inp.type = 'text'; btn.textContent = 'Ẩn'; }
 else { inp.type = 'password'; btn.textContent = 'Hiện'; }
}
</script>
</body>
</html>