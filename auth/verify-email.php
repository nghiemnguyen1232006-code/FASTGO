<?php
session_start();
require "../includes/db.php";

if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit;
}

$email = $_SESSION['verify_email'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $otp = trim($_POST['otp']);

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE email=?
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $message = "Không tìm thấy tài khoản.";

    } elseif ($user['otp_code'] != $otp) {

        $message = "Mã OTP không chính xác.";

    } elseif (strtotime($user['otp_expired_at']) < time()) {

        $message = "Mã OTP đã hết hạn.";

    } else {

        $update = $conn->prepare("
            UPDATE users
            SET
                email_verified = 1,
                otp_code = NULL,
                otp_expired_at = NULL
            WHERE id = ?
        ");

        $update->execute([$user['id']]);

        unset($_SESSION['verify_email']);

        header("Location: login.php?verified=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Xác thực Email - FASTGO</title>

<link rel="stylesheet" href="/FASTGO/assets/css/style.css">

<style>
:root{
    --role-color:#7DD3FC;
    --role-mid:#38BDF8;
    --role-dark:#0EA5E9;
    --role-light:#F0F9FF;
    --role-border:#BAE6FD;
    --role-glow:rgba(56,189,248,.2);
}
</style>

</head>
<body>

<div class="auth-wrapper">

<div class="auth-card">

<div class="brand">FASTGO</div>

<p class="subtitle">
Nhập mã OTP đã gửi tới email của bạn
</p>

<?php if($message): ?>
<div class="alert alert-error">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>Mã OTP</label>

<input
type="text"
name="otp"
maxlength="6"
placeholder="Nhập mã OTP"
required>

</div>

<button
type="submit"
style="width:100%;padding:13px;">
Xác thực tài khoản
</button>

</form>

</div>

</div>

</body>
</html>