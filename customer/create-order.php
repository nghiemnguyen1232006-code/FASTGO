<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/activity_log.php";
require "../includes/geocode.php";
checkRole('customer');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (($_POST['address_valid'] ?? 0) != 1) {
    $message =
        "Vui lòng kiểm tra địa chỉ trước khi tạo đơn.";
}
    $customer_id      = $_SESSION['user']['id'];
    $pickup_address   = trim($_POST['pickup_address']);
    $delivery_address = trim($_POST['delivery_address']);
    if (strlen($pickup_address) < 10 || strlen($delivery_address) < 10) {
    $message = "Vui lòng nhập địa chỉ đầy đủ và hợp lệ.";
}
        // Lấy tọa độ từ OpenStreetMap
    $pickupGeo = getCoordinates($pickup_address);
    $deliveryGeo = getCoordinates($delivery_address);
    if ($pickupGeo && $deliveryGeo) {

    $distance_km = calculateDistance(
        $pickupGeo['lat'],
        $pickupGeo['lng'],
        $deliveryGeo['lat'],
        $deliveryGeo['lng']
    );

}
    if (!$pickupGeo) {
    $message = "Địa chỉ lấy hàng không tồn tại trên bản đồ.";
}

    if (!$deliveryGeo) {
    $message = "Địa chỉ giao hàng không tồn tại trên bản đồ.";
}
    $weight           = (float) $_POST['weight'];
    if ($weight <= 0 || $weight > 800) {
    $message = "Khối lượng hàng phải từ 0.1kg đến 800kg.";
}
    $package_type     = trim($_POST['package_type']);
    $note             = trim($_POST['note'] ?? '');
    $payment_method   = $_POST['payment_method'] ?? 'cod';
    // Tính phí ship theo công thức
    $base_fee   = 15000;
    $per_km     = 5000;
    $weight_fee = 0;
    if ($weight > 3) $weight_fee = ($weight - 3) * 3000;
    $type_fee = ['document'=>0,'small'=>5000,'medium'=>10000,'large'=>20000,'fragile'=>15000][$package_type] ?? 0;
    $price = $base_fee + ($per_km * $distance_km) + $weight_fee + $type_fee;
    $price = ceil($price / 1000) * 1000; // làm tròn lên 1000đ

    if (
    !empty($message) ||
    !$pickup_address ||
    !$delivery_address ||
    $distance_km <= 0
) {

    // Có lỗi -> không tạo đơn

} else {
        // Thêm cột nếu chưa có (safe migration)
        try {
            $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS distance_km DECIMAL(8,2) DEFAULT 0");
            $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS package_type VARCHAR(20) DEFAULT 'small'");
            $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS note TEXT DEFAULT NULL");
            $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_fee DECIMAL(12,2) DEFAULT 0");
            $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS fee_approved TINYINT(1) DEFAULT 0");
        } catch (Exception $e) {
    die("Lỗi tạo đơn: " . $e->getMessage());
}

        $stmt = $conn->prepare("
           INSERT INTO orders(
                customer_id,
                pickup_address,
                delivery_address,
                pickup_lat,
                pickup_lng,
                delivery_lat,
                delivery_lng,
                distance_km,
                weight,
                price,
                payment_method,
                payment_status,
                status
            )
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        try {
           $stmt->execute([
                $customer_id,
                $pickup_address,
                $delivery_address,

                $pickupGeo['lat'],
                $pickupGeo['lng'],

                $deliveryGeo['lat'],
                $deliveryGeo['lng'],

                $distance_km,

                $weight,
                $price,
                $payment_method,
                'unpaid',
                'pending'
            ]);
    $order_id = $conn->lastInsertId();
    $stmt2 = $conn->prepare("
    INSERT INTO payments(
    order_id,
    amount,
    payment_method,
    payment_status
    )
    VALUES(?,?,?,?)
    ");

   $stmt2->execute([
    $order_id,
    $price,
    $payment_method,
    'pending'
]);
logActivity(
    $conn,
    $_SESSION['user']['id'],
    $_SESSION['user']['fullname'],
    $_SESSION['user']['role'],
    'CREATE_ORDER',
    'Tạo đơn hàng #' . $order_id
);

header("Location: my-orders.php");
exit;
        } catch (Exception $e) {
            // Fallback nếu cột chưa có
            $stmt2 = $conn->prepare("INSERT INTO orders(customer_id, pickup_address, delivery_address, weight, price) VALUES(?,?,?,?,?)");
           $stmt2->execute([
    $customer_id,
    $pickup_address,
    $delivery_address,
    $weight,
    $price
]);

$order_id = $conn->lastInsertId();

logActivity(
    $conn,
    $_SESSION['user']['id'],
    $_SESSION['user']['fullname'],
    $_SESSION['user']['role'],
    'CREATE_ORDER',
    'Tạo đơn hàng #' . $order_id
);

header("Location: my-orders.php");
exit;
        }
    }
}

include "../includes/header.php";
?>

<style>
.create-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    padding-top: 28px;
    padding-bottom: 48px;
    align-items: start;
}

