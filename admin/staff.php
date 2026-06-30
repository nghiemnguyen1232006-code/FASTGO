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

 if (!validatePhone($phone)) {
    $msg = "Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0.";
    $msg_type = 'error';

} elseif (!validatePassword($password)) {
    $msg = "Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.";

    $msg_type = 'error';

}else{
 // Kiểm tra email trùng
$chk = $conn->prepare("SELECT id FROM users WHERE email=?");
$chk->execute([$email]);

if ($chk->fetch()) {
    $msg = "Email đã tồn tại trong hệ thống.";
    $msg_type = 'error';

} else {

    // Kiểm tra SĐT trùng
    $chk = $conn->prepare("SELECT id FROM users WHERE phone=?");
    $chk->execute([$phone]);

    if ($chk->fetch()) {

        $msg = "Số điện thoại đã tồn tại trong hệ thống.";
        $msg_type = 'error';

    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users
            (fullname,email,phone,password,role,status)
            VALUES (?,?,?,?,'staff','active')
        ");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $hash
        ]);

        $msg = "Đã thêm nhân viên <strong>$fullname</strong> thành công.";
        $msg_type = 'success';
    }
}
}
 } elseif ($action === 'edit') {
 $fullname = trim($_POST['fullname']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $password = trim($_POST['password']);

 if (!validatePhone($phone)) {

    $msg = "Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0.";
    $msg_type = 'error';

} elseif ($password !== '' && !validatePassword($password)) {

    $msg = "Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.";
    $msg_type = 'error';

} else {

    // Email trùng
    $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $chk->execute([$email, $uid]);

    if ($chk->fetch()) {

        $msg = "Email đã được dùng bởi tài khoản khác.";
        $msg_type = 'error';

    } else {

        // SĐT trùng
        $chk = $conn->prepare("SELECT id FROM users WHERE phone=? AND id!=?");
        $chk->execute([$phone, $uid]);

        if ($chk->fetch()) {

            $msg = "Số điện thoại đã được dùng bởi tài khoản khác.";
            $msg_type = 'error';

        } else {

            if ($password !== '') {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    UPDATE users
                    SET fullname=?,email=?,phone=?,password=?
                    WHERE id=? AND role='staff'
                ");

                $stmt->execute([
                    $fullname,
                    $email,
                    $phone,
                    $hash,
                    $uid
                ]);

            } else {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET fullname=?,email=?,phone=?
                    WHERE id=? AND role='staff'
                ");

                $stmt->execute([
                    $fullname,
                    $email,
                    $phone,
                    $uid
                ]);
            }

            $msg = "Đã cập nhật thông tin nhân viên.";
            $msg_type = 'success';
        }
    }
}

 } elseif ($action === 'lock') {
 $conn->prepare("UPDATE users SET status='locked' WHERE id=? AND role='staff'")->execute([$uid]);
 $msg = "Đã khóa tài khoản."; $msg_type = 'warning';

 } elseif ($action === 'unlock') {
 $conn->prepare("UPDATE users SET status='active' WHERE id=? AND role='staff'")->execute([$uid]);
 $msg = "Đã mở khóa tài khoản."; $msg_type = 'success';

 } elseif ($action === 'delete') {
 $conn->prepare("DELETE FROM users WHERE id=? AND role='staff'")->execute([$uid]);
 $msg = "Đã xóa nhân viên."; $msg_type = 'success';

 } elseif ($action === 'reset_password') {
 $hash = password_hash('fastgo@123', PASSWORD_DEFAULT);
 $conn->prepare("UPDATE users SET password=? WHERE id=? AND role='staff'")->execute([$hash, $uid]);
 $msg = "Đã đặt lại mật khẩu về <strong>fastgo@123</strong>."; $msg_type = 'success';
 }
}

