<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('customer');

if (!isset($_GET['id'])) {
 header("Location: my-orders.php");
 exit;
}

$order_id = (int)$_GET['id'];
$customer_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
 SELECT
orders.*,
driver.fullname AS driver_name,
driver.phone AS driver_phone,
driver.latitude,
driver.longitude,
staff.fullname AS staff_name
 FROM orders
 LEFT JOIN users AS driver ON orders.driver_id = driver.id
 LEFT JOIN users AS staff ON orders.staff_id = staff.id
 WHERE orders.id=? AND orders.customer_id=?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
 header("Location: my-orders.php");
 exit;
}
$stmtPay = $conn->prepare("
    SELECT *
    FROM payments
    WHERE order_id = ?
    LIMIT 1
");

$stmtPay->execute([$order['id']]);

$payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

// Timeline steps based on status
$steps = [
 'pending' =>['label' =>'Đặt đơn', 'icon' =>'', 'desc' =>'Đơn hàng đã được tạo thành công'],
 'assigned' =>['label' =>'Đã phân tài xế', 'icon' =>'', 'desc' =>'Tài xế được điều phối'],
 'picked_up' =>['label' =>'Đã lấy hàng', 'icon' =>'', 'desc' =>'Tài xế đã lấy hàng tại điểm lấy'],
 'delivering' =>['label' =>'Đang giao', 'icon' =>'', 'desc' =>'Tài xế đang trên đường giao'],
 'completed' =>['label' =>'Hoàn thành', 'icon' =>'', 'desc' =>'Đơn hàng đã giao thành công'],
];
$stepKeys = array_keys($steps);
$currentIdx = array_search($order['status'], $stepKeys);
if ($order['status'] === 'cancelled') $currentIdx = -1;

include "../includes/header.php";
?>
<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link rel="stylesheet"
href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css"/>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<style>
.detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
.info-section { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; margin-bottom: 16px; }
.info-section-title { padding: 14px 20px; border-bottom: 1px solid var(--border); background: var(--surface-2); font-size: .82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; }
.info-row { display: flex; padding: 13px 20px; border-bottom: 1px solid var(--border); }
.info-row:last-child { border-bottom: none; }
.info-key { font-size: .8rem; font-weight: 600; color: var(--text-muted); width: 160px; flex-shrink: 0; display: flex; align-items: center; gap: 6px; }
.info-val { font-size: .875rem; color: var(--text); font-weight: 500; }
.timeline { padding: 20px; }
.tl-step { display: flex; gap: 14px; position: relative; padding-bottom: 24px; }
.tl-step:last-child { padding-bottom: 0; }
.tl-step::before { content: ''; position: absolute; left: 17px; top: 36px; bottom: 0; width: 2px; background: var(--border); }
.tl-step:last-child::before { display: none; }
.tl-dot { width: 36px; height: 36px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; z-index: 1; transition: var(--transition); }
.tl-dot.done { background: #ECFDF5; border-color: #10B981; }
.tl-dot.active { background: var(--role-light); border-color: var(--role-color); box-shadow: 0 0 0 4px var(--role-glow); }
.tl-text .tl-label { font-size: .85rem; font-weight: 700; color: var(--text-muted); }
.tl-text .tl-label.done { color: #10B981; }
.tl-text .tl-label.active { color: var(--role-color); }
.tl-text .tl-desc { font-size: .78rem; color: var(--text-light); margin-top: 2px; }
.driver-card { background: linear-gradient(135deg, var(--role-light), var(--surface)); border: 1.5px solid var(--role-color); border-radius: var(--radius); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
.driver-avatar { width: 46px; height: 46px; border-radius: 50%; background: var(--role-color); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; font-family: 'Space Grotesk', sans-serif; }
@media(max-width:860px){ .detail-grid { grid-template-columns: 1fr; } }
.leaflet-routing-container{
    display:none;
}
</style>
<div class="page-header">
 <div>
 <h2>Đơn hàng <span>#<?= $order['id'] ?></span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">
 Tạo lúc <?= date('H:i d/m/Y', strtotime($order['created_at'])) ?>
 </p>
 </div>
 <div style="display:flex;gap:8px;align-items:center">
 <span class="badge badge-<?= $order['status'] ?>" style="font-size:.82rem;padding:6px 14px"><?= $order['status'] ?></span>
 <a href="my-orders.php" class="btn btn-ghost btn-sm">← Quay lại</a>
 </div>
</div>

<div class="detail-grid">
 <!-- Left column -->
 <div>
 <!-- Order info -->
 <div class="info-section">
 <div class="info-section-title">Thông tin đơn hàng</div>
 <div class="info-row">
 <span class="info-key">Điểm lấy hàng</span>
 <span class="info-val"><?= htmlspecialchars($order['pickup_address']) ?></span>
 </div>
 <div class="info-row">
 <span class="info-key">Điểm giao hàng</span>
 <span class="info-val"><?= htmlspecialchars($order['delivery_address']) ?></span>
 </div>
 <div class="info-row">
 <span class="info-key">Khối lượng</span>
 <span class="info-val"><?= $order['weight'] ?>kg</span>
 </div>
 <div class="info-row">
 <span class="info-key">Phí vận chuyển</span>
 <span class="info-val" style="font-family:'Space Grotesk',sans-serif;font-size:1rem;font-weight:700;color:var(--role-color)">
 <?= number_format($order['price']) ?>đ
 </span>
 </div>
 <div class="info-row">
 <span class="info-key">Ngày tạo</span>
 <span class="info-val"><?= date('H:i – d/m/Y', strtotime($order['created_at'])) ?></span>
 </div>
 </div>

 <!-- Staff / Driver info -->
 <div class="info-section">
 <div class="info-section-title">Thông tin vận chuyển</div>
 <div class="info-row">
 <span class="info-key">Nhân viên</span>
 <span class="info-val">
 <?php if ($order['staff_name']): ?>
 <?= htmlspecialchars($order['staff_name']) ?>
 <?php else: ?>
 <span style="color:var(--text-light);font-style:italic">Chưa điều phối</span>
 <?php endif; ?>
 </span>
 </div>
 <div class="info-row">
 <span class="info-key">Tài xế</span>
 <span class="info-val">
 <?php if ($order['driver_name']): ?>
 <?= htmlspecialchars($order['driver_name']) ?>
 <?php else: ?>
 <span style="color:var(--text-light);font-style:italic">Chưa phân công</span>
 <?php endif; ?>
 </span>
 </div>
 <div class="info-row">
 <span class="info-key">SĐT tài xế</span>
 <span class="info-val">
 <?php if ($order['driver_phone']): ?>
 <a href="tel:<?= $order['driver_phone'] ?>" style="font-weight:700"><?= htmlspecialchars($order['driver_phone']) ?></a>
 <?php else: ?>
 <span style="color:var(--text-light);font-style:italic">Chưa cập nhật</span>
 <?php endif; ?>
 </span>
 </div>
 </div>
<div class="info-section">

    <div class="info-section-title">
        Vị trí tài xế
    </div>

    <div id="map"
         style="
         height:350px;
         width:100%;
         ">
    </div>
<a class="btn btn-ghost btn-full"
   target="_blank"
   href="https://www.google.com/maps/dir/?api=1&origin=<?= urlencode($order['pickup_address']) ?>&destination=<?= urlencode($order['delivery_address']) ?>"
   style="margin-bottom:10px">
   🗺 Xem vị trí đơn hàng
</a>
</div>
 <?php if ($order['driver_name'] && in_array($order['status'], ['assigned','picked_up','delivering'])): ?>
 <div class="driver-card">
 <div class="driver-avatar"><?= mb_substr($order['driver_name'], 0, 1) ?></div>
 <div>
 <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($order['driver_name']) ?></div>
 <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px">Tài xế đang giao đơn của bạn</div>
 <?php if ($order['driver_phone']): ?>
 <a href="tel:<?= $order['driver_phone'] ?>" style="font-size:.8rem;margin-top:4px;display:inline-block"><?= $order['driver_phone'] ?></a>
 <?php endif; ?>
 </div>
 </div>
 <?php endif; ?>
 </div>

 <!-- Right column: Timeline -->
 <div>
    <div class="info-section">
    <div class="info-section-title">
        Thanh toán
    </div>

    <div style="padding:20px">

    <?php if($payment): ?>

        <div style="margin-bottom:10px">
            <strong>Số tiền:</strong>
            <?= number_format($payment['amount']) ?> VNĐ
        </div>

       <div style="margin-bottom:10px">
    <strong>Phương thức:</strong>

    <?php
    if($payment['payment_method'] == 'cod'){
        echo "Thanh toán khi nhận hàng (COD)";
    }elseif($payment['payment_method'] == 'bank_transfer'){
        echo "Chuyển khoản ngân hàng";
    }else{
        echo $payment['payment_method'];
    }
    ?>
</div>

        <div>
            <strong>Trạng thái:</strong>

            <?php if($payment['payment_status'] == 'paid'): ?>
    <span style="
        background:#DCFCE7;
        color:#166534;
        padding:4px 10px;
        border-radius:20px;
        font-weight:700;
    ">
        Đã thanh toán
    </span>
<?php else: ?>
    <span style="
        background:#FEE2E2;
        color:#991B1B;
        padding:4px 10px;
        border-radius:20px;
        font-weight:700;
    ">
        Chưa thanh toán
    </span>
<?php endif; ?>
        </div>

    <?php else: ?>

        <div style="color:#6B7280">
            Chưa có thông tin thanh toán
        </div>

    <?php endif; ?>

    </div>
</div>
</div>
 <div class="info-section">
 <div class="info-section-title">Tiến trình đơn hàng</div>

 <?php if ($order['status'] === 'cancelled'): ?>
 <div style="padding:24px;text-align:center">
 <div style="font-size:2.5rem;margin-bottom:10px"></div>
 <div style="font-weight:700;color:#EF4444;font-size:.95rem">Đơn hàng đã bị hủy</div>
 <div style="font-size:.8rem;color:var(--text-muted);margin-top:6px">Liên hệ hỗ trợ nếu cần thêm thông tin</div>
 </div>
 <?php else: ?>
 <div class="timeline">
 <?php foreach ($steps as $key =>$step):
 $idx = array_search($key, $stepKeys);
 $isDone = $idx < $currentIdx;
 $isActive = $idx === $currentIdx;
 $dotClass = $isDone ? 'done' : ($isActive ? 'active' : '');
 $labelClass = $isDone ? 'done' : ($isActive ? 'active' : '');
 ?>
 <div class="tl-step">
 <div class="tl-dot <?= $dotClass ?>"><?= $isDone ? '' : $step['icon'] ?></div>
 <div class="tl-text" style="padding-top:6px">
 <div class="tl-label <?= $labelClass ?>"><?= $step['label'] ?></div>
 <?php if ($isActive): ?>
 <div class="tl-desc" style="color:var(--role-color);font-weight:600"><?= $step['desc'] ?></div>
 <?php elseif ($isDone): ?>
 <div class="tl-desc">Đã hoàn tất</div>
 <?php else: ?>
 <div class="tl-desc">Chưa đến bước này</div>
 <?php endif; ?>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 <?php endif; ?>
 </div>

 <a href="my-orders.php" class="btn btn-ghost btn-full" style="text-align:center">← Xem tất cả đơn hàng</a>
 </div>
</div>
<script>

let map;

let driverMarker;
let pickupMarker;
let deliveryMarker;

<?php if($order['driver_id']): ?>

// Khởi tạo bản đồ
map = L.map('map').setView(
[
<?= $order['latitude'] ?: 13.782 ?>,
<?= $order['longitude'] ?: 109.219 ?>
],
15
);

// OpenStreetMap
L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
{
    maxZoom:19,
    attribution:'© OpenStreetMap'
}
).addTo(map);

// =====================
// Điểm lấy hàng
// =====================
pickupMarker = L.marker([
<?= $order['pickup_lat'] ?>,
<?= $order['pickup_lng'] ?>
])
.addTo(map)
.bindPopup("📦 Điểm lấy hàng");

// =====================
// Điểm giao hàng
// =====================
deliveryMarker = L.marker([
<?= $order['delivery_lat'] ?>,
<?= $order['delivery_lng'] ?>
])
.addTo(map)
.bindPopup("🏠 Điểm giao hàng");

// =====================
// Tài xế
// =====================
driverMarker = L.marker([
<?= $order['latitude'] ?: 13.782 ?>,
<?= $order['longitude'] ?: 109.219 ?>
])
.addTo(map)
.bindPopup("🛵 Tài xế");

L.Routing.control({

    waypoints:[
        L.latLng(
            <?= $order['pickup_lat'] ?>,
            <?= $order['pickup_lng'] ?>
        ),

        L.latLng(
            <?= $order['delivery_lat'] ?>,
            <?= $order['delivery_lng'] ?>
        )
    ],

    lineOptions:{
        styles:[
            {
                color:"#2563EB",
                weight:6
            }
        ]
    },

    createMarker:function(){
        return null;
    },

    addWaypoints:false,
    draggableWaypoints:false,
    fitSelectedRoutes:true,
    routeWhileDragging:false

}).addTo(map);
// Zoom để thấy cả 3 điểm
let group = L.featureGroup([
pickupMarker,
deliveryMarker,
driverMarker
]);

map.fitBounds(group.getBounds(),{
padding:[50,50]
});

// =====================
// Cập nhật vị trí tài xế
// =====================
function updateDriver(){

fetch("get-driver-location.php?order_id=<?= $order['id'] ?>")

.then(res=>res.json())

.then(data=>{

if(!data.latitude) return;

let lat=parseFloat(data.latitude);
let lng=parseFloat(data.longitude);

driverMarker.setLatLng([lat,lng]);

});

}

updateDriver();

setInterval(updateDriver,5000);

<?php endif; ?>
</script>
<?php include "../includes/footer.php"; ?>
