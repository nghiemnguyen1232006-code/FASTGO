<?php
session_start();
require "../includes/db.php";
require "../includes/activity_log.php";
// Nếu đã đăng nhập admin thì redirect luôn
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
 header("Location: ../admin/dashboard.php");
 exit;
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
 if ($user['role'] !== 'admin') {
 $message = "Tài khoản này không phải Admin. Nhân viên / Tài xế vui lòng dùng <a href='login-nhanvien.php' style='color:#6366F1;text-decoration:underline'>cổng nhân viên</a>.";
 } else {

 $_SESSION['user'] = $user;

 // Ghi nhật ký đăng nhập
 logActivity(
    $conn,
    $user['id'],
    $user['fullname'],
    $user['role'],
    'LOGIN',
    'Đăng nhập hệ thống'
);

 header("Location: ../admin/dashboard.php");
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
 <title>Đăng nhập Admin – FASTGO</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
 <style>
 :root {
 --role-color: #7C3AED;
 --role-dark: #6D28D9;
 --role-light: #F5F3FF;
 --role-glow: rgba(124,58,237,.15);
 --brand-glow: rgba(124,58,237,.2);
 --shadow-brand: 0 8px 24px rgba(124,58,237,.25);
 }
 .show-pw-btn { background:none; border:none; box-shadow:none; padding:4px 8px; color:var(--text-muted); cursor:pointer; font-size:.8rem; font-weight:600; position:absolute; right:10px; top:50%; transform:translateY(-50%); }
 .show-pw-btn:hover { background:none; transform:translateY(-50%); box-shadow:none; color:var(--role-color); }
 .input-wrap { position:relative; }
 .input-wrap input { padding-right:56px; }
 .portal-chip { display:inline-flex; align-items:center; gap:6px; background:var(--role-light); color:var(--role-color); border:1px solid #DDD6FE; border-radius:20px; padding:4px 14px; font-size:.72rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; margin-bottom:28px; }
 .other-portal { margin-top:22px; text-align:center; font-size:.82rem; color:var(--text-muted); }
 .other-portal a { color:var(--role-color); font-weight:700; }
 </style>
</head>
<body>
<div class="auth-wrapper">
<div class="auth-card" style="animation:fadeInUp .4s ease both">

 <div class="brand" style="color:var(--role-color)">FASTGO</div>
 <p class="subtitle">Cổng đăng nhập <strong>Quản trị viên</strong></p>

 <div style="text-align:center"><span class="portal-chip">Admin Portal</span></div>

 <?php if ($message): ?>
 <div class="alert alert-error"><?= $message ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-group">
 <label>Địa chỉ email</label>
 <input type="email" name="email" placeholder="admin@fastgo.vn" required
 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Mật khẩu</label>
 <div class="input-wrap">
 <input type="password" name="password" id="pw" placeholder="Nhập mật khẩu" required>
 <button type="button" class="show-pw-btn" onclick="togglePw()">Hiện</button>
 </div>
 </div>
 <button type="submit" style="width:100%;padding:13px;font-size:.95rem;margin-top:4px;background:linear-gradient(135deg,#7C3AED,#6D28D9);">
 Đăng nhập →
 </button>
 </form>

 <div class="other-portal">
 Nhân viên / Tài xế? <a href="login-nhanvien.php">Vào cổng nhân viên →</a>
 </div>
<div style="margin-top:15px;text-align:center">
    <a href="../index.php"
       style="
       color:#64748B;
       text-decoration:none;
       font-size:14px;
       font-weight:600;">
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
