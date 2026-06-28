
<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../config/database.php";

checkRole('customer');

$customer_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE customer_id=?
    ORDER BY id DESC
");

$stmt->execute([$customer_id]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
include "../includes/header.php";
?>
<style>
.page-title{
    font-size:28px;
    font-weight:700;
    margin-bottom:20px;
    color:#111827;
}

.orders-card{
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 2px 12px rgba(0,0,0,.08);
}

.orders-table{
    width:100%;
    border-collapse:collapse;
}

.orders-table thead{
    background:#f97316;
    color:white;
}

.orders-table th{
    padding:15px;
    text-align:left;
}

.orders-table td{
    padding:15px;
    border-bottom:1px solid #eee;
}

.orders-table tr:hover{
    background:#fafafa;
}

.badge{
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
}

.pending{
    background:#FEF3C7;
    color:#92400E;
}

.completed{
    background:#DCFCE7;
    color:#166534;
}

.cancelled{
    background:#FEE2E2;
    color:#991B1B;
}

.btn{
    display:inline-block;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
}

.btn-view{
    background:#3B82F6;
    color:white;
}

.btn-cancel{
    background:#EF4444;
    color:white;
}

.btn-disabled{
    background:#9CA3AF;
    color:white;
    border:none;
    cursor:not-allowed;
}
</style>
<h1 class="page-title">
    📦 Đơn hàng của tôi
</h1>

<div class="orders-card">
<table class="orders-table">

    <tr>
        <th>Mã đơn</th>
        <th>Điểm lấy</th>
        <th>Điểm giao</th>
        <th>Khối lượng</th>
        <th>Giá</th>
        <th>Trạng thái</th>
    </tr>

    <?php foreach ($orders as $order): ?>

    <tr>

        <td>
            <?= $order['id'] ?>
        </td>

        <td>
            <?= $order['pickup_address'] ?>
        </td>

        <td>
            <?= $order['delivery_address'] ?>
        </td>

        <td>
            <?= $order['weight'] ?> kg
        </td>

        <td>
            <?= number_format($order['price']) ?> VNĐ
        </td>

        <td>
<?php
switch($order['status']) {
    case 'pending':
        echo '<span style="color:orange">⏳ Chờ xử lý</span>';
        break;

    case 'assigned':
        echo '<span style="color:blue">🚚 Đã phân công</span>';
        break;

    case 'delivering':
        echo '<span style="color:#06b6d4">📦 Đang giao</span>';
        break;

    case 'completed':
        echo '<span style="color:green">✅ Hoàn thành</span>';
        break;

    case 'cancelled':
        echo '<span style="color:red">❌ Đã hủy</span>';
        break;

    default:
        echo htmlspecialchars($order['status']);
}
?>
</td>

<td>

<a href="order-detail.php?id=<?= $order['id'] ?>"
   class="btn btn-view">
    Chi tiết
</a>

<br><br>

<?php if($order['status']=='pending'): ?>

    <a href="cancel-order.php?id=<?= $order['id'] ?>"
       class="btn btn-cancel"
       onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
       Hủy đơn
    </a>

<?php elseif($order['status']=='cancelled'): ?>

    <button class="btn-disabled">
        Đã hủy
    </button>

<?php elseif(
    $order['status']=='assigned' ||
    $order['status']=='delivering'
): ?>

    <button class="btn-disabled">
        Đã phân công vận chuyển
    </button>

<?php elseif($order['status']=='completed'): ?>

    <button class="btn-disabled">
        Hoàn thành
    </button>

<?php endif; ?>

</td>
    </tr>

    <?php endforeach; ?>

</table>
</div>