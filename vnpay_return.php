<?php
// =======================
// vnpay_return.php — BẢN HOÀN CHỈNH (CÓ BOOKING-ONLY = 30K)
// =======================

include_once __DIR__ . '/lib/session.php';
include_once __DIR__ . '/lib/database.php';
// include Mon nếu muốn, nhưng không bắt buộc nữa
// @include_once __DIR__ . '/classes/Mon.php';

Session::init();
$db = new Database();
if (!isset($db->link)) {
    Session::set('toast_message', 'Không kết nối được CSDL');
    header("Location: index.php?msg=ThatBai");
    exit;
}

/* ========== 1) VERIFY HASH & KẾT QUẢ ========== */
$vnp_HashSecret  = "SLE5RRY8UJMZR2IZX1UF4JAJIFPAOKCP";
$vnp_SecureHash  = $_GET['vnp_SecureHash'] ?? '';

$inputData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) === "vnp_") $inputData[$key] = $value;
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);

$hashData = '';
foreach ($inputData as $key => $value) {
    $hashData .= $key . '=' . $value . '&';
}
$hashData  = rtrim($hashData, '&');
$secureCal = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';

if ($vnp_ResponseCode !== '00') {
    Session::set('toast_message', 'Thanh toán không thành công hoặc sai chữ ký.');
    header("Location: index.php?msg=ThatBai");
    exit;
}

/* ========== 2) LẤY SESSION & CHUẨN BỊ DỮ LIỆU ========== */
$orderData      = Session::get('order_data'); // có thể rỗng, không bắt buộc
$menu_snapshot  = Session::get('menu_snapshot'); // [{id_mon,name_mon,gia_mon,soluong,thanhtien}]
$menu_chon      = Session::get('menu_chon');     // [{id_mon,soluong,gia?}, ...]
$userId         = (int)(Session::get('id') ?? 0);
$amountPaid     = (float)(Session::get('order_amount') ?? 0);
$paymentType    = Session::get('order_payment_type') ?? 'deposit';
$booking_id     = (int)(Session::get('order_booking_id') ?? Session::get('booking_id') ?? 0);
$subtotal       = (float)(Session::get('menu_subtotal') ?? 0);

/* NEW: Nhận biết case "ĐẶT BÀN, KHÔNG CHỌN MÓN" */
$mode           = strtolower((string)(Session::get('order_mode') ?? ($_GET['mode'] ?? '')));
$isBookingOnly  = ($mode === 'datban') || (empty($menu_snapshot) && empty($menu_chon));

// Nếu cần lấy theo TxnRef, mở block dưới
// if (isset($_GET['vnp_TxnRef']) && (int)$_GET['vnp_TxnRef'] > 0) {
//     $booking_id = (int)$_GET['vnp_TxnRef'];
// }

/* Nếu vẫn chưa có booking_id, lấy gần nhất (theo code cũ của bạn) */
if ($booking_id <= 0) {
    $sqlGetMaxId = "SELECT MAX(id) AS max_id FROM hopdong";
    if ($result = $db->link->query($sqlGetMaxId)) {
        if ($row = $result->fetch_assoc()) $booking_id = (int)$row['max_id'];
    }
}

// Suy luận paymentType nếu trống: gần 20% => deposit, ngược lại full
if ($paymentType === '') {
    if ($amountPaid > 0 && $subtotal > 0 && abs($amountPaid - 0.2 * $subtotal) <= 1) {
        $paymentType = 'deposit';
    } else {
        $paymentType = 'full';
    }
}

/* ========== 3) CHECK HỢP ĐỒNG HỢP LỆ ========== */
if ($booking_id <= 0) {
    Session::set('toast_message', 'Không xác định được đơn hàng sau thanh toán.');
    header("Location: index.php?msg=ThatBai");
    exit;
}

/* ========== 4) GHI CHI TIẾT MÓN ĂN + CẬP NHẬT HỢP ĐỒNG ========== */
$tong = 0.0;
$db->link->begin_transaction();

/* XÓA CHI TIẾT CŨ (đảm bảo idempotent khi user reload) */
$del = $db->link->prepare("DELETE FROM hopdong_chitiet WHERE hopdong_id = ?");
$del->bind_param("i", $booking_id);
$del->execute();
$del->close();

