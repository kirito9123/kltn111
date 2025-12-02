<?php
include_once __DIR__ . '/lib/session.php';
Session::init();

if (isset($_GET['mode']) && $_GET['mode'] === 'datban') {
    Session::set('order_mode', 'datban');
}

function getRandomNumber($length)
{
    $characters = '0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function generateVNPayUrl($amount, $orderInfo)
{
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    $vnp_Version = "2.1.0";
    $vnp_Command = "pay";
    $orderType = "other";
    $bankCode = "NCB";
    //$vnp_TmnCode = "0S7T01T8";
    $vnp_TmnCode = "B92A2S3P";
    $vnp_TxnRef = getRandomNumber(8);
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

    $vnp_Params = [
        "vnp_Version" => $vnp_Version,
        "vnp_Command" => $vnp_Command,
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $amount * 100,
        "vnp_CurrCode" => "VND",
        "vnp_BankCode" => $bankCode,
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_OrderInfo" => $orderInfo,
        "vnp_OrderType" => $orderType,
        "vnp_Locale" => "vn",
        "vnp_ReturnUrl" => "http://localhost/restaurant/vnpay_return.php",
        "vnp_IpAddr" => $vnp_IpAddr
    ];

    $vnp_CreateDate = date("YmdHis");
    $vnp_ExpireDate = date("YmdHis", strtotime("+15 minutes"));

    $vnp_Params["vnp_CreateDate"] = $vnp_CreateDate;
    $vnp_Params["vnp_ExpireDate"] = $vnp_ExpireDate;

    ksort($vnp_Params);

    $hashData = "";
    $query = "";
    foreach ($vnp_Params as $key => $value) {
        if (!empty($value)) {
            $hashData .= $key . "=" . urlencode($value) . "&";
            $query .= urlencode($key) . "=" . urlencode($value) . "&";
        }
    }

    $hashData = rtrim($hashData, '&');
    $query = rtrim($query, '&');

    //$secretKey = "BEZLUPOPOTXTDYZHCBGDJBHFJPBLSARL";
    $secretKey = "SLE5RRY8UJMZR2IZX1UF4JAJIFPAOKCP";
    $vnp_SecureHash = hash_hmac("sha512", $hashData, $secretKey);

    $paymentUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

    header("Location: " . $paymentUrl);
    exit();
}

if (isset($_GET['hopdong_id'])) {
    $booking_id = (int)$_GET['hopdong_id'];
    if ($booking_id > 0) {
        // Tính tổng tiền từ DB
        include_once __DIR__ . '/lib/database.php';
        $db = new Database();
        $total = 0;

        if (isset($db->link)) {
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

        // Loại thanh toán: full (mặc định) hoặc deposit nếu bạn truyền ?type=deposit
        $type = isset($_GET['type']) && $_GET['type']==='deposit' ? 'deposit' : 'full';
        $amount_to_pay = ($type === 'deposit') ? (int)round($total * 0.20) : (int)round($total);
        if ($amount_to_pay <= 0) { $amount_to_pay = 30000; } // tối thiểu như code cũ của bạn

        // Đặt các Session mà vnpay_return.php đang dùng
        Session::set('order_booking_id',  $booking_id);
        Session::set('order_amount',      $amount_to_pay);
        Session::set('order_payment_type',$type);

        // Giữ tương thích với code hiện tại của file này
        Session::set('order_data_deposit', $amount_to_pay);
        $info = ($type==='deposit')
              ? "Thanh toán đặt cọc đơn đặt bàn #{$booking_id}"
              : "Thanh toán toàn bộ đơn đặt bàn #{$booking_id}";
        Session::set('order_payment_info', $info);
    }
}

$deposit_amount = Session::get('order_data_deposit');
$order_info = Session::get('order_payment_info');

if (!$deposit_amount) {
    // Nếu gọi tới trang này với ?mode=datban thì dùng mặc định 30k
    if (isset($_GET['mode']) && $_GET['mode'] === 'datban') {
        $deposit_amount = 30000;
        $order_info = "Thanh toán tiền cọc đặt bàn";
    }
}
if (!$deposit_amount) {
    echo "Thiếu thông tin số tiền!";
    exit();
}

generateVNPayUrl($deposit_amount, $order_info);
