<?php
session_start();

require "../includes/db.php";
require "../includes/mailer.php";

$message = "";
$type    = "info";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

    $token = bin2hex(random_bytes(32));

    $expires_at = date(
        "Y-m-d H:i:s",
        strtotime("+1 hour")
    );

    $stmt2 = $conn->prepare("
        INSERT INTO password_resets(
            email,
            token,
            expires_at
        )
        VALUES(?,?,?)
    ");

    $stmt2->execute([
        $email,
        $token,
        $expires_at
    ]);

    $link =
    "http://localhost/FASTGO/auth/reset-password.php?token="
    . $token;

    sendMail(
        $email,
        "Đặt lại mật khẩu FASTGO",
        "
        <h2>FASTGO</h2>

        <p>Bạn vừa yêu cầu đặt lại mật khẩu.</p>

        <p>
            <a href='$link'
               style='background:#38bdf8;
                      color:white;
                      padding:12px 20px;
                      text-decoration:none;
                      border-radius:6px'>
                Đặt lại mật khẩu
            </a>
        </p>

        <p>
            Liên kết có hiệu lực trong 1 giờ.
        </p>
        "
    );

    $message =
    "Đã gửi email đặt lại mật khẩu.";

    $type = "success";
} else {
        $message = "Email không tồn tại trong hệ thống";
        $type    = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu – FASTGO</title>
    <link rel="stylesheet" href="/FASTGO/assets/css/customer.css">
    <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
<div class="auth-card">

    <div class="brand"> FASTGO</div>
    <p class="subtitle">Quên mật khẩu</p>

    <?php if ($message): ?>
    <div class="alert alert-<?= $type ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email đã đăng ký</label>
            <input type="email" name="email" placeholder="Nhập email của bạn" required>
        </div>
        <button type="submit" style="width:100%;padding:13px;font-size:1rem;">
            Gửi yêu cầu
        </button>
    </form>

    <div class="auth-links" style="justify-content:center;margin-top:16px;">
        <a href="login.php">← Quay lại đăng nhập</a>
    </div>

</div>
</div>
</body>
</html>
