<?php
/**
 * Trả về text tiếng Việt cho trạng thái đơn hàng.
 */
function getStatusText(string $status): string
{
    $map = [
        'pending'    => '⏳ Chờ xử lý',
        'assigned'   => '📋 Đã phân công',
        'picked_up'  => '📦 Đã lấy hàng',
        'delivering' => '🚚 Đang giao',
        'completed'  => '✅ Hoàn thành',
        'cancelled'  => '❌ Đã hủy',
    ];

    return $map[$status] ?? ucfirst($status);
}

/**
 * Format số tiền theo định dạng Việt Nam.
 */
function formatPrice(float $amount): string
{
    return number_format($amount, 0, ',', '.') . 'đ';
}
?>
