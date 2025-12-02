<?php
// admin/vnpay_create_payment.php

// Gọi các file cần thiết
include_once __DIR__ . '/../lib/session.php';
include_once __DIR__ . '/../lib/database.php';

Session::init();

// Cấu hình lại múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

function generateVNPayUrl($amount, $orderInfo, $booking_id)
{
    $vnp_Version = "2.1.0";
    $vnp_Command = "pay";
    $vnp_TmnCode = "B92A2S3P"; 
    $secretKey = "SLE5RRY8UJMZR2IZX1UF4JAJIFPAOKCP";
    
    // --- [QUAN TRỌNG NHẤT] ---
    // SỬA: Tạo mã giao dịch có chứa ID đơn hàng (Ví dụ: 15_20231130...)
    // Để bên Return có thể tách lấy số 15 ra được.
    $vnp_TxnRef = $booking_id . "_" . date("YmdHis"); 
    // -------------------------

    $vnp_OrderType = "other";
    $vnp_BankCode = "NCB"; 
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    if($vnp_IpAddr == '::1') $vnp_IpAddr = '127.0.0.1';

    // Link trả về (Admin)
    $vnp_ReturnUrl = "http://localhost/restaurant/admin/vnpay_return.php";

    $vnp_Params = [
        "vnp_Version" => $vnp_Version,
        "vnp_Command" => $vnp_Command,
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $amount * 100,
        "vnp_CurrCode" => "VND",
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_OrderInfo" => $orderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_Locale" => "vn",
        "vnp_ReturnUrl" => $vnp_ReturnUrl,
        "vnp_IpAddr" => $vnp_IpAddr
    ];

    if (isset($vnp_BankCode) && $vnp_BankCode != "") {
        $vnp_Params['vnp_BankCode'] = $vnp_BankCode;
    }

    $vnp_CreateDate = date("YmdHis");
    $vnp_ExpireDate = date("YmdHis", strtotime("+15 minutes"));
    $vnp_Params["vnp_CreateDate"] = $vnp_CreateDate;
    $vnp_Params["vnp_ExpireDate"] = $vnp_ExpireDate;

    ksort($vnp_Params);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($vnp_Params as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $secretKey);
    $vnp_Url .= "?" . $query . "vnp_SecureHash=" . $vnpSecureHash;

    header("Location: " . $vnp_Url);
    exit();
}

// XỬ LÝ NHẬN DỮ LIỆU
if (isset($_GET['id_hd'])) {
    $booking_id = (int)$_GET['id_hd'];
    $amount_from_js = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

    // Tính toán số tiền (ưu tiên lấy từ JS gửi sang cho đúng số tiền khách cần trả)
    if($amount_from_js > 0) {
        $amount_to_pay = $amount_from_js;
    } else {
        // Fallback: Tính từ DB nếu JS ko gửi
        $db = new Database();
        $sql = "SELECT thanhtien, so_tien FROM hopdong WHERE id = $booking_id LIMIT 1";
        $rs = $db->select($sql);
        if($rs){
            $row = $rs->fetch_assoc();
            $amount_to_pay = $row['thanhtien'] - $row['so_tien'];
        } else {
            $amount_to_pay = 0;
        }
    }

    if ($amount_to_pay > 0) {
        $orderInfo = "Thanh toan don hang #{$booking_id}";
        generateVNPayUrl((int)$amount_to_pay, $orderInfo, $booking_id);
    } else {
        echo "Số tiền thanh toán không hợp lệ hoặc đã thanh toán đủ.";
    }
} else {
    echo "Thiếu ID đơn hàng.";
}
?>