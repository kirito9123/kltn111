<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

// Clear session booking info
unset($_SESSION['booking_info']);
?>

<style>
    h1, h2, h3, h4, h5, h6 {
        text-align: center;
        padding: 10px;
        margin-top: 0;
    }
    .success-section {
        max-width: 460px;
        margin: 35px auto;
        padding: 38px 30px 34px 30px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 32px 0 rgba(46, 204, 113, 0.14), 0 1.5px 6px 0 rgba(44, 62, 80, 0.09);
        text-align: center;
        position: relative;
        animation: fadeinup 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    }
    .success-section img {
        margin-bottom: 18px;
        border-radius: 50%;
        background: #eafaf1;
        box-shadow: 0 0 0 6px #eafaf1;
        padding: 16px;
    }
    .success-section h3 {
        color: #2ecc71;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 16px;
        letter-spacing: 0.5px;
    }
    .success-section p {
        color: #333;
        font-size: 16px;
        margin-bottom: 25px;
        letter-spacing: 0.2px;
    }
    .success-section .btn {
        background: linear-gradient(90deg, #27ae60 0%, #2ecc71 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 32px;
        font-size: 17px;
        font-weight: 600;
        box-shadow: 0 2px 12px 0 rgba(46, 204, 113, 0.11);
        transition: background 0.24s, box-shadow 0.22s;
        text-decoration: none;
        display: inline-block;
    }
    .success-section .btn:hover {
        background: linear-gradient(90deg, #219150 0%, #27ae60 100%);
        box-shadow: 0 4px 18px 0 rgba(39, 174, 96, 0.18);
        color: #fff;
    }
    @keyframes fadeinup {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    body {
        background: #f6faf7 !important;
    }
</style>


<div class="grid_10">
    <div class="box round first grid">
        <h2>Thanh toán thành công</h2>
        <div class="block">
            <div class="success-section">
                <img height="150" width="150" src="images/success1.png" alt="Success">
                <h3 class="mt-3">Thanh toán đặt bàn đã được thực hiện thành công!</h3>
                <p>Cảm ơn bạn đã tạo đặt bàn tại Nhà Hàng TrisKiet. Đơn hàng đã được ghi nhận.</p>
                <a href="admin_orders.php" class="btn btn-primary py-2 px-4">Quay về trang chủ</a>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>