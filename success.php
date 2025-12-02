<?php
include 'inc/header.php';
Session::checkSession();

$ct = new cart();
$sid = session_id();

// Xoá giỏ hàng và session đặt hàng
$ct->del_all_cart();
unset($_SESSION['current_sesis']);
unset($_SESSION['order_created_at']);
unset($_SESSION['current_order_id']);
?>

<!-- Banner -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate text-center mb-4">
        <h1 class="mb-2 bread">Thanh toán thành công</h1>
        <p class="breadcrumbs">
          <span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
          <span>Thanh toán <i class="ion-ios-arrow-forward"></i></span>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Nội dung xác nhận thành công -->
<section class="ftco-section ftco-no-pt ftco-no-pb">
  <div class="container px-4">
    <div class="row justify-content-center">
      <div class="col-md-6 text-center success-box">
        <img src="images/success1.png" alt="Thành công" class="mb-4" style="width: 130px; height: 130px;">
        <h3 class="text-success mb-3">Thanh toán của bạn đã được thực hiện thành công!</h3>
        <p class="lead">Cảm ơn bạn đã đặt bàn tại <strong>Nhà Hàng TrisKiet</strong>.<br>Chúng tôi sẽ liên hệ để xác nhận chi tiết đặt tiệc.</p>
        <a href="index.php" class="btn btn-primary mt-4 px-5 py-2">Quay về trang chủ</a>
      </div>
    </div>
  </div>
</section>

<!-- CSS cải tiến -->
<style>
  .success-box {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    margin-top: 40px;
  }

  .success-box h3 {
    font-weight: 600;
    font-size: 22px;
  }

  .success-box p {
    font-size: 16px;
    color: #444;
  }

  .btn-primary {
    background-color: #d19c65;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 600;
    transition: 0.3s ease;
  }

  .btn-primary:hover {
    background-color: #b87e4b;
  }

  .text-success {
    color: #28a745 !important;
  }
</style>

<?php include 'inc/footer.php'; ?>
