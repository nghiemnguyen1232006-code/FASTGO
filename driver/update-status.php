<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";
require "../includes/activity_log.php";
checkRole('driver');

if (!isset($_GET['id'])) {
    header("Location: my-orders.php"); exit;
}

$order_id  = (int)$_GET['id'];
$driver_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT orders.*, u.fullname AS customer_name, u.phone AS customer_phone FROM orders JOIN users u ON orders.customer_id = u.id WHERE orders.id=? AND orders.driver_id=?");
$stmt->execute([$order_id, $driver_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { header("Location: my-orders.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status  = $_POST['status'] ?? '';
    $allowed = ['picked_up', 'delivering', 'completed', 'cancelled'];
    if (!in_array($status, $allowed)) {
        $error = 'Trạng thái không hợp lệ.';
    } else {
        $conn->prepare("
UPDATE orders
SET status=?
WHERE id=? AND driver_id=?
")->execute([
    $status,
    $order_id,
    $driver_id
]);
if ($status == 'completed') {

    $stmtPay = $conn->prepare("
        UPDATE payments
        SET payment_status='paid',
            paid_at=NOW()
        WHERE order_id=?
    ");

    $stmtPay->execute([$order_id]);
}
logActivity(
    $conn,
    $_SESSION['user']['id'],
    $_SESSION['user']['fullname'],
    $_SESSION['user']['role'],
    'UPDATE_ORDER_STATUS',
    'Cập nhật trạng thái đơn #' . $order_id . ' thành ' . $status
);
header("Location: my-orders.php");
exit;
    }
}

$next_map = [
    'assigned'   => ['picked_up',  'Xác nhận lấy hàng'],
    'picked_up'  => ['delivering', 'Bắt đầu giao hàng'],
    'delivering' => ['completed',  'Xác nhận đã giao'],
];
$next = $next_map[$order['status']] ?? null;

include "../includes/header-nhanvien.php";
?>

<style>
.update-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
.info-section { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; margin-bottom: 16px; }
.info-section-title { padding: 12px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2); font-size: .7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; }
.info-row { display: flex; padding: 11px 18px; border-bottom: 1px solid var(--border); }
.info-row:last-child { border-bottom: none; }
.info-key { font-size: .8rem; font-weight: 600; color: var(--text-muted); width: 130px; flex-shrink: 0; }
.info-val { font-size: .875rem; color: var(--text); font-weight: 500; }
.action-panel { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; }
.action-panel-title { padding: 12px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2); font-size: .7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; }
.action-panel-body { padding: 20px; }
.status-options { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
.status-option { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition); font-size: .85rem; font-weight: 500; }
.status-option:has(input:checked) { border-color: var(--role-color); background: var(--role-light); color: var(--role-color); font-weight: 600; }
.status-option input[type=radio] { accent-color: var(--role-color); }
@media(max-width:800px){ .update-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div>
        <h2>Đơn hàng <span>#<?= $order['id'] ?></span></h2>
        <p style="color:var(--text-muted);font-size:.84rem;margin:0;">Trạng thái: <?= getStatusText($order['status']) ?></p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span class="badge badge-<?= $order['status'] ?>"><?= getStatusText($order['status']) ?></span>
        <a href="my-orders.php" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="update-grid">
    <div>
        <div class="info-section">
            <div class="info-section-title">Thông tin đơn hàng</div>
            <div class="info-row"><span class="info-key">Điểm lấy</span><span class="info-val"><?= htmlspecialchars($order['pickup_address']) ?></span></div>
            <div class="info-row"><span class="info-key">Điểm giao</span><span class="info-val"><?= htmlspecialchars($order['delivery_address']) ?></span></div>
            <div class="info-row"><span class="info-key">Khối lượng</span><span class="info-val"><?= $order['weight'] ?> kg</span></div>
            <div class="info-row"><span class="info-key">Phí vận chuyển</span><span class="info-val" style="font-weight:700;color:var(--role-color)"><?= number_format($order['price']) ?>đ</span></div>
        </div>
        <div class="info-section">
            <div class="info-section-title">Thông tin khách hàng</div>
            <div class="info-row"><span class="info-key">Họ tên</span><span class="info-val"><?= htmlspecialchars($order['customer_name']) ?></span></div>
            <div class="info-row"><span class="info-key">Điện thoại</span><span class="info-val"><a href="tel:<?= $order['customer_phone'] ?>"><?= htmlspecialchars($order['customer_phone']) ?></a></span></div>
        </div>
    </div>

    <div>
        <?php if (in_array($order['status'], ['completed','cancelled'])): ?>
        <div class="action-panel">
            <div class="action-panel-title">Trạng thái</div>
            <div class="action-panel-body" style="text-align:center;padding:32px 20px">
                <div style="font-size:1.8rem;margin-bottom:8px"><?= $order['status'] === 'completed' ? '✓' : '✗' ?></div>
                <div style="font-weight:700;color:<?= $order['status'] === 'completed' ? '#15803D' : '#9F1239' ?>"><?= getStatusText($order['status']) ?></div>
            </div>
        </div>

        <?php else: ?>
        <div class="action-panel">
            <div class="action-panel-title">Cập nhật trạng thái</div>
            <div class="action-panel-body">
                <form method="POST">
                    <div class="status-options">
                        <?php if ($next): ?>
                        <label class="status-option">
                            <input type="radio" name="status" value="<?= $next[0] ?>" checked>
                            <?= $next[1] ?>
                        </label>
                        <?php endif; ?>
                        <label class="status-option">
                            <input type="radio" name="status" value="cancelled">
                            Hủy đơn hàng
                        </label>
                    </div>
                    <button type="submit" style="width:100%">Xác nhận cập nhật</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
