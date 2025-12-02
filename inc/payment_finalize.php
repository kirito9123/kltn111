<?php
// inc/payment_finalize.php
// Ghi chi tiết món vào hopdong_chitiet (nếu chưa có), tính tổng, cập nhật hopdong (deposit/full)

require_once __DIR__ . '/../lib/database.php';
@require_once __DIR__ . '/../classes/Mon.php';

function pf_get_mon_price($id_mon) {
    if (class_exists('Mon')) {
        $Mon = new Mon();
        if (method_exists($Mon, 'getMonById')) {
            $r = $Mon->getMonById((int)$id_mon);
            if ($r && $row = $r->fetch_assoc()) {
                return (float)($row['gia_mon'] ?? 0);
            }
        }
    }
    return 0.0;
}

/**
 * finalize_payment
 * @param int    $booking_id      ID hợp đồng (hopdong.id)
 * @param string $payment_type    'full' | 'deposit'
 * @param string $payment_method  vd: 'cash' | 'vnpay' | 'momo'
 * @param float  $amount_paid     số tiền cổng thu/đã thu (nếu deposit thì chính là tiền cọc; nếu full có thể = tổng)
 * @param array  $menu_chon       mảng session menu_chon: [{id_mon, soluong, gia?}, ...]
 * @param float  $fallback_total  tổng dự phòng (nếu chưa có chi tiết)
 * @return array ['sum'=>float,'so_tien'=>float,'status'=>'deposit|completed']
 */
function finalize_payment(int $booking_id, string $payment_type, string $payment_method, float $amount_paid, array $menu_chon = [], float $fallback_total = 0.0): array {
    $db = new Database();
    if (!isset($db->link)) {
        throw new Exception('DB connection failed');
    }

    // 1) đã có chi tiết chưa?
    $hasDetail = false;
    if ($rs = $db->select("SELECT 1 FROM hopdong_chitiet WHERE hopdong_id = {$booking_id} LIMIT 1")) {
        $hasDetail = $rs->num_rows > 0;
    }

    // 2) nếu chưa có, đổ từ $menu_chon
    if (!$hasDetail && !empty($menu_chon)) {
        foreach ($menu_chon as $item) {
            $mid = (int)($item['id_mon'] ?? 0);
            $qty = (int)($item['soluong'] ?? 0);
            if ($mid <= 0 || $qty <= 0) continue;

            $gia = isset($item['gia']) && $item['gia'] !== null
                ? (float)$item['gia']
                : (float)pf_get_mon_price($mid);

            $thanhtien = $gia * $qty;
            $sqlIns = "INSERT INTO hopdong_chitiet (hopdong_id, mon_id, so_luong, don_gia, thanhtien)
                       VALUES ({$booking_id}, {$mid}, {$qty}, {$gia}, {$thanhtien})";
            $db->insert($sqlIns);
        }
    }

    // 3) tính tổng từ chi tiết
    $sum = 0.0;
    if ($rs = $db->select("SELECT SUM(thanhtien) AS tong FROM hopdong_chitiet WHERE hopdong_id = {$booking_id}")) {
        if ($row = $rs->fetch_assoc()) $sum = (float)($row['tong'] ?? 0);
    }
    if ($sum <= 0) $sum = (float)$fallback_total;      // fallback khi chưa có chi tiết
    if ($sum <= 0 && $amount_paid > 0) $sum = $amount_paid; // fallback cuối

    // 4) cập nhật hopdong theo full/deposit
    $method = mysqli_real_escape_string($db->link, $payment_method);
    if ($payment_type === 'deposit') {
        // đã thu cọc (amount_paid); tổng thực tế = $sum
        $so_tien = ($amount_paid > 0) ? $amount_paid : round($sum * 0.2);
        $status  = 'deposit';
        $sqlUp = "
            UPDATE hopdong SET
                so_tien        = {$so_tien},
                thanhtien      = {$sum},
                payment_status = 'deposit',
                payment_method = '{$method}',
                tinhtrang      = 1,
                updated_at     = NOW()
            WHERE id = {$booking_id}
            LIMIT 1
        ";
    } else {
        // thanh toán đủ: đã thu đủ
        $so_tien = ($amount_paid > 0) ? $amount_paid : $sum;
        $status  = 'completed';
        $sqlUp = "
            UPDATE hopdong SET
                so_tien        = {$so_tien},
                thanhtien      = {$sum},
                payment_status = 'completed',
                payment_method = '{$method}',
                tinhtrang      = 1,
                updated_at     = NOW()
            WHERE id = {$booking_id}
            LIMIT 1
        ";
    }
    $db->update($sqlUp);

    return ['sum' => $sum, 'so_tien' => $so_tien, 'status' => $status];
}
