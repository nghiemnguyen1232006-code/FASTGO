<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";

checkRole('admin');

$msg = '';
$msg_type = '';
$edit_user = null;

function validatePhone($phone)
{
    return preg_match('/^0\d{9}$/', $phone);
}

function validatePassword($password)
{
    // Ít nhất 8 ký tự
    // Có chữ thường, chữ hoa, số và ký tự đặc biệt
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
}

/* ─── XỬ LÝ ACTION ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $action = $_POST['action'] ?? '';
 $uid = (int)($_POST['user_id'] ?? 0);

 if ($action === 'add') {
 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $password = $_POST['password'];
 $vehicle_plate = trim($_POST['vehicle_plate']);
 $license_type = trim($_POST['license_type']);
 $cccd = trim($_POST['cccd']);

 if (!validatePhone($phone)) {
    $msg = "Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0.";
    $msg_type = 'error';

} elseif (!validatePassword($password)) {
    $msg = "Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.";

    $msg_type = 'error';

} else {
 $chk = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ?
       OR cccd = ?
       OR vehicle_plate = ?
");
$chk->execute([$email, $cccd, $vehicle_plate]);
$user = $chk->fetch(PDO::FETCH_ASSOC);

if ($user) {

    // kiểm tra riêng từng trường
    $chk = $conn->prepare("
        SELECT
            SUM(email = ?) AS email_exist,
            SUM(cccd = ?) AS cccd_exist,
            SUM(vehicle_plate = ?) AS plate_exist
        FROM users
    ");
    $chk->execute([$email, $cccd, $vehicle_plate]);
    $exist = $chk->fetch(PDO::FETCH_ASSOC);

    if ($exist['email_exist']) {
        $msg = "Email đã tồn tại trong hệ thống.";
    } elseif ($exist['cccd_exist']) {
        $msg = "Số CCCD đã tồn tại trong hệ thống.";
    } elseif ($exist['plate_exist']) {
        $msg = "Biển số xe đã tồn tại trong hệ thống.";
    }

    $msg_type = "error";

} else {
 $hash = password_hash($password, PASSWORD_DEFAULT);
 $stmt = $conn->prepare("INSERT INTO users (fullname,email,phone,password,role,status,vehicle_plate,license_type,cccd) VALUES (?,?,?,?,'driver','active',?,?,?)");
 $stmt->execute([$fullname, $email, $phone, $hash, $vehicle_plate, $license_type, $cccd]);
 $msg = "Đã thêm tài xế <strong>$fullname</strong> thành công."; $msg_type = 'success';
 }
}
 } elseif ($action === 'edit') {
 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $password = trim($_POST['password']);
 $vehicle_plate = trim($_POST['vehicle_plate']);
 $license_type = trim($_POST['license_type']);
 $cccd = trim($_POST['cccd']);

 if (!validatePhone($phone)) {
    $msg = "Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0.";
    $msg_type = 'error';

} elseif ($password !== '' && !validatePassword($password)) {
    $msg = "Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.";
    $msg_type = 'error';

}elseif (!preg_match('/^\d{12}$/', $cccd)) {
    $msg = "CCCD phải gồm đúng 12 chữ số.";
    $msg_type = 'error';
}elseif (!preg_match('/^[0-9]{2}[A-Z][0-9]?-[0-9]{4,5}$/', strtoupper($vehicle_plate))) {
    $msg = "Biển số xe không đúng định dạng.";
}else {
 $chk = $conn->prepare("
SELECT
    SUM(email = ?) AS email_exist,
    SUM(cccd = ?) AS cccd_exist,
    SUM(vehicle_plate = ?) AS plate_exist
FROM users
WHERE id != ?
");

$chk->execute([
    $email,
    $cccd,
    $vehicle_plate,
    $uid
]);

$exist = $chk->fetch(PDO::FETCH_ASSOC);

if ($exist['email_exist']) {

    $msg = "Email đã được dùng bởi tài khoản khác.";
    $msg_type = "error";

} elseif ($exist['cccd_exist']) {

    $msg = "Số CCCD đã được dùng bởi tài khoản khác.";
    $msg_type = "error";

} elseif ($exist['plate_exist']) {

    $msg = "Biển số xe đã được dùng bởi tài khoản khác.";
    $msg_type = "error";

} else {
 if ($password !== '') {
 $hash = password_hash($password, PASSWORD_DEFAULT);
 $stmt = $conn->prepare("UPDATE users SET fullname=?,email=?,phone=?,password=?,vehicle_plate=?,license_type=?,cccd=? WHERE id=? AND role='driver'");
 $stmt->execute([$fullname, $email, $phone, $hash, $vehicle_plate, $license_type, $cccd, $uid]);
 } else {
 $stmt = $conn->prepare("UPDATE users SET fullname=?,email=?,phone=?,vehicle_plate=?,license_type=?,cccd=? WHERE id=? AND role='driver'");
 $stmt->execute([$fullname, $email, $phone, $vehicle_plate, $license_type, $cccd, $uid]);
 }
 $msg = "Đã cập nhật thông tin tài xế."; $msg_type = 'success';
 }
}
 } elseif ($action === 'lock') {
 $conn->prepare("UPDATE users SET status='locked' WHERE id=? AND role='driver'")->execute([$uid]);
 $msg = "Đã khóa tài khoản tài xế."; $msg_type = 'warning';

 } elseif ($action === 'unlock') {
 $conn->prepare("UPDATE users SET status='active' WHERE id=? AND role='driver'")->execute([$uid]);
 $msg = "Đã mở khóa tài khoản."; $msg_type = 'success';

 } elseif ($action === 'delete') {
 $conn->prepare("DELETE FROM users WHERE id=? AND role='driver'")->execute([$uid]);
 $msg = "Đã xóa tài xế."; $msg_type = 'success';

 } elseif ($action === 'reset_password') {
 $hash = password_hash('fastgo@123', PASSWORD_DEFAULT);
 $conn->prepare("UPDATE users SET password=? WHERE id=? AND role='driver'")->execute([$hash, $uid]);
 $msg = "Đã đặt lại mật khẩu về <strong>fastgo@123</strong>."; $msg_type = 'success';
 }
}

if (isset($_GET['edit'])) {
 $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='driver'");
 $stmt->execute([(int)$_GET['edit']]);
 $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$drivers = $conn->query("SELECT * FROM users WHERE role='driver' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-admin.php";
?>

<style>
.btn-sm { padding:4px 12px; border-radius:6px; font-size:.73rem; font-weight:700; cursor:pointer; border:none; transition:.15s; white-space:nowrap; text-decoration:none; display:inline-block; }
.btn-edit { background:#EFF6FF; color:#2563EB; } .btn-edit:hover { background:#DBEAFE; }
.btn-lock { background:#FFF7ED; color:#EA580C; } .btn-lock:hover { background:#FFEDD5; }
.btn-unlock { background:#F0FDF4; color:#16A34A; } .btn-unlock:hover { background:#DCFCE7; }
.btn-delete { background:#FEF2F2; color:#DC2626; } .btn-delete:hover { background:#FEE2E2; }
.btn-reset { background:#F5F3FF; color:#7C3AED; } .btn-reset:hover { background:#EDE9FE; }
.btn-view { background:#F0F9FF; color:#0369A1; } .btn-view:hover { background:#E0F2FE; }
.btn-primary { background:var(--role-color); color:#fff; padding:10px 22px; border-radius:8px; font-size:.88rem; font-weight:700; border:none; cursor:pointer; }
.btn-primary:hover { opacity:.9; }
.btn-secondary { background:#F1F5F9; color:#475569; padding:10px 22px; border-radius:8px; font-size:.88rem; font-weight:700; border:none; cursor:pointer; text-decoration:none; display:inline-block; }

.form-card { background:#fff; border-radius:14px; padding:28px; border:1px solid var(--border); margin-bottom:24px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
.form-group label { display:block; font-size:.8rem; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
.form-group input, .form-group select { width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:.88rem; box-sizing:border-box; }
.form-group input:focus, .form-group select:focus { outline:none; border-color:var(--role-color); }
.form-actions { display:flex; gap:10px; margin-top:20px; align-items:center; }
.form-section-title { font-size:.78rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:.5px; margin:20px 0 12px; border-top:1px solid var(--border); padding-top:16px; }

.badge-active { background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-locked { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
.alert-success { background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }
.alert-warning { background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }
.alert-error { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }
.user-actions { display:flex; gap:6px; flex-wrap:wrap; }
.stat-pill { background:#F1F5F9; border-radius:20px; padding:2px 10px; font-size:.72rem; font-weight:700; color:#475569; }

.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:14px; padding:32px 28px; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-box h3 { margin:0 0 8px; } .modal-box p { color:#64748B; font-size:.87rem; margin:0 0 24px; }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; }
.btn-confirm { padding:8px 20px; border-radius:8px; font-weight:700; border:none; cursor:pointer; font-size:.85rem; }
.btn-confirm-delete { background:#EF4444; color:#fff; }
.btn-confirm-lock { background:#EA580C; color:#fff; }
.btn-confirm-reset { background:#7C3AED; color:#fff; }
.btn-cancel-modal { background:#F1F5F9; color:#475569; padding:8px 20px; border-radius:8px; font-weight:700; border:none; cursor:pointer; font-size:.85rem; }

.orders-panel { background:#F8FAFC; border:1px solid var(--border); border-radius:10px; padding:16px; margin-top:10px; }
.orders-panel table { width:100%; font-size:.8rem; }
.orders-panel th { background:#F1F5F9; padding:6px 10px; text-align:left; font-size:.72rem; color:var(--text-muted); }
.orders-panel td { padding:6px 10px; border-bottom:1px solid var(--border); }
</style>

<div class="page-header">
 <div>
 <h2><?= $edit_user ? 'Chỉnh sửa' : 'Quản lý' ?><span>tài xế</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($drivers) ?>tài xế trong hệ thống</p>
 </div>
</div>

<?php if ($msg): ?><div class="alert-<?= $msg_type ?>"><?= $msg ?></div><?php endif; ?>

<!-- ─── FORM THÊM / SỬA ─────────────────────────────────────── -->
<div class="form-card">
 <h3 style="margin:0 0 20px;font-size:1rem;"><?= $edit_user ? 'Chỉnh sửa tài xế' : 'Thêm tài xế mới' ?></h3>
 <form method="POST">
 <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'add' ?>">
 <?php if ($edit_user): ?><input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>"><?php endif; ?>

 <div class="form-grid">
 <div class="form-group">
 <label>Họ tên *</label>
 <input type="text" name="fullname" required placeholder="Nguyễn Văn A"
 value="<?= htmlspecialchars($edit_user['fullname'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Email *</label>
 <input type="email" name="email" required placeholder="taixe@gmail.com"
 value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Số điện thoại</label>
 <input
    type="text"
    name="phone"
    required
    maxlength="10"
    pattern="0[0-9]{9}"
    title="Số điện thoại phải gồm 10 số và bắt đầu bằng số 0" placeholder="0901000000"
 value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label><?= $edit_user ? 'Mật khẩu mới (bỏ trống = giữ nguyên)' : 'Mật khẩu *' ?></label>
 <input
    type="password"
    name="password"
    <?= $edit_user ? '' : 'required' ?>
    minlength="8"
    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z\d]).{8,}"
    title="Ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt"
    placeholder="<?= $edit_user ? 'Để trống nếu không đổi' : 'Nhập mật khẩu' ?>">
 </div>
 </div>

 <p class="form-section-title">Thông tin phương tiện & giấy tờ</p>
 <div class="form-grid3">
 <div class="form-group">
 <label>Biển số xe</label>
 <input type="text" name="vehicle_plate" placeholder="77A1-12345"
 value="<?= htmlspecialchars($edit_user['vehicle_plate'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Loại bằng lái</label>
 <select name="license_type">
 <option value="">-- Chọn --</option>
 <?php foreach (['A1','A2','B1','B2','C','D','E','F'] as $lt): ?>
 <option value="<?= $lt ?>" <?= ($edit_user['license_type'] ?? '') === $lt ? 'selected' : '' ?>><?= $lt ?></option>
 <?php endforeach; ?>
 </select>
 </div>
 <div class="form-group">
 <label>Số CCCD</label>
 <input type="text" name="cccd" placeholder="052204000001"
 value="<?= htmlspecialchars($edit_user['cccd'] ?? '') ?>">
 </div>
 </div>

 <div class="form-actions">
 <button type="submit" class="btn-primary"><?= $edit_user ? 'Lưu thay đổi' : 'Thêm tài xế' ?></button>
 <?php if ($edit_user): ?>
 <a href="drivers.php" class="btn-secondary">Hủy</a>
 <?php endif; ?>
 </div>
 </form>
</div>

<!-- ─── BẢNG DANH SÁCH ──────────────────────────────────────── -->
<div class="table-wrap">
<div class="table-header"><h3>Tất cả tài xế</h3></div>
<table>
<thead>
<tr>
 <th>ID</th><th>Họ tên</th><th>Email / SĐT</th>
 <th>Biển số</th><th>Bằng lái</th>
 <th>Trạng thái</th><th>Hiệu suất</th><th style="min-width:310px">Thao tác</th>
</tr>
</thead>
<tbody>
<?php if (empty($drivers)): ?>
<tr><td colspan="8" class="empty-state"><p>Chưa có tài xế nào</p></td></tr>
<?php else: ?>
<?php foreach ($drivers as $d):
 $is_locked = (($d['status'] ?? 'active') === 'locked');
 // Thống kê đơn của tài xế
 $stats = $conn->prepare("
 SELECT
 COUNT(*) AS total,
 SUM(status='completed') AS completed,
 SUM(status IN ('assigned','picked_up','delivering')) AS active_orders
 FROM orders WHERE driver_id=?
 ");
 $stats->execute([$d['id']]);
 $st = $stats->fetch(PDO::FETCH_ASSOC);
 $total_orders = (int)$st['total'];
 $completed_orders = (int)$st['completed'];
 $active_orders = (int)$st['active_orders'];
?>
<tr style="<?= $is_locked ? 'opacity:.65;' : '' ?>">
 <td><?= $d['id'] ?></td>
 <td><strong><?= htmlspecialchars($d['fullname']) ?></strong></td>
 <td>
 <div style="font-size:.83rem"><?= htmlspecialchars($d['email']) ?></div>
 <div style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($d['phone']) ?></div>
 </td>
 <td><?= htmlspecialchars($d['vehicle_plate'] ?: '—') ?></td>
 <td><?= htmlspecialchars($d['license_type'] ?: '—') ?></td>
 <td><span class="badge-<?= $is_locked ? 'locked' : 'active' ?>"><?= $is_locked ? 'Đã khóa' : 'Hoạt động' ?></span></td>
 <td>
 <div style="display:flex;gap:4px;flex-wrap:wrap;">
 <?php if ($active_orders >0): ?>
 <span class="stat-pill" style="background:#FEF3C7;color:#92400E;"><?= $active_orders ?>đang giao</span>
 <?php endif; ?>
 <span class="stat-pill"><?= $completed_orders ?>/<?= $total_orders ?></span>
 </div>
 </td>
 <td>
 <div class="user-actions">
 <a href="?edit=<?= $d['id'] ?>" class="btn-sm btn-edit">Sửa</a>
 <button class="btn-sm btn-reset" onclick="openModal('reset', <?= $d['id'] ?>, '<?= addslashes(htmlspecialchars($d['fullname'])) ?>')">Reset MK</button>
 <?php if ($is_locked): ?>
 <button class="btn-sm btn-unlock" onclick="openModal('unlock', <?= $d['id'] ?>, '<?= addslashes(htmlspecialchars($d['fullname'])) ?>')">Mở khóa</button>
 <?php else: ?>
 <button class="btn-sm btn-lock" onclick="openModal('lock', <?= $d['id'] ?>, '<?= addslashes(htmlspecialchars($d['fullname'])) ?>')">Khóa</button>
 <?php endif; ?>
 <button class="btn-sm btn-delete" onclick="openModal('delete', <?= $d['id'] ?>, '<?= addslashes(htmlspecialchars($d['fullname'])) ?>')">Xóa</button>
 <?php if ($total_orders >0): ?>
 <button class="btn-sm btn-view" onclick="toggleOrders(<?= $d['id'] ?>)"><?= $total_orders ?>đơn</button>
 <?php endif; ?>
 </div>
 <!-- Panel đơn hàng inline -->
 <div id="orders-<?= $d['id'] ?>" class="orders-panel" style="display:none">
 <?php
 $ords = $conn->prepare("
 SELECT orders.id, orders.status, orders.created_at, u.fullname AS customer
 FROM orders
 JOIN users u ON orders.customer_id = u.id
 WHERE orders.driver_id=?
 ORDER BY orders.id DESC LIMIT 10
 ");
 $ords->execute([$d['id']]);
 $ords_data = $ords->fetchAll(PDO::FETCH_ASSOC);
 ?>
 <table>
 <thead><tr><th>#Đơn</th><th>Khách hàng</th><th>Trạng thái</th><th>Ngày tạo</th></tr></thead>
 <tbody>
 <?php foreach ($ords_data as $o): ?>
 <tr>
 <td><strong>#<?= $o['id'] ?></strong></td>
 <td><?= htmlspecialchars($o['customer']) ?></td>
 <td><span class="badge badge-<?= $o['status'] ?>"><?= getStatusText($o['status']) ?></span></td>
 <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 <?php if ($total_orders >10): ?>
 <p style="font-size:.75rem;color:var(--text-muted);margin:8px 0 0;text-align:center">Hiển thị 10 / <?= $total_orders ?>đơn gần nhất</p>
 <?php endif; ?>
 </div>
 </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- ─── MODAL XÁC NHẬN ─────────────────────────────────────── -->
<div class="modal-overlay" id="confirmModal">
 <div class="modal-box">
 <h3 id="modal-title"></h3>
 <p id="modal-desc"></p>
 <div class="modal-actions">
 <button class="btn-cancel-modal" onclick="closeModal()">Hủy bỏ</button>
 <form method="POST" id="modal-form" style="margin:0">
 <input type="hidden" name="user_id" id="modal-user-id">
 <input type="hidden" name="action" id="modal-action">
 <button type="submit" class="btn-confirm" id="modal-confirm-btn">Xác nhận</button>
 </form>
 </div>
 </div>
</div>

<script>
const modalCfg = {
 delete: { title:'Xóa tài xế', desc: n=>`Xóa tài khoản <strong>${n}</strong>? Không thể hoàn tác.`, btnClass:'btn-confirm-delete', btnText:'Xóa' },
 lock: { title:'Khóa tài khoản', desc: n=>`Khóa <strong>${n}</strong>? Tài xế sẽ không thể đăng nhập.`, btnClass:'btn-confirm-lock', btnText:'Khóa' },
 unlock: { title:'Mở khóa tài khoản', desc: n=>`Mở khóa <strong>${n}</strong>?`, btnClass:'btn-unlock', btnText:'Mở khóa' },
 reset: { title:'Đặt lại mật khẩu', desc: n=>`Đặt lại MK của <strong>${n}</strong>về <code>fastgo@123</code>?`, btnClass:'btn-confirm-reset', btnText:'Đặt lại MK' },
};
function openModal(action, id, name) {
 const c = modalCfg[action];
 document.getElementById('modal-title').textContent = c.title;
 document.getElementById('modal-desc').innerHTML = c.desc(name);
 document.getElementById('modal-user-id').value = id;
 document.getElementById('modal-action').value = action;
 const btn = document.getElementById('modal-confirm-btn');
 btn.className = 'btn-confirm ' + c.btnClass;
 btn.textContent = c.btnText;
 document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e =>{ if (e.target.id === 'confirmModal') closeModal(); });

function toggleOrders(id) {
 const el = document.getElementById('orders-' + id);
 el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include "../includes/footer.php"; ?>
