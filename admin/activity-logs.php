<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";

checkRole('admin');

$stmt = $conn->query("
    SELECT al.*, u.fullname
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
");

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header-admin.php";
?>

<h2>Nhật ký hoạt động</h2>

<table class="table">
    <thead>
        <tr>
            <th>Thời gian</th>
            <th>Người dùng</th>
            <th>Hành động</th>
            <th>Mô tả</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach($logs as $log): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
            <td><?= htmlspecialchars($log['fullname'] ?? 'Không xác định') ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><?= htmlspecialchars($log['description']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include "../includes/footer.php"; ?>