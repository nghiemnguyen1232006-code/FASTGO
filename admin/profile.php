<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('admin');

$id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $stmt2 = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=? WHERE id=?");
 $stmt2->execute([$fullname, $email, $phone, $id]);
 $_SESSION['user']['fullname'] = $fullname;
 header("Location: profile.php");
 exit;
}

include "../includes/header-admin.php";
?>

<div class="page-header"><h2>Thông tin cá nhân</h2></div>

<div class="form-card">
<form method="POST">
 <div class="form-group">
 <label>Họ tên</label>
 <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
 </div>
 <div class="form-group">
 <label>Email</label>
 <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
 </div>
 <div class="form-group">
 <label>Số điện thoại</label>
 <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
 </div>
 <div class="form-group">
 <label>Vai trò</label>
 <input type="text" value="<?= $user['role'] ?>" disabled>
 </div>
 <button type="submit" style="width:100%">Cập nhật thông tin</button>
</form>
</div>

<?php include "../includes/footer.php"; ?>
