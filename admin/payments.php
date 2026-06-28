<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('admin');

$stmt = $conn->query("
SELECT
p.*,
o.id AS order_id,
u.fullname AS customer_name
FROM payments p
JOIN orders o ON p.order_id = o.id
JOIN users u ON o.customer_id = u.id
ORDER BY p.id DESC
");

$payments = $stmt->fetchAll();

/* ===== STATS ===== */
$total_revenue = 0;
$total_paid = 0;
$total_pending = 0;

foreach ($payments as $p) {
    if ($p['payment_status'] === 'paid') {
        $total_revenue += $p['amount'];
        $total_paid++;
    } else {
        $total_pending++;
    }
}
?>

<style>
.container-pay { padding:20px; }

.cards {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
    margin-bottom:20px;
}

.card {
    background:#fff;
    border-radius:12px;
    padding:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.card h3 { margin:0; font-size:14px; color:#666; }
.card .value { font-size:20px; font-weight:bold; margin-top:5px; }

table {
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

th {
    background:#f5f6f8;
    text-align:left;
    padding:12px;
    font-size:13px;
}

td {
    padding:12px;
    border-top:1px solid #eee;
    font-size:14px;
}

.badge {
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

.paid { background:#d1fae5; color:#059669; }
.pending { background:#fef3c7; color:#d97706; }
.failed { background:#fee2e2; color:#dc2626; }
</style>

<div class="container-pay">

<h2>💳 Quản lý thanh toán</h2>

<!-- STATS -->
<div class="cards">
    <div class="card">
        <h3>Tổng doanh thu</h3>
        <div class="value"><?= number_format($total_revenue) ?>đ</div>
    </div>

    <div class="card">
        <h3>Đã thanh toán</h3>
        <div class="value"><?= $total_paid ?></div>
    </div>

    <div class="card">
        <h3>Chờ thanh toán</h3>
        <div class="value"><?= $total_pending ?></div>
    </div>
</div>

<!-- TABLE -->
<table>
    <thead>
        <tr>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Số tiền</th>
            <th>Phương thức</th>
            <th>Trạng thái</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td>#<?= $p['order_id'] ?></td>

            <td><?= htmlspecialchars($p['customer_name']) ?></td>

            <td><?= number_format($p['amount']) ?>đ</td>

            <td><?= htmlspecialchars($p['payment_method'] ?? 'N/A') ?></td>

           <td>
<?php
$status = $p['payment_status'] ?? 'pending';

if ($status === 'paid') {
    echo '<span class="badge paid">Đã thanh toán</span>';
}
elseif ($status === 'pending' || $status === 'cod') {
    echo '<span class="badge pending">Chờ thanh toán</span>';
}
elseif ($status === 'failed') {
    echo '<span class="badge failed">Thất bại</span>';
}
else {
    echo '<span class="badge pending">Chờ xử lý</span>';
}
?>
</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>