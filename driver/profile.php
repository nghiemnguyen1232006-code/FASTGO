<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('driver');

$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone    = trim($_POST['phone']);
    $conn->prepare("UPDATE users SET fullname=?, phone=? WHERE id=?")->execute([$fullname, $phone, $user_id]);
    $_SESSION['user']['fullname'] = $fullname;
    $msg = 'success';
    $stmt->execute([$user_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
}

$words    = explode(' ', trim($driver['fullname']));
$initials = mb_strtoupper(mb_substr($words[0], 0, 1));
if (count($words) > 1) $initials .= mb_strtoupper(mb_substr(end($words), 0, 1));

include "../includes/header-nhanvien.php";
?>

<style>
.profile-layout { display: grid; grid-template-columns: 260px 1fr; gap: 20px; }
.profile-sidebar { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); padding: 28px 20px; text-align: center; height: fit-content; }
.avatar-circle { width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, var(--role-color), var(--role-mid)); color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 1.7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
.profile-name  { font-family: 'Space Grotesk', sans-serif; font-size: .95rem; font-weight: 700; color: var(--text); }
.profile-role  { display: inline-block; background: var(--role-light); color: var(--role-color); border: 1px solid var(--role-border); border-radius: 20px; padding: 3px 12px; font-size: .68rem; font-weight: 700; margin-top: 6px; text-transform: uppercase; letter-spacing: .8px; }
.profile-meta  { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); text-align: left; }
.profile-meta-item { display: flex; gap: 8px; font-size: .8rem; color: var(--text-muted); margin-bottom: 10px; align-items: flex-start; }
.profile-meta-item svg { width:15px; height:15px; stroke:var(--text-light); fill:none; stroke-width:2; flex-shrink:0; margin-top:1px; }
.info-section { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; margin-bottom: 16px; }
.info-section-title { padding: 12px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2); font-size: .7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; }
.info-row { display: flex; padding: 11px 18px; border-bottom: 1px solid var(--border); }
.info-row:last-child { border-bottom: none; }
.info-key { font-size: .78rem; font-weight: 600; color: var(--text-muted); width: 150px; flex-shrink: 0; }
.info-val { font-size: .875rem; color: var(--text); font-weight: 500; }
@media(max-width:720px){ .profile-layout { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div>
        <h2>Hồ sơ <span>tài xế</span></h2>
        <p style="color:var(--text-muted);font-size:.84rem;margin:0;">Thông tin tài khoản của bạn</p>
    </div>
</div>

<div class="profile-layout">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="avatar-circle"><?= $initials ?></div>
        <div class="profile-name"><?= htmlspecialchars($driver['fullname']) ?></div>
        <div class="profile-role">Tài xế</div>
        <div class="profile-meta">
            <div class="profile-meta-item">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <span style="word-break:break-all"><?= htmlspecialchars($driver['email']) ?></span>
            </div>
            <?php if ($driver['phone']): ?>
            <div class="profile-meta-item">
                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span><?= htmlspecialchars($driver['phone']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($driver['vehicle_plate']): ?>
            <div class="profile-meta-item">
                <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="m16 8 4-1 3 3-1 2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span><?= htmlspecialchars($driver['vehicle_plate']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nội dung -->
    <div>
        <?php if ($msg === 'success'): ?>
        <div class="alert alert-success" style="margin-bottom:16px">Cập nhật thông tin thành công.</div>
        <?php endif; ?>

        <!-- Thông tin cá nhân (readonly) -->
        <div class="info-section">
            <div class="info-section-title">Thông tin cá nhân</div>
            <?php
            $fields = [
                'CCCD'            => $driver['cccd'],
                'Ngày sinh'       => $driver['birthdate'],
                'Giới tính'       => $driver['gender'],
                'Quốc tịch'       => $driver['nationality'],
                'Dân tộc'         => $driver['ethnicity'],
                'Tôn giáo'        => $driver['religion'],
                'Quê quán'        => $driver['hometown'],
                'Địa chỉ'         => $driver['address'],
                'Trình độ'        => $driver['education'],
                'Biển số xe'      => $driver['vehicle_plate'],
                'Bằng lái'        => $driver['license_type'],
            ];
            foreach ($fields as $k => $v): if (!$v) continue; ?>
            <div class="info-row">
                <span class="info-key"><?= $k ?></span>
                <span class="info-val"><?= htmlspecialchars($v) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Form chỉnh sửa thông tin cơ bản -->
        <div class="form-card" style="max-width:100%">
            <h2>Chỉnh sửa thông tin</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($driver['fullname']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($driver['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($driver['email']) ?>" disabled style="opacity:.6;cursor:not-allowed">
                </div>
                <button type="submit" style="width:100%;margin-top:4px">Lưu thay đổi</button>
            </form>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
