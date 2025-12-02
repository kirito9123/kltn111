<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/cart.php';
require_once '../classes/khuyenmai.php';

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

$sid = session_id();
$ct = new Cart();
$km = new khuyenmai();
$fm = new Format();
$booking_info = $_SESSION['booking_info'] ?? null;

if (!$booking_info) {
    echo "<script>alert('Không tìm thấy thông tin đặt bàn. Vui lòng thử lại.'); window.location.href = 'create_booking.php';</script>";
    exit();
}

$total_amount = $booking_info['total_amount'] ?? 0;
$id_km = $booking_info['id_km'] ?? null;
$id_user = $booking_info['id_user'];
$time = $booking_info['time'];
$date = $booking_info['date'];
$khach = $booking_info['khach'];
$noidung = $booking_info['noidung'];

// --- Xử lý khuyến mãi giảm theo %
$km_info = null;
$discount = 0;
$phantram = 0;
if ($id_km) {
    $km_result = $km->getkmbyid($id_km);
    if ($km_result && $km_result->num_rows > 0) {
        $km_info = $km_result->fetch_assoc();
        // Sử dụng đúng trường 'discout'
        $phantram = isset($km_info['discout']) ? floatval($km_info['discout']) : 0;
        if ($phantram > 0) {
            $discount = round($total_amount * $phantram / 100);
        }
    }
}
$total_with_discount = $total_amount - $discount;
if ($total_with_discount < 0) $total_with_discount = 0;

// --- Tạo link VietQR
function generateVietQR($bankCode, $accountNumber, $accountName, $amount, $note)
{
    $url = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-compact2.png";
    $url .= "?amount={$amount}";
    $url .= "&addInfo=" . urlencode($note);
    $url .= "&accountName=" . urlencode($accountName);
    return $url;
}
$bankCode = 'MB';
$accountNumber = '0869387703';
$accountName = 'NGUYEN MINH TRI';
$note = "DATMON" . $sid;
$vietqr_img = generateVietQR($bankCode, $accountNumber, $accountName, $total_with_discount, $note);

// --- Xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_vietqr_payment'])) {
    $db = new Database();
    $update = "UPDATE hopdong SET payment_status = 'completed', payment_method = 'vietqr' WHERE sesis = ? AND payment_status = 'pending'";
    $stmt = $db->link->prepare($update);
    if ($stmt) {
        $stmt->bind_param("s", $sid);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['booking_info']);
        header('Location: success_admin.php');
        exit();
    } else {
        $error_msg = "Lỗi cập nhật thanh toán VietQR.";
    }
}
?>

<style>
    h1, h2, h3, h4, h5, h6 {
        text-align: center;
        padding: 10px;
    }
    label {
        font-weight: bold;
        position: relative;
    }
    .vietqr-section {
        max-width: 400px;
        margin: 20px auto;
        padding: 15px;
        border: 1px dashed #2ecc71;
        text-align: center;
        background-color: #e9f9ee;
    }
    .text1 {
        color: red;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Thanh toán VietQR</h2>
        <div class="block">
            <div class="vietqr-section">
                <h4>Quét mã QR để thanh toán</h4>
                <img src="<?php echo htmlspecialchars($vietqr_img); ?>" alt="QR Thanh toán VietQR" width="300" />
                <p><strong>Tổng tiền (chưa giảm):</strong> <?php echo $fm->formatMoney($total_amount); ?> VNĐ</p>
                <?php if ($discount > 0 && $km_info) { ?>
                    <p>
                        <strong>Khuyến mãi<?php echo $km_info['name_km'] ? ' ('.htmlspecialchars($km_info['name_km']).')' : ''; ?> - <?php echo $phantram; ?>%:</strong>
                        -<?php echo $fm->formatMoney($discount); ?> VNĐ
                    </p>
                <?php } ?>
                <p><strong>Số tiền cần thanh toán:</strong> <?php echo $fm->formatMoney($total_with_discount); ?> VNĐ</p>
                <p><strong>Nội dung chuyển khoản:</strong> <span class="text1">DATMON<?php echo htmlspecialchars($sid); ?></span></p>
                <form method="post" action="">
                    <button type="submit" name="confirm_vietqr_payment" class="btn btn-primary py-2 px-4">Xác nhận đã thanh toán</button>
                </form>
                <?php if (isset($error_msg)): ?>
                    <div style="color: red; margin-top: 15px;"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>
