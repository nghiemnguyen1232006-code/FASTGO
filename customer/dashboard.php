<?php
require "../includes/auth.php";
require "../includes/role.php";
require "../includes/db.php";
date_default_timezone_set('Asia/Ho_Chi_Minh');
checkRole('customer');

$customer_id = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'];

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=?");
$stmt->execute([$customer_id]);
$total = $stmt->fetchColumn();

$stmt2 = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND status='completed'");
$stmt2->execute([$customer_id]);
$completed = $stmt2->fetchColumn();

$stmt3 = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND status='pending'");
$stmt3->execute([$customer_id]);
$pending = $stmt3->fetchColumn();

$stmt4 = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND status IN ('delivering','picked_up','assigned')");
$stmt4->execute([$customer_id]);
$ongoing = $stmt4->fetchColumn();

$stmt5 = $conn->prepare("SELECT COALESCE(SUM(price),0) FROM orders WHERE customer_id=? AND status='completed'");
$stmt5->execute([$customer_id]);
$spent = $stmt5->fetchColumn();

$stmt6 = $conn->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 5");
$stmt6->execute([$customer_id]);
$recent = $stmt6->fetchAll(PDO::FETCH_ASSOC);

$hour = (int)date('H');
$greet = $hour < 12 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');
$firstName = explode(' ', trim($fullname));
$firstName = end($firstName);

include "../includes/header.php";
?>