/* ── Form card ── */
.order-form-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    overflow: hidden;
}
.form-section {
    padding: 24px 28px;
    border-bottom: 1px solid #f3f4f6;
}
.form-section:last-child { border-bottom: none; }
.form-section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #9ca3af;
    margin-bottom: 18px;
}
.form-section-label .section-num {
    width: 20px; height: 20px;
    background: #f97316;
    color: #fff;
    border-radius: 50%;
    font-size: .65rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.fgroup { margin-bottom: 14px; }
.fgroup:last-child { margin-bottom: 0; }
.fgroup label {
    display: block;
    font-size: .8rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.fgroup label span { color: #ef4444; margin-left: 2px; }
.fgroup input, .fgroup select, .fgroup textarea {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    color: #111827;
    background: #fff;
    outline: none;
    transition: .15s;
    box-sizing: border-box;
}
.fgroup input:focus, .fgroup select:focus, .fgroup textarea:focus {
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249,115,22,.1);
}
.fgroup input::placeholder, .fgroup textarea::placeholder { color: #d1d5db; }
.fgroup textarea { resize: vertical; min-height: 70px; }

.frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* km input with icon */
.km-input-wrap { position: relative; }
.km-input-wrap input { padding-right: 48px; }
.km-unit {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: .75rem; font-weight: 700; color: #9ca3af; pointer-events: none;
}

/* package type */
.pkg-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
.pkg-opt { display: none; }
.pkg-label {
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    cursor: pointer;
    transition: .15s;
    font-size: .78rem;
    font-weight: 600;
    color: #6b7280;
}
.pkg-label .pkg-icon { font-size: 1.3rem; display: block; margin-bottom: 4px; }
.pkg-opt:checked + .pkg-label {
    border-color: #f97316;
    background: #fff8f3;
    color: #f97316;
}
.pkg-label:hover { border-color: #fed7aa; color: #f97316; }

/* form footer */
.form-footer {
    padding: 20px 28px;
    background: #fafafa;
    border-top: 1px solid #f3f4f6;
}
.btn-submit {
    width: 100%;
    padding: 13px;
    background: #f97316;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    transition: .15s;
    box-shadow: 0 4px 14px rgba(249,115,22,.3);
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit:hover { background: #ea580c; transform: translateY(-1px); }
.btn-submit svg { width: 18px; height: 18px; }

/* ── Sidebar summary ── */
.summary-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    overflow: hidden;
    position: sticky;
    top: 76px;
}
.summary-header {
    background: linear-gradient(135deg, #f97316, #ea580c);
    padding: 18px 20px;
    color: #fff;
}
.summary-header h3 { font-family: 'Space Grotesk', sans-serif; font-size: .95rem; font-weight: 700; margin: 0 0 2px; }
.summary-header p { font-size: .78rem; opacity: .85; margin: 0; }

.fee-breakdown { padding: 18px 20px; }
.fee-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: .82rem;
}
.fee-row:last-child { border-bottom: none; }
.fee-row .fee-label { color: #6b7280; display: flex; align-items: center; gap: 6px; }
.fee-row .fee-label .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.fee-row .fee-val { font-weight: 600; color: #374151; font-family: 'Space Grotesk', sans-serif; }

.fee-total {
    margin: 4px 20px 0;
    padding: 14px 16px;
    background: #fff8f3;
    border-radius: 10px;
    border: 1.5px solid #fed7aa;
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 16px;
}
.fee-total .tl { font-size: .78rem; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: .4px; }
.fee-total .tv { font-family: 'Space Grotesk', sans-serif; font-size: 1.35rem; font-weight: 800; color: #f97316; }

.rate-table { padding: 0 20px 20px; }
.rate-table h4 { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #9ca3af; margin-bottom: 10px; }
.rate-row {
    display: flex; justify-content: space-between;
    font-size: .78rem; padding: 5px 0;
    border-bottom: 1px dashed #f3f4f6;
    color: #6b7280;
}
.rate-row:last-child { border-bottom: none; }
.rate-row strong { color: #374151; }

.note-box {
    margin: 0 20px 16px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: .78rem;
    color: #1e40af;
    display: flex; gap: 7px;
}
.note-box svg { width: 14px; height: 14px; flex-shrink: 0; stroke: #3b82f6; fill: none; stroke-width: 2; margin-top: 1px; }

/* page header */
.pg-head { padding: 28px 0 20px; margin-bottom: 0; border-bottom: none; }
.pg-head h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.45rem; font-weight: 700; color: #111827; margin: 0 0 4px; }
.pg-head h2 span { color: #f97316; }
.pg-head p { color: #9ca3af; font-size: .84rem; margin: 0; }

.alert-error {
    background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3;
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: .85rem; font-weight: 600;
}

@media (max-width: 860px) {
    .create-layout { grid-template-columns: 1fr; }
    .summary-card { position: static; }
}
</style>

<div class="pg-head">
    <h2>Tạo <span>đơn hàng mới</span></h2>
    <p>Điền thông tin để tạo đơn giao hàng — giá ship được tính tự động</p>
</div>

<?php if ($message): ?>
<div class="alert-error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="create-layout">
    <!-- ── FORM ── -->
    <form method="POST" id="orderForm">
    <div class="order-form-card">

        <!-- Section 1: Địa chỉ -->
        <div class="form-section">
            <div class="form-section-label">
                <span class="section-num">1</span>
                Thông tin địa chỉ
            </div>
            <div class="fgroup">
                <label>Địa chỉ lấy hàng <span>*</span></label>
                <input type="text" name="pickup_address" id="pickup_address"
                    placeholder="VD: 123 Lê Lợi, Quận 1, TP.HCM" required
                    value="<?= htmlspecialchars($_POST['pickup_address'] ?? '') ?>">
            </div>
            <div class="fgroup">
                <label>Địa chỉ giao hàng <span>*</span></label>
                <input type="text" name="delivery_address" id="delivery_address"
                    placeholder="VD: 456 Nguyễn Huệ, Quận 1, TP.HCM" required
                    value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>">
                    <div id="address-result"
                        style="margin-top:10px;font-weight:bold">
                    </div>
            </div>
            <div class="fgroup">
                <label>Khoảng cách</label>
                <strong id="distance_result">
                    Chưa xác định
                </strong>
            </div>
        </div>

        <!-- Section 2: Hàng hóa -->
        <div class="form-section">
            <div class="form-section-label">
                <span class="section-num">2</span>
                Thông tin hàng hóa
            </div>
            <div class="fgroup">
                <label>Loại hàng hóa <span>*</span></label>
                <div class="pkg-grid">
                    <?php
                    $pkgTypes = [
                        'document' => ['icon' => '📄', 'label' => 'Tài liệu'],
                        'small'    => ['icon' => '📦', 'label' => 'Nhỏ'],
                        'medium'   => ['icon' => '🛍️', 'label' => 'Vừa'],
                        'large'    => ['icon' => '📫', 'label' => 'Lớn'],
                        'fragile'  => ['icon' => '🪴', 'label' => 'Dễ vỡ'],
                        'food'     => ['icon' => '🍱', 'label' => 'Thực phẩm'],
                    ];
                    $selectedPkg = $_POST['package_type'] ?? 'small';
                    foreach ($pkgTypes as $val => $pt): ?>
                    <div>
                        <input type="radio" name="package_type" value="<?= $val ?>"
                            id="pkg_<?= $val ?>" class="pkg-opt"
                            <?= $selectedPkg === $val ? 'checked' : '' ?>
                            onchange="calcFee()">
                        <label for="pkg_<?= $val ?>" class="pkg-label">
                            <span class="pkg-icon"><?= $pt['icon'] ?></span>
                            <?= $pt['label'] ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="frow">
                <div class="fgroup">
                    <label>Khối lượng (kg) <span>*</span></label>
                    <input type="number" name="weight" id="weight"
                        step="0.1" min="0.1" max="800"
                        placeholder="VD: 2.5" required
                        value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>"
                        oninput="calcFee()">
                </div>
                <div class="fgroup">
                    <label>Giá trị hàng (VNĐ)</label>
                    <input type="number" name="goods_value" id="goods_value"
                        step="1000" min="0"
                        placeholder="Để trống nếu không khai báo"
                        value="<?= htmlspecialchars($_POST['goods_value'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Section 3: Ghi chú -->
        <div class="form-section">
            <div class="form-section-label">
                <span class="section-num">3</span>
                Ghi chú & yêu cầu thêm
            </div>
            <div class="fgroup">
                <label>Ghi chú cho tài xế</label>
                <textarea name="note" placeholder="VD: Gọi điện trước khi giao, hàng dễ vỡ, để dưới chân cầu thang..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
            </div>
        </div>
        <!-- Section 4: Thanh toán -->
<div class="form-section">
    <div class="form-section-label">
        <span class="section-num">4</span>
        Thanh toán
    </div>

    <div class="fgroup">
        <label>Phương thức thanh toán <span>*</span></label>

        <select name="payment_method" required>
            <option value="cod"
                <?= (($_POST['payment_method'] ?? '') == 'cod') ? 'selected' : '' ?>>
                Thanh toán khi nhận hàng (COD)
            </option>

            <option value="bank_transfer"
                <?= (($_POST['payment_method'] ?? '') == 'bank_transfer') ? 'selected' : '' ?>>
                Chuyển khoản ngân hàng
            </option>
        </select>
    </div>

    <div style="margin-top:10px;padding:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:.8rem;color:#1e40af;">
        💡 Nếu chọn chuyển khoản, khách hàng có thể thanh toán trước và hệ thống sẽ lưu thông tin thanh toán cho đơn hàng.
    </div>
</div>
        <!-- Hidden price -->
        <input type="hidden" name="price" id="price_hidden" value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>">
        <div class="form-footer">
            <input type="hidden"
                    id="address_valid"
                    name="address_valid"
                    value="0">
            <input type="hidden"
                    id="distance_km"
                    name="distance_km"
                    value="0">
            <button type="submit" class="btn-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
                Tạo đơn hàng
            </button>
        </div>
    </div>
    </form>

    <!-- ── SIDEBAR SUMMARY ── -->
    <div>
        <div class="summary-card">
            <div class="summary-header">
                <h3>💰 Chi phí vận chuyển</h3>
                <p>Tính tự động theo khoảng cách & hàng hóa</p>
            </div>

            <div class="fee-breakdown">
                <div class="fee-row">
                    <span class="fee-label">
                        <span class="dot" style="background:#f97316"></span>
                        Phí cơ bản
                    </span>
                    <span class="fee-val" id="fee_base">15,000đ</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">
                        <span class="dot" style="background:#3b82f6"></span>
                        Phí khoảng cách
                    </span>
                    <span class="fee-val" id="fee_km">—</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">
                        <span class="dot" style="background:#10b981"></span>
                        Phụ phí khối lượng
                    </span>
                    <span class="fee-val" id="fee_weight">Miễn phí</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">
                        <span class="dot" style="background:#8b5cf6"></span>
                        Phụ phí loại hàng
                    </span>
                    <span class="fee-val" id="fee_type">Miễn phí</span>
                </div>
            </div>

            <div class="fee-total">
                <span class="tl">Tổng phí dự tính</span>
                <span class="tv" id="fee_total">—</span>
            </div>

            <div class="note-box">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Giá trên là ước tính. Nhân viên sẽ xem xét và xác nhận giá chính thức trước khi điều phối tài xế.
            </div>

            <div class="rate-table">
                <h4>Bảng giá tham khảo</h4>
                <div class="rate-row"><span>Phí cơ bản</span><strong>15,000đ</strong></div>
                <div class="rate-row"><span>Mỗi km</span><strong>5,000đ/km</strong></div>
                <div class="rate-row"><span>KL &gt; 3kg</span><strong>+3,000đ/kg</strong></div>
                <div class="rate-row"><span>Hàng dễ vỡ / thực phẩm</span><strong>+15,000đ</strong></div>
                <div class="rate-row"><span>Hàng lớn</span><strong>+20,000đ</strong></div>
                <div class="rate-row"><span>Hàng vừa</span><strong>+10,000đ</strong></div>
                <div class="rate-row"><span>Hàng nhỏ</span><strong>+5,000đ</strong></div>
                <div class="rate-row"><span>Tài liệu</span><strong>Miễn phí</strong></div>
            </div>
        </div>
    </div>
</div>

<script>
const TYPE_FEES = {
    document: 0,
    small:    5000,
    medium:   10000,
    large:    20000,
    fragile:  15000,
    food:     15000,
};

function fmt(n) {
    return n.toLocaleString('vi-VN') + 'đ';
}

function calcFee() {
    const km     = parseFloat(document.getElementById('distance_km').value) || 0;
    const weight = parseFloat(document.getElementById('weight').value) || 0;
    const pkg    = document.querySelector('input[name="package_type"]:checked')?.value || 'small';

    const base       = 15000;
    const kmFee      = Math.round(km * 5000);
    const weightFee  = weight > 3 ? Math.round((weight - 3) * 3000) : 0;
    const typeFee    = TYPE_FEES[pkg] || 0;
    let   total      = base + kmFee + weightFee + typeFee;
    total = Math.ceil(total / 1000) * 1000;

    document.getElementById('fee_base').textContent   = fmt(base);
    document.getElementById('fee_km').textContent     = km > 0 ? fmt(kmFee) + ' (' + km + ' km)' : '—';
    document.getElementById('fee_weight').textContent = weightFee > 0 ? fmt(weightFee) : 'Miễn phí';
    document.getElementById('fee_type').textContent   = typeFee > 0 ? fmt(typeFee) : 'Miễn phí';

    if (km > 0) {
        document.getElementById('fee_total').textContent = fmt(total);
        document.getElementById('price_hidden').value = total;
    } else {
        document.getElementById('fee_total').textContent = '—';
        document.getElementById('price_hidden').value = 0;
    }
}

// init on load
document.addEventListener('DOMContentLoaded', calcFee);
document.querySelectorAll('input[name="package_type"]').forEach(r => r.addEventListener('change', calcFee));
document.getElementById("pickup_address").addEventListener("blur",validateAddresses);

document.getElementById("delivery_address").addEventListener("blur",validateAddresses);
function calculateDistance(
    lat1,
    lon1,
    lat2,
    lon2
) {
    const R = 6371;

    const dLat =
        (lat2 - lat1) * Math.PI / 180;

    const dLon =
        (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat / 2) *
        Math.sin(dLat / 2) +

        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *

        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);

    const c =
        2 * Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a)
        );

    return R * c;
}
async function validateAddresses() {

    const pickup =
        document.getElementById(
            "pickup_address"
        ).value.trim();

    const delivery =
        document.getElementById(
            "delivery_address"
        ).value.trim();

    const result =
        document.getElementById(
            "address-result"
        );

    if (!pickup || !delivery) {
        return;
    }

    try {

        const r1 = await fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" +
            encodeURIComponent(pickup)
        );

        const d1 = await r1.json();

        const r2 = await fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" +
            encodeURIComponent(delivery)
        );

        const d2 = await r2.json();

        if (d1.length > 0 && d2.length > 0) {

            const lat1 =
                parseFloat(d1[0].lat);

            const lon1 =
                parseFloat(d1[0].lon);

            const lat2 =
                parseFloat(d2[0].lat);

            const lon2 =
                parseFloat(d2[0].lon);

            const km =
                calculateDistance(
                    lat1,
                    lon1,
                    lat2,
                    lon2
                ).toFixed(2);

            result.innerHTML =
                "✅ Địa chỉ hợp lệ";

            document.getElementById(
                "distance_result"
            ).innerHTML =
                km + " km";

            document.getElementById(
                "distance_km"
            ).value = km;

            document.getElementById(
                "address_valid"
            ).value = 1;

            calcFee();

        } else {

            result.innerHTML =
                "❌ Địa chỉ không hợp lệ";

            document.getElementById(
                "distance_result"
            ).innerHTML =
                "Không xác định";

            document.getElementById(
                "distance_km"
            ).value = 0;

            document.getElementById(
                "address_valid"
            ).value = 0;
        }

    } catch (e) {

        result.innerHTML =
            "❌ Lỗi kết nối OpenStreetMap";

        document.getElementById(
            "address_valid"
        ).value = 0;
    }
}

</script>

<?php include "../includes/footer.php"; ?>