<?php
// admin/vnpay_return.php
session_start();

// Gọi thẳng file Database để chạy câu lệnh SQL gốc (tránh lỗi Class nhân viên)
include_once __DIR__ . '/../lib/database.php';

$vnp_HashSecret = "SLE5RRY8UJMZR2IZX1UF4JAJIFPAOKCP";

$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả thanh toán</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; width: 450px; }
        .icon { font-size: 65px; margin-bottom: 20px; }
        .success { color: #2ecc71; } .error { color: #e74c3c; }
        h2 { margin: 10px 0; font-size: 24px; color: #333; }
        .btn { display: inline-block; padding: 12px 30px; margin-top: 25px; background: #3498db; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <?php
        if ($secureHash == $vnp_SecureHash) {
            if ($_GET['vnp_ResponseCode'] == '00') {
                // === THANH TOÁN THÀNH CÔNG ===
                
                // 1. TÁCH LẤY ID ĐƠN HÀNG (Quan trọng)
                $ref = $_GET['vnp_TxnRef']; // Ví dụ: 15_20231130...
                $parts = explode('_', $ref);
                
                if(count($parts) > 1){
                    $id_hd = $parts[0]; // Lấy số 15
                } else {
                    $id_hd = $ref; // Dự phòng
                }
                
                $amount = $_GET['vnp_Amount'] / 100;

                // 2. CHẠY SQL UPDATE TRỰC TIẾP (Để chắc chắn 100% database thay đổi)
                // Dựa trên ảnh database bạn gửi: tinhtrang, payment_status
                $db = new Database();
                
                // Cập nhật trạng thái = 1 (hoàn thành), payment_status = completed
                $query = "UPDATE hopdong 
                          SET tinhtrang = 1, 
                              payment_status = 'completed', 
                              payment_method = 'vnpay',
                              updated_at = NOW()
                          WHERE id = '$id_hd'";
                
                $update = $db->update($query);

                // HIỂN THỊ THÔNG BÁO
                echo '<div class="icon success"><i class="fa fa-check-circle"></i></div>';
                echo '<h2>Thanh toán thành công!</h2>';
                echo '<p>Đơn hàng: <b>#'.$id_hd.'</b></p>';
                echo '<p>Số tiền: <b class="success">'.number_format($amount).' VNĐ</b></p>';
                
                // Link quay về danh sách bàn (như bạn yêu cầu)
                echo '<a href="booking_list.php" class="btn">Quay về Danh Sách Bàn</a>';

            } else {
                echo '<div class="icon error"><i class="fa fa-times-circle"></i></div>';
                echo '<h2>Giao dịch thất bại</h2>';
                echo '<p>Lỗi từ ngân hàng hoặc hủy bỏ.</p>';
                echo '<a href="booking_list.php" class="btn" style="background:#95a5a6">Quay về</a>';
            }
        } else {
            echo '<div class="icon error"><i class="fa fa-shield-alt"></i></div>';
            echo '<h2>Lỗi bảo mật (Chữ ký sai)</h2>';
            echo '<a href="booking_list.php" class="btn" style="background:#95a5a6">Quay về</a>';
        }
        ?>
    </div>
</body>
</html>