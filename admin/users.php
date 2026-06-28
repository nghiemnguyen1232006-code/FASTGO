<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('admin');

$current_admin_id = $_SESSION['user']['id'];
$msg = '';
$msg_type = '';

/* ─── XỬ LÝ ACTION ────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $target_id = (int)($_POST['user_id'] ?? 0);
 $action = $_POST['action'] ?? '';

 // Không cho phép thao tác lên chính mình
 if ($target_id === $current_admin_id) {
 $msg = "Bạn không thể thực hiện thao tác này lên chính mình.";
 $msg_type = 'error';
 } elseif ($action === 'delete') {
 // XÓA tài khoản (không xóa admin khác nếu muốn an toàn thêm)
 $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
 $stmt->execute([$target_id]);
 $target = $stmt->fetch(PDO::FETCH_ASSOC);
 if ($target && $target['role'] === 'admin') {
 $msg = "Không thể xóa tài khoản admin khác.";
 $msg_type = 'error';
 } else {
 $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
 $stmt->execute([$target_id]);
 $msg = "Đã xóa tài khoản thành công.";
 $msg_type = 'success';
 }
 } elseif ($action === 'lock') {
 $stmt = $conn->prepare("UPDATE users SET status='locked' WHERE id=?");
 $stmt->execute([$target_id]);
 $msg = "Đã khóa tài khoản.";
 $msg_type = 'warning';
 } elseif ($action === 'unlock') {
 $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
 $stmt->execute([$target_id]);
 $msg = "Đã mở khóa tài khoản.";
 $msg_type = 'success';
 } elseif ($action === 'reset_password') {
 // Đặt lại mật khẩu về mặc định: fastgo@123
 $new_pass = password_hash('fastgo@123', PASSWORD_DEFAULT);
 $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
 $stmt->execute([$new_pass, $target_id]);
 $msg = "Đã đặt lại mật khẩu về <strong>fastgo@123</strong>cho tài khoản #$target_id.";
 $msg_type = 'success';
 }
}

/* ─── LẤY DANH SÁCH ───────────────────────────────────────────── */
$stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-admin.php";
?>

<style>
 .user-actions {
 display: flex;
 gap: 6px;
 flex-wrap: wrap;
 align-items: center;
 }

 .btn-sm {
 padding: 4px 12px;
 border-radius: 6px;
 font-size: .73rem;
 font-weight: 700;
 cursor: pointer;
 border: none;
 transition: .15s;
 white-space: nowrap;
 }

 .btn-lock {
 background: #FFF7ED;
 color: #EA580C;
 }

 .btn-lock:hover {
 background: #FFEDD5;
 }

 .btn-unlock {
 background: #F0FDF4;
 color: #16A34A;
 }

 .btn-unlock:hover {
 background: #DCFCE7;
 }

 .btn-delete {
 background: #FEF2F2;
 color: #DC2626;
 }

 .btn-delete:hover {
 background: #FEE2E2;
 }

 .btn-reset {
 background: #F5F3FF;
 color: #7C3AED;
 }

 .btn-reset:hover {
 background: #EDE9FE;
 }

 .badge-active {
 background: #D1FAE5;
 color: #065F46;
 border: 1px solid #6EE7B7;
 }

 .badge-locked {
 background: #FEE2E2;
 color: #991B1B;
 border: 1px solid #FCA5A5;
 }

 .alert-success {
 background: #D1FAE5;
 color: #065F46;
 border: 1px solid #6EE7B7;
 padding: 12px 16px;
 border-radius: 8px;
 margin-bottom: 16px;
 font-weight: 600;
 }

 .alert-warning {
 background: #FEF3C7;
 color: #92400E;
 border: 1px solid #FCD34D;
 padding: 12px 16px;
 border-radius: 8px;
 margin-bottom: 16px;
 font-weight: 600;
 }

 .alert-error {
 background: #FEE2E2;
 color: #991B1B;
 border: 1px solid #FCA5A5;
 padding: 12px 16px;
 border-radius: 8px;
 margin-bottom: 16px;
 font-weight: 600;
 }

 /* Modal reset */
 .modal-overlay {
 display: none;
 position: fixed;
 inset: 0;
 background: rgba(0, 0, 0, .45);
 z-index: 1000;
 align-items: center;
 justify-content: center;
 }

 .modal-overlay.open {
 display: flex;
 }

 .modal-box {
 background: #fff;
 border-radius: 14px;
 padding: 32px 28px;
 max-width: 420px;
 width: 90%;
 box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
 animation: fadeInUp .2s ease;
 }

 .modal-box h3 {
 margin: 0 0 8px;
 font-size: 1.1rem;
 }

 .modal-box p {
 color: #64748B;
 font-size: .87rem;
 margin: 0 0 24px;
 }

 .modal-actions {
 display: flex;
 gap: 10px;
 justify-content: flex-end;
 }

 .btn-confirm {
 padding: 8px 20px;
 border-radius: 8px;
 font-weight: 700;
 border: none;
 cursor: pointer;
 font-size: .85rem;
 }

 .btn-confirm-delete {
 background: #EF4444;
 color: #fff;
 }

 .btn-confirm-lock {
 background: #EA580C;
 color: #fff;
 }

 .btn-confirm-reset {
 background: #7C3AED;
 color: #fff;
 }

 .btn-cancel-modal {
 background: #F1F5F9;
 color: #475569;
 padding: 8px 20px;
 border-radius: 8px;
 font-weight: 700;
 border: none;
 cursor: pointer;
 font-size: .85rem;
 }
</style>

<div class="page-header">
 <div>
 <h2>Danh sách <span>người dùng</span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($users) ?>tài khoản trong hệ thống</p>
 </div>
