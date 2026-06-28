<?php
session_start();
require "../includes/db.php";
require "../includes/mailer.php";
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $password = $_POST['password'];
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $message = "Số điện thoại không hợp lệ (phải đúng 10 số)";
}
 elseif (strlen($password) < 8) {
 $message = "Mật khẩu phải từ 8 ký tự";
 } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
 $message = "Mật khẩu phải có ký tự đặc biệt";
 } else {
$otp = rand(100000,999999);

$expire = date(
    'Y-m-d H:i:s',
    strtotime('+10 minutes')
);

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);
$check = $conn->prepare(
    "SELECT id FROM users WHERE email=?"
);

$check->execute([$email]);

if($check->fetch()){

    $message =
    "Email đã tồn tại trong hệ thống.";

}
else{

    // INSERT USER Ở ĐÂY
$stmt = $conn->prepare("
INSERT INTO users(
    fullname,
    email,
    phone,
    password,
    role,
    otp_code,
    otp_expired_at,
    email_verified
)
VALUES(?,?,?,?,?,?,?,0)
");

$stmt->execute([
    $fullname,
    $email,
    $phone,
    $passwordHash,
    'customer',
    $otp,
    $expire
]);
sendMail(
    $email,
    'Mã xác thực FASTGO',
    "
    <h2>Xác thực tài khoản FASTGO</h2>
    <p>Mã OTP của bạn là:</p>
    <h1>$otp</h1>
    <p>Mã có hiệu lực trong 10 phút.</p>
    "
);

$_SESSION['verify_email'] = $email;

header("Location: verify-email.php");
exit;
 header("Location: login.php");
 exit;
 }
 }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Đăng ký – FASTGO</title>
 <link rel="stylesheet" href="/FASTGO/assets/css/customer.css">
 <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
<div class="auth-card">

 <div class="brand">FASTGO</div>
 <p class="subtitle">Tạo tài khoản mới</p>

 <?php if ($message): ?>
 <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-group">
 <label>Họ tên</label>
 <input type="text" name="fullname" placeholder="Nhập họ tên" required>
 </div>
 <div class="form-group">
 <label>Email</label>
 <input type="email" name="email" placeholder="Nhập email" required>
 </div>
 <div class="form-group">
 <label>Số điện thoại</label>
 <input type="text" name="phone" placeholder="Nhập số điện thoại" required>
 </div>
 <div class="form-group">
 <label>Mật khẩu</label>
 <input type="password" name="password" placeholder="Tối thiểu 8 ký tự + ký tự đặc biệt" required>
 </div>
 <button type="submit" style="width:100%;padding:13px;font-size:1rem;">
 Đăng ký
 </button>
 </form>

 <div class="auth-links" style="justify-content:center;">
 <a href="login.php">Đã có tài khoản? Đăng nhập</a>
 </div>

</div>
</div>
</body>
</html>
