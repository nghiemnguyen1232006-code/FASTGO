<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";
require "../includes/activity_log.php";

checkRole('driver');

if (!isset($_GET['id'])) {
 header("Location: my-orders.php");
 exit;
}

$order_id = (int)$_GET['id'];
$driver_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
 SELECT
 orders.*,
 users.fullname,
 users.phone,
 users.email
 FROM orders
 JOIN users ON orders.customer_id = users.id
 WHERE orders.id=? AND orders.driver_id=?
");
$stmt->execute([$order_id, $driver_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmtPay = $conn->prepare("
SELECT *
FROM payments
WHERE order_id=?
LIMIT 1
");

$stmtPay->execute([$order_id]);

$payment = $stmtPay->fetch(PDO::FETCH_ASSOC);
$steps = [
    'assigned'   => 'Đã nhận đơn',
    'picked_up'  => 'Đã lấy hàng',
    'delivering' => 'Đang giao',
    'completed'  => 'Hoàn thành'
];

$keys = array_keys($steps);
$current = array_search($order['status'], $keys);
if (!$order) {
 header("Location: my-orders.php");
 exit;
}
logActivity(
    $conn,
    $_SESSION['user']['id'],
    $_SESSION['user']['fullname'],
    $_SESSION['user']['role'],
    'VIEW_ORDER',
    'Xem chi tiết đơn hàng #' . $order['id']
);
include "../includes/header-nhanvien.php";
?>
<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link rel="stylesheet"
href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css">

<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<style>
.detail-grid { display:grid; grid-template-columns:1fr 320px; gap:20px; }
.info-section { background:var(--surface); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; margin-bottom:16px; }
.info-section-title { padding:12px 20px; border-bottom:1px solid var(--border); background:var(--surface-2); font-size:.78rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.6px; }
.info-row { display:flex; padding:12px 20px; border-bottom:1px solid var(--border); }
.info-row:last-child { border-bottom:none; }
.info-key { font-size:.8rem; font-weight:600; color:var(--text-muted); width:150px; flex-shrink:0; }
.info-val { font-size:.875rem; color:var(--text); font-weight:500; }
.status-actions { background:var(--surface); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; }
.status-actions-title { padding:12px 20px; border-bottom:1px solid var(--border); background:var(--surface-2); font-size:.78rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.6px; }
.status-actions-body { padding:20px; }
@media(max-width:800px){ .detail-grid{ grid-template-columns:1fr; } }
.timeline{
    padding:20px;
}

.timeline-item{
    display:flex;
    gap:12px;
    position:relative;
    padding-bottom:25px;
}

.timeline-item:last-child{
    padding-bottom:0;
}

.timeline-item::before{
    content:'';
    position:absolute;
    left:11px;
    top:24px;
    width:2px;
    bottom:0;
    background:#ddd;
}

.timeline-item:last-child::before{
    display:none;
}

.timeline-dot{
    width:22px;
    height:22px;
    border-radius:50%;
    background:#ddd;
    flex-shrink:0;
}

.timeline-dot.done{
    background:#22c55e;
}

.timeline-dot.active{
    background:#3b82f6;
}

.timeline-text{
    flex:1;
}

.timeline-title{
    font-weight:700;
    font-size:.9rem;
}

.timeline-desc{
    font-size:.78rem;
    color:#6b7280;
}
</style>

<div class="page-header">
 <div>
 <h2>Chi tiết <span>đơn #<?= $order['id'] ?></span></h2>
 <p style="color:var(--text-muted);font-size:.85rem;margin:0;">
 Ngày tạo: <?= date('H:i – d/m/Y', strtotime($order['created_at'])) ?>
 </p>
 </div>
 <div style="display:flex;gap:8px;align-items:center">
 <span class="badge badge-<?= $order['status'] ?>"><?= getStatusText($order['status']) ?></span>
 <a href="my-orders.php" class="btn btn-ghost btn-sm">← Quay lại</a>
 </div>
</div>

<div class="detail-grid">
 <!-- Cột trái -->
 <div>
 <div class="info-section">
 <div class="info-section-title">Thông tin đơn hàng</div>
 <div class="info-section">

<div class="info-section-title">
Thông tin hàng hóa
</div>

<div class="info-row">
    <span class="info-key">
        Loại hàng
    </span>

    <span class="info-val">
        <?= htmlspecialchars($order['package_type']) ?>
    </span>
</div>

<div class="info-row">
    <span class="info-key">
        Khối lượng
    </span>

    <span class="info-val">
        <?= $order['weight'] ?> kg
    </span>
</div>

<div class="info-row">
    <span class="info-key">
        Khoảng cách
    </span>

    <span class="info-val">
        <?= $order['distance_km'] ?> km
    </span>
</div>

<div class="info-row">
    <span class="info-key">
        Ghi chú
    </span>

    <span class="info-val">

    <?php
    if($order['note']){
        echo nl2br(htmlspecialchars($order['note']));
    }else{
        echo "<i>Không có</i>";
    }
    ?>

    </span>
</div>

</div>
 <div class="info-section">
    <div class="info-section-title">
        Bản đồ giao hàng
    </div>

    <div id="map" style="height:400px"></div>
</div>
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
 <span class="info-key">Giá tiền</span>
 <span class="info-val" style="font-weight:700;color:var(--role-color);font-size:1rem"><?= number_format($order['price']) ?>VNĐ</span>
 </div>
 <div class="info-row">
 <span class="info-key">Trạng thái</span>
 <span class="info-val"><span class="badge badge-<?= $order['status'] ?>"><?= getStatusText($order['status']) ?></span></span>
 </div>
 </div>

 <div class="info-section">
 <div class="info-section-title">Thông tin khách hàng</div>
 <div class="info-section">

<div class="info-section-title">
Thanh toán
</div>

<div class="info-row">

<span class="info-key">
Phương thức
</span>

<span class="info-val">

<?php

if($payment){

if($payment['payment_method']=="cod")
echo "Thanh toán khi nhận hàng";

else
echo "Chuyển khoản";

}else{

echo "-";

}

?>

</span>

</div>

<div class="info-row">

<span class="info-key">
Trạng thái
</span>

<span class="info-val">

<?php

if($payment){

if($payment['payment_status']=="paid"){

?>

<span class="badge badge-completed">
Đã thanh toán
</span>

<?php

}else{

?>

<span class="badge badge-pending">
Chưa thanh toán
</span>

<?php

}

}

?>

</span>

</div>

</div>
 <div class="info-row">
 <span class="info-key">Họ tên</span>
 <span class="info-val"><?= htmlspecialchars($order['fullname']) ?></span>
 </div>
 <div class="info-row">
 <span class="info-key">Số điện thoại</span>
 <span class="info-val">
 <a href="tel:<?= $order['phone'] ?>" style="font-weight:700"><?= htmlspecialchars($order['phone']) ?></a>
 </span>
 </div>
 <div class="info-row">
 <span class="info-key">Email</span>
 <span class="info-val"><?= htmlspecialchars($order['email']) ?></span>
 </div>
 </div>
 </div>

 <!-- Cột phải: Cập nhật trạng thái -->
  <div class="status-actions">

<div class="status-actions-title">
Tiến trình đơn hàng
</div>

<div class="timeline">

<?php foreach($steps as $key=>$label):

$index=array_search($key,$keys);

$done=$index<$current;
$active=$index==$current;

?>

<div class="timeline-item">

<div class="timeline-dot
<?= $done?'done':'' ?>
<?= $active?'active':'' ?>">
</div>

<div class="timeline-text">

<div class="timeline-title">

<?= $label ?>

</div>

<div class="timeline-desc">

<?php

if($done){

echo "Đã hoàn thành";

}elseif($active){

echo "Đang thực hiện";

}else{

echo "Chưa tới";

}

?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>
 <div>
 <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
    <a

class="btn btn-ghost btn-full"

target="_blank"

href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($order['delivery_address']) ?>"

style="margin-bottom:10px">

🗺 Mở Google Maps

</a>
 <div class="status-actions">
 <div class="status-actions-title">Cập nhật trạng thái</div>
 <div class="status-actions-body">
 <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:16px;">
 Trạng thái hiện tại: <strong><?= getStatusText($order['status']) ?></strong>
 </p>
 <?php

switch($order['status']){

case "assigned":

?>

<a

href="update-status.php?id=<?= $order['id'] ?>&status=picked_up"

class="btn btn-primary btn-full">

📦 Xác nhận lấy hàng

</a>

<?php

break;

case "picked_up":

?>

<a

href="update-status.php?id=<?= $order['id'] ?>&status=delivering"

class="btn btn-primary btn-full">

🚚 Bắt đầu giao

</a>

<?php

break;

case "delivering":

?>

<a

href="update-status.php?id=<?= $order['id'] ?>&status=completed"

class="btn btn-success btn-full">

✅ Giao thành công

</a>

<?php

break;

}

?>
 </div>
 </div>
 <?php else: ?>
 <div class="status-actions">
 <div class="status-actions-title">Trạng thái</div>
 <div class="status-actions-body" style="text-align:center;padding:30px 20px">
 <div style="font-size:2rem;margin-bottom:10px"><?= $order['status'] === 'completed' ? '' : '' ?></div>
 <div style="font-weight:700;color:<?= $order['status'] === 'completed' ? '#16A34A' : '#EF4444' ?>">
 <?= getStatusText($order['status']) ?>
 </div>
 </div>
 </div>
 <?php endif; ?>
 </div>
</div>
<script>

var map = L.map('map').setView([
<?= $order['pickup_lat'] ?>,
<?= $order['pickup_lng'] ?>
],13);

L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
).addTo(map);

L.marker([
<?= $order['pickup_lat'] ?>,
<?= $order['pickup_lng'] ?>
]).addTo(map)
.bindPopup("Điểm lấy");

L.marker([
<?= $order['delivery_lat'] ?>,
<?= $order['delivery_lng'] ?>
]).addTo(map)
.bindPopup("Điểm giao");

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

createMarker:function(){
return null;
},

addWaypoints:false,
draggableWaypoints:false

}).addTo(map);
var driverMarker=
L.marker([
<?= $order['latitude'] ?>,
<?= $order['longitude'] ?>
]).addTo(map);

navigator.geolocation.watchPosition(function(pos){

fetch("update-location.php",{

method:"POST",

headers:{
"Content-Type":"application/x-www-form-urlencoded"
},

body:
"lat="+pos.coords.latitude+
"&lng="+pos.coords.longitude

});

driverMarker.setLatLng([
pos.coords.latitude,
pos.coords.longitude
]);

});
</script>
<?php include "../includes/footer.php"; ?>