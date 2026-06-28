<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('staff');

$msg      = '';
$msg_type = '';

// Migration an toàn
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS distance_km DECIMAL(8,2) DEFAULT 0");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS package_type VARCHAR(20) DEFAULT 'small'");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS note TEXT DEFAULT NULL");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_fee DECIMAL(12,2) DEFAULT 0");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS fee_approved TINYINT(1) DEFAULT 0");
} catch (Exception $e) {}

// Duyệt phí + gán tài xế
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_fee'])) {
        $order_id     = (int)$_POST['order_id'];
        $final_price  = (float)$_POST['final_price'];
        $conn->prepare("UPDATE orders SET price=?, shipping_fee=?, fee_approved=1 WHERE id=?")
             ->execute([$final_price, $final_price, $order_id]);
        $msg = "Đã duyệt phí cho đơn #$order_id.";
        $msg_type = 'success';
    }

    if (isset($_POST['assign'])) {
        $order_id  = (int)$_POST['order_id'];
        $driver_id = (int)$_POST['driver_id'];
        $staff_id  = $_SESSION['user']['id'];
        if ($driver_id) {
            $conn->prepare("UPDATE orders SET driver_id=?, staff_id=?, status='assigned' WHERE id=?")
                 ->execute([$driver_id, $staff_id, $order_id]);
            $msg = "Đã gán tài xế cho đơn #$order_id.";
            $msg_type = 'success';
        }
    }
}

