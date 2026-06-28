<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
require "../includes/functions.php";

checkRole('staff');

$filter  = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'assigned', 'picked_up', 'delivering', 'completed', 'cancelled'];
if (!in_array($filter, $allowed)) $filter = 'all';

if ($filter === 'all') {
    $stmt = $conn->query("
        SELECT orders.*, users.fullname AS customer_name
        FROM orders
        JOIN users ON orders.customer_id = users.id
        ORDER BY orders.id DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT orders.*, users.fullname AS customer_name
        FROM orders
        JOIN users ON orders.customer_id = users.id
        WHERE orders.status=?
        ORDER BY orders.id DESC
    ");
    $stmt->execute([$filter]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<style>
    .filter-bar {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .filter-btn {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 700;
        border: 1.5px solid var(--border);
        background: var(--surface);
        color: var(--text-muted);
        text-decoration: none;
        transition: var(--transition);
    }

    .filter-btn:hover {
        border-color: var(--role-color);
        color: var(--role-color);
        opacity: 1;
    }

    .filter-btn.active {
        background: var(--role-color);
        color: #fff;
        border-color: var(--role-color);
    }
</style>

<div class="page-header">
    <div>
        <h2>Danh sách <span>đơn hàng</span></h2>
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;"><?= count($orders) ?> đơn hàng</p>
    </div>
    <a href="dispatch.php" class="btn btn-primary">Điều phối đơn</a>
</div>

<div class="filter-bar">
    <?php
    $filters = ['all' => 'Tất cả', 'pending' => 'Chờ xử lý', 'assigned' => 'Đã phân công', 'delivering' => 'Đang giao', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];
    foreach ($filters as $val => $label):
    ?>
        <a href="?status=<?= $val ?>" class="filter-btn <?= $filter === $val ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Điểm lấy</th>
                <th>Điểm giao</th>
                <th>Giá tiền</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">Không có đơn hàng nào</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong>#<?= $o['id'] ?></strong></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['pickup_address']) ?></td>
                        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['delivery_address']) ?></td>
                        <td><strong style="font-family:'Space Grotesk',sans-serif"><?= number_format($o['price']) ?>đ</strong></td>
                        <td><span class="badge badge-<?= $o['status'] ?>"><?= getStatusText($o['status']) ?></span></td>
                        <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>