<style>
/* ── Dashboard greeting ── */
.dash-hero {
    background: linear-gradient(135deg, #fff8f3 0%, #fff 60%);
    border: 1px solid #fed7aa;
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    background: radial-gradient(circle, rgba(249,115,22,.12) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero-left h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 4px;
    font-family: 'Space Grotesk', sans-serif;
}
.dash-hero-left h1 span { color: #f97316; }
.dash-hero-left p { color: #6b7280; font-size: .85rem; margin: 0; }
.dash-hero .btn-primary {
    background: #f97316;
    color: #fff;
    border-radius: 10px;
    padding: 11px 22px;
    font-weight: 700;
    font-size: .88rem;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(249,115,22,.35);
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    transition: .15s;
}
.dash-hero .btn-primary:hover { background: #ea580c; transform: translateY(-1px); opacity:1; }
.dash-hero .btn-primary svg { width:16px; height:16px; }

/* ── Stats grid ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 18px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    transition: .2s;
    position: relative;
    overflow: hidden;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.08); }
.stat-dot {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
}
.stat-dot svg { width: 18px; height: 18px; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
.stat-dot.orange { background: #fff7ed; }
.stat-dot.orange svg { stroke: #f97316; }
.stat-dot.green  { background: #f0fdf4; }
.stat-dot.green svg  { stroke: #10b981; }
.stat-dot.blue   { background: #eff6ff; }
.stat-dot.blue svg   { stroke: #3b82f6; }
.stat-dot.yellow { background: #fffbeb; }
.stat-dot.yellow svg { stroke: #f59e0b; }
.stat-dot.purple { background: #f5f3ff; }
.stat-dot.purple svg { stroke: #8b5cf6; }
.stat-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: #9ca3af; margin-bottom: 3px; }
.stat-value { font-family: 'Space Grotesk', sans-serif; font-size: 1.75rem; font-weight: 700; color: #111827; line-height: 1; }
.stat-sub   { font-size: .72rem; color: #d1d5db; margin-top: 4px; }

/* ── Shortcut cards ── */
.shortcuts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
.shortcut-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    padding: 24px 20px;
    text-align: center;
    text-decoration: none;
    color: #111827;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    transition: .2s;
    font-weight: 600;
    font-size: .9rem;
}
.shortcut-card:hover {
    border-color: #f97316;
    background: #fff8f3;
    color: #f97316;
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(249,115,22,.12);
    opacity: 1;
}
.shortcut-icon {
    width: 52px; height: 52px;
    background: #fff7ed;
    border: 1.5px solid #fed7aa;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: .2s;
}
.shortcut-card:hover .shortcut-icon { background: #f97316; border-color: #f97316; }
.shortcut-icon svg { width: 22px; height: 22px; stroke: #f97316; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: .2s; }
.shortcut-card:hover .shortcut-icon svg { stroke: #fff; }

/* ── Recent orders ── */
.section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.section-head h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: .95rem; font-weight: 700; color: #111827; margin: 0;
}
.section-head a {
    font-size: .8rem; color: #f97316; font-weight: 600; text-decoration: none;
}
.section-head a:hover { opacity: .75; }

.orders-table-wrap {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.orders-table-wrap table { width: 100%; border-collapse: collapse; }
.orders-table-wrap thead th {
    background: #f9fafb;
    color: #6b7280;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    padding: 11px 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.orders-table-wrap tbody td {
    padding: 13px 16px;
    font-size: .855rem;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.orders-table-wrap tbody tr:last-child td { border-bottom: none; }
.orders-table-wrap tbody tr:hover td { background: #fafafa; }

.order-id { font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: #f97316 !important; }
.order-price { font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: #111827 !important; }
.order-addr { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Badges */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.status-badge::before {
    content: ''; width: 5px; height: 5px;
    border-radius: 50%; background: currentColor; flex-shrink: 0;
}
.s-pending    { background: #fffbeb; color: #92400e; }
.s-assigned   { background: #eff6ff; color: #1e40af; }
.s-picked_up  { background: #f5f3ff; color: #5b21b6; }
.s-delivering { background: #ecfdf5; color: #065f46; }
.s-completed  { background: #f0fdf4; color: #14532d; }
.s-cancelled  { background: #fff1f2; color: #9f1239; }

.btn-view-sm {
    padding: 5px 13px;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: .75rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    transition: .15s;
    white-space: nowrap;
}
.btn-view-sm:hover { border-color: #f97316; color: #f97316; background: #fff8f3; opacity: 1; }

/* Empty */
.empty-box {
    text-align: center; padding: 48px 24px;
    color: #9ca3af;
}
.empty-box .ei {
    width: 56px; height: 56px; background: #f3f4f6; border-radius: 50%;
    margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;
}
.empty-box .ei svg { width: 24px; height: 24px; stroke: #d1d5db; fill: none; stroke-width: 1.5; }
.empty-box p { font-size: .88rem; margin-bottom: 16px; }

/* Responsive */
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .shortcuts  { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .shortcuts  { grid-template-columns: 1fr 1fr; }
    .dash-hero { flex-direction: column; align-items: flex-start; }
}
</style>

<!-- Hero greeting -->
<div class="dash-hero">
    <div class="dash-hero-left">
        <h1><?= $greet ?>, <span><?= htmlspecialchars($firstName) ?>!</span></h1>
        <p>Theo dõi và quản lý đơn hàng của bạn</p>
    </div>
    <a href="create-order.php" class="btn-primary">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo đơn mới
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-dot orange">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-label">Tổng đơn hàng</div>
        <div class="stat-value"><?= number_format($total) ?></div>
        <div class="stat-sub">Tất cả thời gian</div>
    </div>
    <div class="stat-card">
        <div class="stat-dot green">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-label">Hoàn thành</div>
        <div class="stat-value" style="color:#10b981"><?= number_format($completed) ?></div>
        <div class="stat-sub">Đã giao thành công</div>
    </div>
    <div class="stat-card">
        <div class="stat-dot blue">
            <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="m16 8 4-1 3 3-1 2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div class="stat-label">Đang giao</div>
        <div class="stat-value" style="color:#3b82f6"><?= number_format($ongoing) ?></div>
        <div class="stat-sub">Đơn đang vận chuyển</div>
    </div>
    <div class="stat-card">
        <div class="stat-dot yellow">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-label">Chờ xử lý</div>
        <div class="stat-value" style="color:#f59e0b"><?= number_format($pending) ?></div>
        <div class="stat-sub">Chờ điều phối tài xế</div>
    </div>
    <div class="stat-card">
        <div class="stat-dot purple">
            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-label">Đã chi tiêu</div>
        <div class="stat-value" style="color:#8b5cf6;font-size:1.3rem"><?= number_format($spent) ?>đ</div>
        <div class="stat-sub">Tổng phí vận chuyển</div>
    </div>
</div>

<!-- Shortcuts -->
<div class="shortcuts">
    <a href="create-order.php" class="shortcut-card">
        <div class="shortcut-icon">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        Tạo đơn hàng
    </a>
    <a href="my-orders.php" class="shortcut-card">
        <div class="shortcut-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        Đơn của tôi
    </a>
    <a href="profile.php" class="shortcut-card">
        <div class="shortcut-icon">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        Hồ sơ cá nhân
    </a>
</div>

<!-- Recent orders -->
<?php if (!empty($recent)): ?>
<div class="section-head">
    <h3>Đơn hàng gần đây</h3>
    <a href="my-orders.php">Xem tất cả →</a>
</div>
<div class="orders-table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Điểm giao</th>
                <th>Giá tiền</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $o):
                $sClass = 's-' . $o['status'];
                $sLabel = [
                    'pending'    => 'Chờ xử lý',
                    'assigned'   => 'Đã phân công',
                    'picked_up'  => 'Đã lấy hàng',
                    'delivering' => 'Đang giao',
                    'completed'  => 'Hoàn thành',
                    'cancelled'  => 'Đã hủy',
                ][$o['status']] ?? $o['status'];
            ?>
            <tr>
                <td class="order-id">#<?= $o['id'] ?></td>
                <td class="order-addr"><?= htmlspecialchars($o['delivery_address']) ?></td>
                <td class="order-price"><?= number_format($o['price']) ?>đ</td>
                <td><span class="status-badge <?= $sClass ?>"><?= $sLabel ?></span></td>
                <td style="color:#9ca3af;font-size:.8rem"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                <td><a href="order-detail.php?id=<?= $o['id'] ?>" class="btn-view-sm">Xem →</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="section-head">
    <h3>Đơn hàng gần đây</h3>
</div>
<div class="orders-table-wrap">
    <div class="empty-box">
        <div class="ei">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p>Bạn chưa có đơn hàng nào</p>
        <a href="create-order.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:9px;font-size:.85rem;font-weight:700;background:#f97316;color:#fff;text-decoration:none;box-shadow:0 4px 12px rgba(249,115,22,.3)">
            <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tạo đơn đầu tiên
        </a>
    </div>
</div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>