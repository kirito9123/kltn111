<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Debug VNPAY</title>
    <style>
        body { font-family: monospace; padding: 20px; line-height: 1.5; }
        .box { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; background: #f9f9f9; word-wrap: break-word; }
        .label { font-weight: bold; color: #333; }
        .value { color: blue; }
        .error { color: red; font-weight: bold; }
        .ok { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h1>CÔNG CỤ KIỂM TRA CHỮ KÝ (CHECKSUM)</h1>

<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// -------------------------------------------------------------------------
// 1. NHẬP THÔNG TIN CỦA BẠN VÀO ĐÂY ĐỂ TEST
// -------------------------------------------------------------------------
$vnp_TmnCode = "B92A2S3P";  // <-- Thay Mã Website vào đây
$vnp_HashSecret = "Y1UIED0VEA590F5N5O8XK20EO37DCNLR"; // <-- Thay Secret Key vào đây
// -------------------------------------------------------------------------

$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_ReturnUrl = "http://localhost/restaurant/vnpay_return.php";

// Dữ liệu giả định
$vnp_TxnRef = date("YmdHis");
$vnp_OrderInfo = "Test Debug";
$vnp_OrderType = "other";
$vnp_Amount = 10000 * 100; // 10,000 VND
$vnp_Locale = "vn";
$vnp_BankCode = "NCB";
$vnp_IpAddr = "127.0.0.1";

$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_ReturnUrl,
    "vnp_TxnRef" => $vnp_TxnRef,
);

if (isset($vnp_BankCode) && $vnp_BankCode != "") {
    $inputData['vnp_BankCode'] = $vnp_BankCode;
}

// BƯỚC 1: SẮP XẾP MẢNG (QUAN TRỌNG)
ksort($inputData);

// BƯỚC 2: TẠO CHUỖI HASH VÀ QUERY
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_Url = $vnp_Url . "?" . $query;
$vnpSecureHash = "";

if (isset($vnp_HashSecret)) {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
}

// -------------------------------------------------------------------------
// HIỂN THỊ KẾT QUẢ SOI LỖI
// -------------------------------------------------------------------------
?>

<div class="box">
    <div class="label">1. KIỂM TRA SECRET KEY (Quan trọng nhất):</div>
    <br>
    Giá trị bạn nhập: <span style="background: yellow; font-size: 18px;">[<?php echo $vnp_HashSecret; ?>]</span>
    <br><br>
    Độ dài: <b><?php echo strlen($vnp_HashSecret); ?></b> ký tự.
    <br>
    <?php
    if (strlen($vnp_HashSecret) != 32 && strlen($vnp_HashSecret) != 0) {
        echo "<div class='error'>-> CẢNH BÁO: Key VNPAY thường có 32 ký tự. Key của bạn đang lạ. Có thể dư khoảng trắng!</div>";
    } elseif (preg_match('/\s/', $vnp_HashSecret)) {
        echo "<div class='error'>-> LỖI CHẾT NGƯỜI: Key có chứa dấu cách (khoảng trắng)! Xóa ngay.</div>";
    } else {
        echo "<div class='ok'>-> Định dạng Key có vẻ ổn (Không có khoảng trắng).</div>";
    }
    ?>
</div>

<div class="box">
    <div class="label">2. CHUỖI DỮ LIỆU ĐƯA VÀO HASH (Hash Data):</div>
    <p><i>(Chuỗi này phải sắp xếp A-Z và nối nhau bằng dấu &)</i></p>
    <textarea style="width:100%; height:80px; border:1px solid #999;"><?php echo $hashdata; ?></textarea>
</div>

<div class="box">
    <div class="label">3. CHỮ KÝ TẠO RA (SecureHash):</div>
    <div class="value"><?php echo $vnpSecureHash; ?></div>
</div>

<div class="box">
    <div class="label">4. ĐƯỜNG LINK CUỐI CÙNG:</div>
    <p style="word-break: break-all;"><?php echo $vnp_Url; ?></p>
    <br>
    <a href="<?php echo $vnp_Url; ?>" target="_blank" style="background: red; color: white; padding: 15px 30px; text-decoration: none; font-size: 20px; font-weight: bold;">BẤM VÀO ĐÂY ĐỂ TEST THANH TOÁN</a>
</div>

</body>
</html>