// Load form sửa nếu có ?edit=id
if (isset($_GET['edit'])) {
 $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='staff'");
 $stmt->execute([(int)$_GET['edit']]);
 $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ─── LẤY DANH SÁCH ─────────────────────────────────────────── */
$staffs = $conn->query("SELECT * FROM users WHERE role='staff' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
.form-group label { display:block; font-size:.8rem; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
.form-group input { width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:.88rem; box-sizing:border-box; }
.form-group input:focus { outline:none; border-color:var(--role-color); }
.form-actions { display:flex; gap:10px; margin-top:20px; align-items:center; }

.badge-active { background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-locked { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
.alert-success { background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }
.alert-warning { background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }
.alert-error { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; }

.tab-orders { display:none; }
.tab-orders.open { display:block; }
.user-actions { display:flex; gap:6px; flex-wrap:wrap; }

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

/* Panel xem đơn hàng inline */
.orders-panel { background:#F8FAFC; border:1px solid var(--border); border-radius:10px; padding:16px; margin-top:10px; }
.orders-panel table { width:100%; font-size:.8rem; }
.orders-panel th { background:#F1F5F9; padding:6px 10px; text-align:left; font-size:.72rem; color:var(--text-muted); }
.orders-panel td { padding:6px 10px; border-bottom:1px solid var(--border); }
</style>

<div class="page-header">
 <div>
 <h2><?= $edit_user ? 'Chỉnh sửa' : 'Quản lý' ?><span>nhân viên</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($staffs) ?>nhân viên trong hệ thống</p>
 </div>
</div>

<?php if ($msg): ?><div class="alert-<?= $msg_type ?>"><?= $msg ?></div><?php endif; ?>

<!-- ─── FORM THÊM / SỬA ─────────────────────────────────────── -->
<div class="form-card">
 <h3 style="margin:0 0 20px;font-size:1rem;"><?= $edit_user ? 'Chỉnh sửa nhân viên' : 'Thêm nhân viên mới' ?></h3>
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
 <input type="email" name="email" required placeholder="nhanvien@gmail.com"
 value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
 </div>
 <div class="form-group">
 <label>Số điện thoại</label>
 <input
    type="text"
    name="phone"
    maxlength="10"
    pattern="0[0-9]{9}"
    title="Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0"
    placeholder="0900000000"
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
 <div class="form-actions">
 <button type="submit" class="btn-primary"><?= $edit_user ? 'Lưu thay đổi' : 'Thêm nhân viên' ?></button>
 <?php if ($edit_user): ?>
 <a href="staff.php" class="btn-secondary">Hủy</a>
 <?php endif; ?>
 </div>
 </form>
</div>

<!-- ─── BẢNG DANH SÁCH ──────────────────────────────────────── -->
<div class="table-wrap">
<div class="table-header"><h3>Tất cả nhân viên</h3></div>
<table>
<thead>
<tr>
 <th>ID</th><th>Họ tên</th><th>Email</th><th>SĐT</th>
 <th>Trạng thái</th><th>Ngày tạo</th><th style="min-width:300px">Thao tác</th>
</tr>
</thead>
<tbody>
<?php if (empty($staffs)): ?>
<tr><td colspan="7" class="empty-state"><p>Chưa có nhân viên nào</p></td></tr>
<?php else: ?>
<?php foreach ($staffs as $s):
 $is_locked = (($s['status'] ?? 'active') === 'locked');
 // Đếm đơn hàng staff này quản lý
 $cnt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE staff_id=?");
 $cnt->execute([$s['id']]);
 $order_count = $cnt->fetchColumn();
?>
<tr style="<?= $is_locked ? 'opacity:.65;' : '' ?>">
 <td><?= $s['id'] ?></td>
 <td><strong><?= htmlspecialchars($s['fullname']) ?></strong></td>
 <td style="font-size:.83rem"><?= htmlspecialchars($s['email']) ?></td>
 <td><?= htmlspecialchars($s['phone']) ?></td>
 <td><span class="badge-<?= $is_locked ? 'locked' : 'active' ?>"><?= $is_locked ? 'Đã khóa' : 'Hoạt động' ?></span></td>
 <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
 <td>
 <div class="user-actions">
 <a href="?edit=<?= $s['id'] ?>" class="btn-sm btn-edit">Sửa</a>
 <button class="btn-sm btn-reset" onclick="openModal('reset', <?= $s['id'] ?>, '<?= addslashes(htmlspecialchars($s['fullname'])) ?>')">Reset MK</button>
 <?php if ($is_locked): ?>
 <button class="btn-sm btn-unlock" onclick="openModal('unlock', <?= $s['id'] ?>, '<?= addslashes(htmlspecialchars($s['fullname'])) ?>')">Mở khóa</button>
 <?php else: ?>
 <button class="btn-sm btn-lock" onclick="openModal('lock', <?= $s['id'] ?>, '<?= addslashes(htmlspecialchars($s['fullname'])) ?>')">Khóa</button>
 <?php endif; ?>
 <button class="btn-sm btn-delete" onclick="openModal('delete', <?= $s['id'] ?>, '<?= addslashes(htmlspecialchars($s['fullname'])) ?>')">Xóa</button>
 <?php if ($order_count >0): ?>
 <button class="btn-sm btn-view" onclick="toggleOrders(<?= $s['id'] ?>)"><?= $order_count ?>đơn</button>
 <?php endif; ?>
 </div>
 <!-- Panel đơn hàng inline -->
 <div id="orders-<?= $s['id'] ?>" class="orders-panel" style="display:none">
 <?php
 $ords = $conn->prepare("
 SELECT orders.id, orders.status, orders.created_at, u.fullname AS customer
 FROM orders
 JOIN users u ON orders.customer_id = u.id
 WHERE orders.staff_id=?
 ORDER BY orders.id DESC LIMIT 10
 ");
 $ords->execute([$s['id']]);
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
 <?php if ($order_count >10): ?>
 <p style="font-size:.75rem;color:var(--text-muted);margin:8px 0 0;text-align:center">Hiển thị 10 / <?= $order_count ?>đơn gần nhất</p>
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
 delete: { title:'Xóa nhân viên', desc: n=>`Xóa tài khoản <strong>${n}</strong>? Không thể hoàn tác.`, btnClass:'btn-confirm-delete', btnText:'Xóa' },
 lock: { title:'Khóa tài khoản', desc: n=>`Khóa <strong>${n}</strong>? Nhân viên sẽ không thể đăng nhập.`, btnClass:'btn-confirm-lock', btnText:'Khóa' },
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
