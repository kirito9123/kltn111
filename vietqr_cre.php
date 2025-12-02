<?php
include 'inc/header.php';
Session::checkSession();

ob_start();

$sid = session_id();
$ct = new cart();
$hopdong_info = $ct->get_hopdong_thongtin($sid);
$tongtien_result = $ct->get_tongtien_hopdong($sid);

if (!$hopdong_info || !$tongtien_result) {
    echo "<script>alert('Không tìm thấy thông tin đơn hàng. Vui lòng kiểm tra lại giỏ hàng.'); window.location.href = 'cartt.php';</script>";
    exit();
}

$info = $hopdong_info->fetch_assoc();
$total_amount = $tongtien_result->fetch_assoc()['tongtien'] ?? 0;

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
$note = "DATBAN" . $sid;
$vietqr_img = generateVietQR($bankCode, $accountNumber, $accountName, $total_amount, $note);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_vietqr_payment'])) {
    $db = new Database();
    $update = "UPDATE hopdong SET payment_status = 'completed', payment_method = 'vietqr' WHERE sesis = ? AND payment_status = 'pending'";
    $stmt = $db->link->prepare($update);
    if ($stmt) {
        $stmt->bind_param("s", $sid);
        $stmt->execute();
        $stmt->close();
        header('Location: success.php');
        exit();
    } else {
        $error_msg = "Lỗi cập nhật thanh toán VietQR.";
    }
}
?>

<style>
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
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

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Thanh toán VietQR</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="/restaurant">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thanh toán <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section ftco-no-pt ftco-no-pb">
    <div class="container px-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="vietqr-section">
                    <h4>Quét mã QR để thanh toán</h4>
                    <img src="<?= htmlspecialchars($vietqr_img) ?>" alt="QR Thanh toán VietQR" width="300" />
                    <p><strong>Số tiền:</strong> <?= $fm->formatMoney($total_amount) ?> VNĐ</p>
                    <p><strong>Nội dung chuyển khoản:</strong> <span class="text1">DATBAN<?= htmlspecialchars($sid) ?></span></p>
                    <form method="post" action="">
                        <button type="submit" name="confirm_vietqr_payment" class="btn btn-primary py-2 px-4">Xác nhận đã thanh toán</button>
                    </form>
                    <?php if (isset($error_msg)): ?>
                        <div style="color: red; margin-top: 15px;"><?= htmlspecialchars($error_msg) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>