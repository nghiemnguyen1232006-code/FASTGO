<?php

require "../includes/db.php";

if (!isset($_GET['token'])) {
    die("Token không hợp lệ");
}

$token = $_GET['token'];

$stmt = $conn->prepare("
    SELECT *
    FROM password_resets
    WHERE token = ?
");

$stmt->execute([$token]);

$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    die("Token không tồn tại");
}

if (strtotime($reset['expires_at']) < time()) {
    die("Token đã hết hạn");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {

        $error = "Mật khẩu phải từ 8 ký tự";

    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {

        $error = "Mật khẩu phải có ít nhất 1 ký tự đặc biệt";

    } elseif ($password !== $confirm_password) {

        $error = "Mật khẩu nhập lại không khớp";

    } else {

        $new_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt2 = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE email = ?
        ");

        $stmt2->execute([
            $new_password,
            $reset['email']
        ]);

        $stmt3 = $conn->prepare("
            DELETE FROM password_resets
            WHERE email = ?
        ");

        $stmt3->execute([
            $reset['email']
        ]);

        header("Location: login.php?reset=success");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - FASTGO</title>

    <link rel="stylesheet" href="/FASTGO/assets/css/style.css">
    <link rel="stylesheet" href="/FASTGO/assets/css/customer.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="auth-card">

        <div class="brand">
            FASTGO
        </div>

        <p class="subtitle">
            Đặt lại mật khẩu tài khoản
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

       <form method="POST">

    <input
        type="password"
        name="password"
        placeholder="Mật khẩu mới"
        required
    >

    <br><br>

    <input
        type="password"
        name="confirm_password"
        placeholder="Nhập lại mật khẩu"
        required
    >

    <br><br>

    <button type="submit">
        Đổi mật khẩu
    </button>

</form>

        <div
            class="auth-links"
            style="justify-content:center;margin-top:20px;">

            <a href="login.php">
                ← Quay lại đăng nhập
            </a>

        </div>

    </div>

</div>

</body>
</html>