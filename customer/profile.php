<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('customer');

$id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);

 if (!$fullname || !$email) {
 $message = "Họ tên và email không được để trống.";
 } else {
 $stmt2 = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=? WHERE id=?");
 $stmt2->execute([$fullname, $email, $phone, $id]);
 $_SESSION['user']['fullname'] = $fullname;
 $success = "Cập nhật thông tin thành công!";
 // Refresh user data
 $stmt->execute([$id]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);
 }
}

// Get initials for avatar
$words = explode(' ', trim($user['fullname']));
$initials = mb_substr($words[0], 0, 1);
if (count($words) >1) $initials .= mb_substr(end($words), 0, 1);
$initials = mb_strtoupper($initials);

include "../includes/header.php";
?>

<style>
.profile-layout { display: grid; grid-template-columns: 260px 1fr; gap: 20px; }
.profile-sidebar { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); padding: 28px 20px; text-align: center; height: fit-content; }
.avatar-circle { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--role-color), var(--role-dark)); color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; box-shadow: 0 4px 16px var(--brand-glow); }
.profile-name { font-family: 'Space Grotesk', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text); }
.profile-role { display: inline-block; background: var(--role-light); color: var(--role-color); border-radius: 20px; padding: 3px 12px; font-size: .72rem; font-weight: 700; margin-top: 6px; text-transform: uppercase; letter-spacing: .8px; }
.profile-meta { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); text-align: left; }
.profile-meta-item { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: var(--text-muted); margin-bottom: 10px; }
.profile-meta-item span:first-child { font-size: 1rem; }
@media(max-width:720px){ .profile-layout { grid-template-columns: 1fr; } .profile-sidebar { text-align: center; } }
</style>

<div class="page-header">
 <div>
 <h2>Hồ sơ <span>cá nhân</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Quản lý thông tin tài khoản của bạn</p>
 </div>
</div>

<div class="profile-layout">
 <!-- Sidebar -->
 <div class="profile-sidebar">
 <div class="avatar-circle"><?= $initials ?></div>
 <div class="profile-name"><?= htmlspecialchars($user['fullname']) ?></div>
 <div class="profile-role"><?= $user['role'] ?></div>
 <div class="profile-meta">
 <div class="profile-meta-item">
 <span></span>
 <span style="word-break:break-all"><?= htmlspecialchars($user['email']) ?></span>
 </div>
 <?php if ($user['phone']): ?>
 <div class="profile-meta-item">
 <span></span>
 <span><?= htmlspecialchars($user['phone']) ?></span>
 </div>
 <?php endif; ?>
 <div class="profile-meta-item">
 <span></span>
 <span>Tham gia <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
 </div>
 </div>
 </div>

 <!-- Form -->
 <div>
 <div class="form-card" style="max-width:100%">
 <h2>Chỉnh sửa thông tin</h2>

 <?php if ($message): ?>
 <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
 <?php endif; ?>
 <?php if ($success): ?>
 <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-section-title">Thông tin cơ bản</div>
 <div class="form-group">
 <label>Họ và tên</label>
 <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required placeholder="Nhập họ và tên đầy đủ">
 </div>
 <div class="form-row">
 <div class="form-group">
 <label>Địa chỉ email</label>
 <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required placeholder="email@example.com">
 </div>
 <div class="form-group">
 <label>Số điện thoại</label>
 <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="VD: 0901 234 567">
 </div>
 </div>

 <div class="form-section-title" style="margin-top:20px">Tài khoản</div>
 <div class="form-group">
 <label>Vai trò</label>
 <input type="text" value="<?= ucfirst($user['role']) ?>" disabled style="opacity:.6;cursor:not-allowed">
 </div>

 <button type="submit" style="width:100%;padding:13px;font-size:.95rem;margin-top:8px">
 Lưu thay đổi
 </button>
 </form>
 </div>
 </div>
</div>

<?php include "../includes/footer.php"; ?>
