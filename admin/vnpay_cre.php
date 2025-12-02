<?php
// ==== VNPAY (SANDBOX) - 1 FILE VERSION, KÝ GIỐNG CODE CŨ (hash trên chuỗi đã urlencode) ====
// Lưu file UTF-8 (không BOM), KHÔNG có ký tự thừa trước/ sau thẻ PHP.

include_once __DIR__ . '/../lib/session.php';
include_once __DIR__ . '/../lib/database.php';

Session::init();

function getRandomNumber($length) {
    $characters = '0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function generateVNPayUrl($amount, $orderInfo) {
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    $vnp_Version = "2.1.0";
    $vnp_Command = "pay";
    $orderType   = "other";
    $bankCode    = "NCB";
    $vnp_TmnCode = "0S7T01T8"; // mã sandbox bạn đang dùng
    $vnp_TxnRef  = getRandomNumber(8);
    $vnp_IpAddr  = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // ====== THAM SỐ THEO ĐÚNG CÁCH CŨ CỦA BẠN ======
    $vnp_Params = [
        "vnp_Version"   => $vnp_Version,
        "vnp_Command"   => $vnp_Command,
        "vnp_TmnCode"   => $vnp_TmnCode,
        "vnp_Amount"    => ((int)round($amount)) * 100, // *100 bắt buộc
        "vnp_CurrCode"  => "VND",
        "vnp_BankCode"  => $bankCode,
        "vnp_TxnRef"    => $vnp_TxnRef,
        "vnp_OrderInfo" => $orderInfo,
        "vnp_OrderType" => $orderType,
        "vnp_Locale"    => "vn",
        // TRẢ VỀ THEO ĐÚNG URL BẠN ĐÃ XÀI ỔN TRƯỚC ĐÂY:
        "vnp_ReturnUrl" => "http://localhost/restaurant/vnpay_return.php",
        "vnp_IpAddr"    => $vnp_IpAddr,
        "vnp_CreateDate"=> date("YmdHis"),
        "vnp_ExpireDate"=> date("YmdHis", strtotime("+15 minutes")),
    ];

    // Sắp xếp & build chuỗi theo CÁCH CŨ (hash trên chuỗi đã urlencode)
    ksort($vnp_Params);

    $hashData = "";
    $query    = "";
    foreach ($vnp_Params as $key => $value) {
        if ($value === "" || $value === null) continue;
        $hashData .= $key . "=" . urlencode($value) . "&";     // <<< GIỮ urlencode NHƯ CODE CŨ
        $query    .= urlencode($key) . "=" . urlencode($value) . "&";
    }
    $hashData = rtrim($hashData, '&');
    $query    = rtrim($query, '&');

    // Secret sandbox ĐANG DÙNG theo file cũ của bạn (không đổi)
    $secretKey = "BEZLUPOPOTXTDYZHCBGDJBHFJPBLSARL";
    $vnp_SecureHash = hash_hmac("sha512", $hashData, $secretKey);

    $paymentUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

    // Redirect
    header("Location: " . $paymentUrl);
    exit();
}

// ====== NHẬN hopdong_id ĐỂ TÍNH TIỀN (nếu có), GIỮ NGUYÊN PHONG CÁCH CODE CŨ ======
if (isset($_GET['hopdong_id'])) {
    $booking_id = (int)$_GET['hopdong_id'];
    if ($booking_id > 0) {
        include_once __DIR__ . '/lib/database.php';
        $db = new Database();
        $total = 0;

        if (isset($db->link)) {
            // Tổng tiền: ưu tiên chi tiết, fallback h.thanhtien
            $sql = "
                SELECT COALESCE(
                    (SELECT SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)))
                     FROM hopdong_chitiet c
                     JOIN monan m ON m.id_mon = c.monan_id
                     WHERE c.hopdong_id = h.id),
                    h.thanhtien, 0
                ) AS total
                FROM hopdong h
                WHERE h.id = {$booking_id}
                LIMIT 1
            ";
            $rs = $db->select($sql);
            if ($rs && $rs->num_rows > 0) {
                $row = $rs->fetch_assoc();
                $total = (float)($row['total'] ?? 0);
            }
        }

        // Loại thanh toán: default full; ?type=deposit thì 20%
        $type = (isset($_GET['type']) && $_GET['type'] === 'deposit') ? 'deposit' : 'full';
        $amount_to_pay = ($type === 'deposit') ? (int)round($total * 0.20) : (int)round($total);
        if ($amount_to_pay <= 0) { $amount_to_pay = 30000; } // tối thiểu như code cũ

        // Set session (để return đọc nếu bạn cần)
        Session::set('order_booking_id',   $booking_id);
        Session::set('order_amount',       $amount_to_pay);
        Session::set('order_payment_type', $type);
        Session::set('order_data_deposit', $amount_to_pay); // tương thích code cũ

        $info = ($type==='deposit')
              ? "Thanh toán đặt cọc đơn đặt bàn #{$booking_id}"
              : "Thanh toán toàn bộ đơn đặt bàn #{$booking_id}";
        Session::set('order_payment_info', $info);
    }
}

// ====== LẤY SỐ TIỀN & INFO TỪ SESSION (giống code cũ) ======
$deposit_amount = Session::get('order_data_deposit');
$order_info     = Session::get('order_payment_info');

// Fallback: nếu gọi dạng ?mode=datban thì mặc định 30k
if (!$deposit_amount && isset($_GET['mode']) && $_GET['mode'] === 'datban') {
    $deposit_amount = 30000;
    $order_info     = "Thanh toán tiền cọc đặt bàn";
}

if (!$deposit_amount) {
    echo "Thiếu thông tin số tiền!";
    exit();
}

// Gọi hàm tạo URL & redirect (ký giống code cũ)
generateVNPayUrl($deposit_amount, $order_info);
