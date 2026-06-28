<?php
session_start();
require "../includes/db.php";

// Nếu đã đăng nhập thì redirect
if (isset($_SESSION['user'])) {
 $r = $_SESSION['user']['role'];
 if ($r === 'staff') { header("Location: ../staff/dashboard.php"); exit; }
 if ($r === 'driver') { header("Location: ../driver/dashboard.php"); exit; }
}

$message = "";
$msg_type = "error";

if (isset($_GET['error']) && $_GET['error'] === 'locked') {
 $message = "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 $email = trim($_POST['email']);
 $password = $_POST['password'];

 $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
 $stmt->execute([$email]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

 if ($user) {
 if (($user['status'] ?? 'active') === 'locked') {
 $message = "Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.";
 } elseif (password_verify($password, $user['password'])) {
 if ($user['role'] === 'customer') {
 $message = "Tài khoản khách hàng không được đăng nhập ở đây.";
 } elseif ($user['role'] === 'admin') {
 $message = "Tài khoản Admin vui lòng dùng <a href='login-admin.php' style='color:#7C3AED;text-decoration:underline'>cổng Admin</a>.";
 } else {
 /* staff hoặc driver */
 $_SESSION['user'] = $user;
 if ($user['role'] === 'staff') {
 header("Location: ../staff/dashboard.php");
 } else {
 header("Location: ../driver/dashboard.php");
 }
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
 <title>Đăng nhập Nhân viên – FASTGO</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
 <style>
 :root {
 --role-color: #0EA5E9;
 --role-dark: #0284C7;
 --role-light: #F0F9FF;
 --role-glow: rgba(14,165,233,.15);
 --brand-glow: rgba(14,165,233,.2);
 --shadow-brand: 0 8px 24px rgba(14,165,233,.25);
 }
 .show-pw-btn { background:none; border:none; box-shadow:none; padding:4px 8px; color:var(--text-muted); cursor:pointer; font-size:.8rem; font-weight:600; position:absolute; right:10px; top:50%; transform:translateY(-50%); }
 .show-pw-btn:hover { background:none; transform:translateY(-50%); box-shadow:none; color:var(--role-color); }
 .input-wrap { position:relative; }
 .input-wrap input { padding-right:56px; }
 .role-chips { display:flex; gap:8px; justify-content:center; margin-bottom:28px; flex-wrap:wrap; }
 .role-chip { background:var(--role-light); color:var(--role-color); border:1px solid #BAE6FD; border-radius:20px; padding:4px 14px; font-size:.72rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
 .other-portal { margin-top:22px; text-align:center; font-size:.82rem; color:var(--text-muted); }
 .other-portal a { color:#7C3AED; font-weight:700; }
 </style>
</head>
<body>
<div class="auth-wrapper">
<div class="auth-card" style="animation:fadeInUp .4s ease both">

 <div class="brand" style="color:var(--role-color)">FASTGO</div>
 <p class="subtitle">Cổng đăng nhập <strong>Nhóm Nhân viên</strong></p>

 <div class="role-chips">
 <span class="role-chip">Nhân viên</span>
 <span class="role-chip">Tài xế</span>
 </div>

 <?php if ($message): ?>
 <div class="alert alert-error"><?= $message ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-group">
 <label>Địa chỉ email</label>
 <input type="email" name="email" placeholder="email@fastgo.vn" required
 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Mật khẩu</label>
 <div class="input-wrap">
 <input type="password" name="password" id="pw" placeholder="Nhập mật khẩu" required>
 <button type="button" class="show-pw-btn" onclick="togglePw()">Hiện</button>
 </div>
 </div>
 <button type="submit" style="width:100%;padding:13px;font-size:.95rem;margin-top:4px;">
 Đăng nhập →
 </button>
 </form>

 <div class="other-portal">
 Quản trị viên? <a href="login-admin.php">Vào cổng Admin →</a>
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