// Đơn chờ gán
$orders = $conn->query("
    SELECT orders.*, u.fullname AS customer_name, u.phone AS customer_phone
    FROM orders
    LEFT JOIN users u ON orders.customer_id = u.id
    WHERE orders.driver_id IS NULL AND orders.status='pending'
    ORDER BY orders.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Tài xế active
$drivers = $conn->query("SELECT id, fullname, phone FROM users WHERE role='driver' AND status='active' ORDER BY fullname")
                ->fetchAll(PDO::FETCH_ASSOC);

$pkg_labels = [
    'document'=>'📄 Tài liệu','small'=>'📦 Nhỏ','medium'=>'🛍️ Vừa',
    'large'=>'📫 Lớn','fragile'=>'🪴 Dễ vỡ','food'=>'🍱 Thực phẩm',
];

include "../includes/header-nhanvien.php";
?>

<style>
.pg-head { padding: 28px 0 20px; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
.pg-head h2 { font-family:'Space Grotesk',sans-serif; font-size:1.4rem; font-weight:700; color:#111827; margin:0; }
.pg-head h2 span { color:#0ea5e9; }
.pg-head p { color:#9ca3af; font-size:.84rem; margin:4px 0 0; }

.alert-success { background:#f0fdf4; color:#14532d; border:1px solid #bbf7d0; border-radius:10px; padding:12px 16px; margin-bottom:16px; font-weight:600; font-size:.85rem; }

/* dispatch card */
.dispatch-wrap { display:flex; flex-direction:column; gap:14px; }
.dispatch-card {
    background:#fff;
    border:1.5px solid #e5e7eb;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 1px 3px rgba(0,0,0,.05);
    transition:.2s;
}
.dispatch-card:hover { border-color:#fed7aa; box-shadow:0 4px 16px rgba(249,115,22,.1); }

.dc-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px;
    background:#fafafa;
    border-bottom:1px solid #f3f4f6;
}
.dc-id { font-family:'Space Grotesk',sans-serif; font-weight:800; color:#f97316; font-size:.95rem; }
.dc-customer { font-size:.82rem; color:#6b7280; margin-left:10px; }
.dc-pkg { background:#f3f4f6; border-radius:20px; padding:3px 10px; font-size:.72rem; font-weight:700; color:#6b7280; }
.dc-fee-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:20px; font-size:.72rem; font-weight:700;
}
.fee-pending { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
.fee-approved { background:#f0fdf4; color:#14532d; border:1px solid #bbf7d0; }

.dc-body {
    display:grid;
    grid-template-columns:1fr 1fr 280px;
    gap:0;
}
.dc-col { padding:16px 20px; border-right:1px solid #f3f4f6; }
.dc-col:last-child { border-right:none; }
.dc-col-label {
    font-size:.65rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.6px; color:#9ca3af; margin-bottom:8px;
}
.dc-addr { font-size:.84rem; color:#374151; font-weight:500; line-height:1.5; }
.dc-addr .addr-from, .dc-addr .addr-to {
    display:flex; gap:7px; align-items:flex-start;
}
.dc-addr .addr-dot {
    width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px;
}
.dc-addr .addr-line { width:1.5px; height:14px; background:#e5e7eb; margin:3px auto; }

/* fee breakdown col */
.fee-detail-row { display:flex; justify-content:space-between; font-size:.78rem; margin-bottom:5px; }
.fee-detail-row .fl { color:#9ca3af; }
.fee-detail-row .fv { font-weight:600; color:#374151; font-family:'Space Grotesk',sans-serif; }
.fee-divider { height:1px; background:#f3f4f6; margin:8px 0; }
.fee-total-row { display:flex; justify-content:space-between; align-items:center; font-size:.85rem; }
.fee-total-row .fl { font-weight:700; color:#374151; }
.fee-total-row .fv { font-family:'Space Grotesk',sans-serif; font-size:1.1rem; font-weight:800; color:#f97316; }

/* action col */
.dc-action{
    display:grid;
    grid-template-columns:320px 1fr;
    gap:20px;

    padding:18px 20px;

    background:#fafafa;
    border-top:1px solid #f3f4f6;

    align-items:start;
}

.fee-edit-form { width:100%; }
.fee-edit-form label { font-size:.72rem; font-weight:700; text-transform:uppercase; color:#9ca3af; display:block; margin-bottom:5px; }
.fee-input-row { display:flex; gap:8px; }
.fee-input-row input {
    flex:1; padding:8px 12px; border:1.5px solid #e5e7eb; border-radius:8px;
    font-size:.88rem; font-family:'Space Grotesk',sans-serif; font-weight:700;
    color:#f97316; outline:none;
}
.fee-input-row input:focus { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.1); }
.btn-approve {
    padding:8px 16px; background:#10b981; color:#fff; border:none; border-radius:8px;
    font-weight:700; font-size:.8rem; cursor:pointer; white-space:nowrap; transition:.15s;
}
.btn-approve:hover { background:#059669; transform:none; }

.assign-form { display:flex; gap:10px; align-items:end; width:100%; }
.assign-form > div{
    flex:1;
}
.assign-form label { font-size:.72rem; font-weight:700; text-transform:uppercase; color:#9ca3af; display:block; margin-bottom:5px; }
.assign-form select {
    padding:8px 12px; border:1.5px solid #e5e7eb; border-radius:8px;
    font-size:.84rem; width:100%; outline:none;
}
.assign-form select:focus { border-color:#0ea5e9; }
.btn-assign {
    padding:8px 16px; background:#0ea5e9; color:#fff; border:none; border-radius:8px;
    font-weight:700; font-size:.8rem; cursor:pointer; white-space:nowrap; transition:.15s;
}
.btn-assign:hover { background:#0284c7; transform:none; }
.btn-assign:disabled { background:#d1d5db; cursor:not-allowed; }

.note-chip {
    background:#f3f4f6; border-radius:8px; padding:6px 10px;
    font-size:.76rem; color:#6b7280; margin-top:6px;
    display:flex; gap:5px;
}

.empty-dispatch {
    text-align:center; padding:60px 24px;
    background:#fff; border-radius:14px; border:1px solid #e5e7eb;
    color:#9ca3af;
}
.empty-dispatch .ei { width:56px; height:56px; background:#f3f4f6; border-radius:50%; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; }
.empty-dispatch .ei svg { width:24px; height:24px; stroke:#d1d5db; fill:none; stroke-width:1.5; }

@media(max-width:860px){ 
    .dc-body { grid-template-columns:1fr; } 
    .dc-col { border-right:none; border-bottom:1px solid #f3f4f6; }
    .dc-action{
        grid-template-columns:1fr;
    }
    }
</style>

<div class="pg-head">
    <div>
        <h2>Điều phối <span>đơn hàng</span></h2>
        <p><?= count($orders) ?> đơn chờ duyệt phí & gán tài xế</p>
    </div>
    <a href="orders.php" class="btn btn-ghost btn-sm">Xem tất cả đơn →</a>
</div>

<?php if ($msg): ?>
<div class="alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (empty($orders)): ?>
<div class="empty-dispatch">
    <div class="ei"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
    <p style="font-weight:600;font-size:.95rem;margin-bottom:6px">Không có đơn nào chờ điều phối</p>
    <p style="font-size:.84rem">Tất cả đơn đã được xử lý</p>
</div>
<?php else: ?>

<div class="dispatch-wrap">
<?php foreach ($orders as $order):
    $km         = (float)($order['distance_km'] ?? 0);
    $weight     = (float)($order['weight'] ?? 0);
    $pkg        = $order['package_type'] ?? 'small';
    $pkg_label  = $pkg_labels[$pkg] ?? $pkg;
    $approved   = (int)($order['fee_approved'] ?? 0);

    // Tính lại để hiển thị breakdown
    $base       = 15000;
    $km_fee     = round($km * 5000);
    $wt_fee     = $weight > 3 ? round(($weight - 3) * 3000) : 0;
    $type_fees  = ['document'=>0,'small'=>5000,'medium'=>10000,'large'=>20000,'fragile'=>15000,'food'=>15000];
    $type_fee   = $type_fees[$pkg] ?? 0;
    $calc_total = ceil(($base + $km_fee + $wt_fee + $type_fee) / 1000) * 1000;
    $final_price = (float)$order['price'];
?>
<div class="dispatch-card">
    <!-- Header -->
    <div class="dc-header">
        <div style="display:flex;align-items:center;gap:4px;">
            <span class="dc-id">#<?= $order['id'] ?></span>
            <span class="dc-customer">— <?= htmlspecialchars($order['customer_name'] ?? '—') ?> <?= $order['customer_phone'] ? '(' . htmlspecialchars($order['customer_phone']) . ')' : '' ?></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="dc-pkg"><?= $pkg_label ?></span>
            <span class="dc-fee-badge <?= $approved ? 'fee-approved' : 'fee-pending' ?>">
                <?= $approved ? '✓ Đã duyệt phí' : '⏳ Chờ duyệt phí' ?>
            </span>
        </div>
    </div>

    <!-- Body -->
    <div class="dc-body">
        <!-- Địa chỉ -->
        <div class="dc-col">
            <div class="dc-col-label">Tuyến đường</div>
            <div class="dc-addr">
                <div class="addr-from">
                    <span class="addr-dot" style="background:#f97316;margin-top:5px"></span>
                    <span><?= htmlspecialchars($order['pickup_address']) ?></span>
                </div>
                <div class="addr-line" style="margin-left:3px"></div>
                <div class="addr-to">
                    <span class="addr-dot" style="background:#10b981;margin-top:5px"></span>
                    <span><?= htmlspecialchars($order['delivery_address']) ?></span>
                </div>
            </div>
            <?php if ($order['note'] ?? ''): ?>
            <div class="note-chip">📝 <?= htmlspecialchars($order['note']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Chi tiết phí -->
        <div class="dc-col">
            <div class="dc-col-label">Chi tiết phí ship</div>
            <div class="fee-detail-row">
                <span class="fl">Phí cơ bản</span>
                <span class="fv"><?= number_format($base) ?>đ</span>
            </div>
            <div class="fee-detail-row">
                <span class="fl">Khoảng cách (<?= $km ?>km × 5,000)</span>
                <span class="fv"><?= number_format($km_fee) ?>đ</span>
            </div>
            <?php if ($wt_fee > 0): ?>
            <div class="fee-detail-row">
                <span class="fl">Phụ phí KL (<?= $weight ?>kg)</span>
                <span class="fv">+<?= number_format($wt_fee) ?>đ</span>
            </div>
            <?php endif; ?>
            <?php if ($type_fee > 0): ?>
            <div class="fee-detail-row">
                <span class="fl">Phụ phí loại hàng</span>
                <span class="fv">+<?= number_format($type_fee) ?>đ</span>
            </div>
            <?php endif; ?>
            <div class="fee-divider"></div>
            <div class="fee-detail-row">
                <span class="fl">Giá hệ thống tính</span>
                <span class="fv" style="color:#6b7280"><?= number_format($calc_total) ?>đ</span>
            </div>
            <div class="fee-total-row">
                <span class="fl">Giá xác nhận</span>
                <span class="fv"><?= number_format($final_price) ?>đ</span>
            </div>
        </div>

        <!-- Thông tin hàng -->
        <div class="dc-col">
            <div class="dc-col-label">Thông tin hàng</div>
            <div class="fee-detail-row"><span class="fl">Khối lượng</span><span class="fv"><?= $weight ?>kg</span></div>
            <div class="fee-detail-row"><span class="fl">Loại</span><span class="fv"><?= $pkg_label ?></span></div>
            <?php if ($km > 0): ?>
            <div class="fee-detail-row"><span class="fl">Khoảng cách</span><span class="fv"><?= $km ?>km</span></div>
            <?php endif; ?>
            <div class="fee-detail-row"><span class="fl">Ngày tạo</span><span class="fv" style="color:#9ca3af;font-size:.76rem"><?= date('d/m H:i', strtotime($order['created_at'])) ?></span></div>
        </div>
    </div>

    <!-- Actions -->
    <div class="dc-action">
        <!-- Duyệt phí -->
        <form method="POST" class="fee-edit-form" style="max-width:280px;">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <label>Điều chỉnh & duyệt phí (đ)</label>
            <div class="fee-input-row">
                <input type="number" name="final_price" step="1000" min="0"
                    value="<?= (int)$final_price ?>"
                    placeholder="Nhập giá xác nhận">
                <button type="submit" name="approve_fee" class="btn-approve">
                    <?= $approved ? '✓ Cập nhật' : '✓ Duyệt phí' ?>
                </button>
            </div>
        </form>

        <!-- Gán tài xế -->
        <form method="POST" class="assign-form" style="flex:1;min-width:220px;">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div style="flex:1;">
                <label>Gán tài xế</label>
                <select name="driver_id" <?= !$approved ? 'title="Hãy duyệt phí trước khi gán tài xế"' : '' ?>>
                    <option value="">— Chọn tài xế —</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['fullname']) ?> (<?= htmlspecialchars($d['phone'] ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="assign" class="btn-assign" <?= !$approved ? 'disabled' : '' ?>>
                Gán →
            </button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>