if ($isBookingOnly) {
    /* === BOOKING-ONLY (Đặt bàn, không chọn món) → KHÔNG insert chi tiết, CHỐT 30K === */
    $tong = 30000.0;
} else {
    /* === CÓ MÓN → GIỮ NGUYÊN LOGIC CŨ === */
    $insSql = "INSERT INTO hopdong_chitiet (hopdong_id, monan_id, soluong, gia, thanhtien)
               VALUES (?,?,?,?,?)";
    $stmtIns = $db->link->prepare($insSql);

    // 4.1 ƯU TIÊN DÙNG menu_snapshot
    if (is_array($menu_snapshot) && !empty($menu_snapshot)) {
        foreach ($menu_snapshot as $line) {
            $mid = (int)($line['id_mon'] ?? 0);
            $qty = (int)($line['soluong'] ?? 0);
            $gia = (float)($line['gia_mon'] ?? 0);
            $tt  = (float)($line['thanhtien'] ?? ($qty * $gia));

            if ($mid <= 0 || $qty <= 0) continue;
            $tong += $tt;

            $stmtIns->bind_param("iiidd", $booking_id, $mid, $qty, $gia, $tt);
            $stmtIns->execute();
        }
    }
    // 4.2 Fallback: dùng menu_chon (giữ nguyên, không ép 30k ở đây)
    elseif (is_array($menu_chon) && !empty($menu_chon)) {
        foreach ($menu_chon as $row) {
            $mid = (int)($row['id'] ?? $row['id_mon'] ?? $row['monan_id'] ?? 0);
            $qty = (int)($row['qty'] ?? $row['so_luong'] ?? $row['soluong'] ?? 0);
            $gia = isset($row['price']) ? (float)$row['price']
                : (isset($row['gia']) ? (float)$row['gia'] : 0.0);

            if ($mid <= 0 || $qty <= 0) continue;

            $tt = $qty * $gia;
            $tong += $tt;

            $stmtIns->bind_param("iiidd", $booking_id, $mid, $qty, $gia, $tt);
            $stmtIns->execute();
        }
    }
    if (isset($stmtIns)) $stmtIns->close();

    // Fallback tổng khi có món mà chưa tính được
    if ($tong <= 0 && $subtotal > 0) $tong = $subtotal;
    if ($tong <= 0 && $amountPaid > 0) $tong = $amountPaid;
}

/* Fallback cuối cùng – đảm bảo tối thiểu 30.000 */
if ($tong <= 0) $tong = 30000.0;

/* CẬP NHẬT BẢNG hopdong */
$method = 'vnpay';
if ($paymentType === 'deposit') {
    // booking-only: nếu chưa có số thực trả thì đặt cọc = 30k
    $depositValue = ($amountPaid > 0) ? $amountPaid : 30000.0;
    $sqlUpd = "UPDATE hopdong
               SET so_tien = ?, thanhtien = ?, payment_status='deposit', payment_method=?, tinhtrang=1, updated_at = NOW()
               WHERE id = ?";
    $u = $db->link->prepare($sqlUpd);
    $u->bind_param("ddsi", $depositValue, $tong, $method, $booking_id);
    $u->execute();
    $u->close();
} else {
    // full: booking-only → full = 30k nếu không có số thực trả
    $paid = ($amountPaid > 0) ? $amountPaid : 30000.0;
    $sqlUpd = "UPDATE hopdong
               SET so_tien = ?, thanhtien = ?, payment_status='completed', payment_method=?, tinhtrang=1, updated_at = NOW()
               WHERE id = ?";
    $u = $db->link->prepare($sqlUpd);
    $u->bind_param("ddsi", $paid, $tong, $method, $booking_id);
    $u->execute();
    $u->close();
}

$db->link->commit();

/* ========== 5) DỌN SESSION ========== */
Session::set('menu_snapshot', null);
Session::set('menu_chon', null);
Session::set('menu_subtotal', null);
Session::set('order_amount', null);
Session::set('order_payment_type', null);
Session::set('order_booking_id', null);
Session::set('order_mode', null); // NEW: dọn mode

/* ========== 6) REDIRECT ========== */
Session::set('toast_message', 'Đặt bàn & thanh toán thành công!');
Session::set('toast_type', 'success');

// 1) Lấy user hiện tại từ session (ưu tiên)
$targetUserId = (int)(Session::get('id') ?? 0);

// 2) Nếu chưa có session, suy ra từ hợp đồng vừa thanh toán
if ($targetUserId <= 0 && $booking_id > 0) {
    if (isset($db->link)) {
        $q = $db->link->prepare("SELECT id_user FROM hopdong WHERE id = ? LIMIT 1");
        $q->bind_param("i", $booking_id);
        if ($q->execute()) {
            $q->bind_result($owner);
            if ($q->fetch() && (int)$owner > 0) {
                $targetUserId = (int)$owner;
            }
        }
        $q->close();
    }
}

// 3) Điều hướng
if ($targetUserId > 0) {
    header("Location: userblog.php?id={$targetUserId}");
} else {
    header("Location: index.php");
}
exit;