</div>

<?php if ($msg): ?>
 <div class="alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<div class="table-wrap">
 <div class="table-header">
 <h3>Tất cả tài khoản</h3>
 </div>
 <table>
 <thead>
 <tr>
 <th>ID</th>
 <th>Họ tên</th>
 <th>Email</th>
 <th>SĐT</th>
 <th>Vai trò</th>
 <th>Trạng thái</th>
 <th>Ngày tạo</th>
 <th style="min-width:260px">Thao tác</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($users as $user):
 $is_self = ($user['id'] == $current_admin_id);
 $is_admin = ($user['role'] === 'admin');
 $status = $user['status'] ?? 'active';
 $is_locked = ($status === 'locked');
 ?>
 <tr style="<?= $is_locked ? 'opacity:.65;' : '' ?>">
 <td><?= $user['id'] ?></td>
 <td>
 <?= htmlspecialchars($user['fullname']) ?>
 <?php if ($is_self): ?><span style="font-size:.7rem;background:#DBEAFE;color:#1D4ED8;padding:1px 7px;border-radius:20px;margin-left:4px;font-weight:700;">Bạn</span><?php endif; ?>
 </td>
 <td><?= htmlspecialchars($user['email']) ?></td>
 <td><?= htmlspecialchars($user['phone']) ?></td>
 <td>
 <span class="badge badge-<?= $user['role'] === 'admin' ? 'cancelled' : ($user['role'] === 'driver' ? 'delivering' : ($user['role'] === 'staff' ? 'assigned' : 'completed')) ?>">
 + <?= strtoupper($user['role']) ?>
 </span>
 </td>
 <td>
 <span class="badge <?= $is_locked ? 'badge-locked' : 'badge-active' ?>">
 <?= $is_locked ? 'Đã khóa' : 'Hoạt động' ?>
 </span>
 </td>
 <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
 <td>
 <?php if ($is_self): ?>
 <span style="font-size:.75rem;color:var(--text-muted);font-style:italic">Tài khoản hiện tại</span>
 <?php else: ?>
 <div class="user-actions">
 <!-- Đặt lại mật khẩu -->
 <button class="btn-sm btn-reset"
 onclick="openModal('reset', <?= $user['id'] ?>, '<?= addslashes(htmlspecialchars($user['fullname'])) ?>')">
 Reset MK
 </button>

 <!-- Khóa / Mở khóa -->
 <?php if ($is_locked): ?>
 <button class="btn-sm btn-unlock"
 onclick="openModal('unlock', <?= $user['id'] ?>, '<?= addslashes(htmlspecialchars($user['fullname'])) ?>')">
 Mở khóa
 </button>
 <?php else: ?>
 <button class="btn-sm btn-lock"
 onclick="openModal('lock', <?= $user['id'] ?>, '<?= addslashes(htmlspecialchars($user['fullname'])) ?>')">
 Khóa TK
 </button>
 <?php endif; ?>

 <!-- Xóa (không cho xóa admin khác) -->
 <?php if (!$is_admin): ?>
 <button class="btn-sm btn-delete"
 onclick="openModal('delete', <?= $user['id'] ?>, '<?= addslashes(htmlspecialchars($user['fullname'])) ?>')">
 Xóa
 </button>
 <?php endif; ?>
 </div>
 <?php endif; ?>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
</div>

<!-- ─── MODAL XÁC NHẬN ──────────────────────────────────────── -->
<div class="modal-overlay" id="confirmModal">
 <div class="modal-box">
 <h3 id="modal-title">Xác nhận</h3>
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
 delete: {
 title: 'Xóa tài khoản',
 desc: name =>`Bạn có chắc muốn xóa tài khoản <strong>${name}</strong>? Hành động này không thể hoàn tác.`,
 btnClass: 'btn-confirm-delete',
 btnText: 'Xóa tài khoản'
 },
 lock: {
 title: 'Khóa tài khoản',
 desc: name =>`Bạn có chắc muốn khóa tài khoản <strong>${name}</strong>? Người dùng sẽ không thể đăng nhập.`,
 btnClass: 'btn-confirm-lock',
 btnText: 'Khóa tài khoản'
 },
 unlock: {
 title: 'Mở khóa tài khoản',
 desc: name =>`Mở khóa tài khoản <strong>${name}</strong>? Người dùng sẽ đăng nhập lại được bình thường.`,
 btnClass: 'btn-unlock',
 btnText: 'Mở khóa'
 },
 reset: {
 title: 'Đặt lại mật khẩu',
 desc: name =>`Đặt lại mật khẩu của <strong>${name}</strong>về <code style="background:#F1F5F9;padding:2px 6px;border-radius:4px">fastgo@123</code>?`,
 btnClass: 'btn-confirm-reset',
 btnText: 'Đặt lại mật khẩu'
 }
 };

 function openModal(action, userId, name) {
 const cfg = modalCfg[action];
 document.getElementById('modal-title').textContent = cfg.title;
 document.getElementById('modal-desc').innerHTML = cfg.desc(name);
 document.getElementById('modal-user-id').value = userId;
 document.getElementById('modal-action').value = action;
 const btn = document.getElementById('modal-confirm-btn');
 btn.className = 'btn-confirm ' + cfg.btnClass;
 btn.textContent = cfg.btnText;
 document.getElementById('confirmModal').classList.add('open');
 }

 function closeModal() {
 document.getElementById('confirmModal').classList.remove('open');
 }

 // Đóng khi click bên ngoài
 document.getElementById('confirmModal').addEventListener('click', function(e) {
 if (e.target === this) closeModal();
 });
</script>

<?php include "../includes/footer.php"; ?>