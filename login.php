<?php
include 'inc/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sdt  = $_POST['sdt'];
  $pass = md5($_POST['pass']);

  $login_check = $us->login_user($sdt, $pass);
}
?>

<style>
  .login-section {
    padding: 60px 0;
    background-color: #f8f9fa;
  }

  .login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 40px;
    max-width: 500px;
    margin: auto;
  }

  .login-card h2 {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 30px;
    text-align: center;
    color: #343a40;
  }

  .form-control {
    border-radius: 8px;
    height: 45px;
  }

  .btn-login {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 8px;
  }

  .register-link {
    text-align: center;
    margin-top: 20px;
  }

  .register-link a {
    text-decoration: none;
    color: #007bff;
    font-weight: 500;
  }

  .register-link a:hover {
    text-decoration: underline;
  }

  @media (max-width: 768px) {
    .login-card {
      padding: 25px;
    }

    .login-card h2 {
      font-size: 26px;
    }
  }
</style>

<!-- Banner -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate text-center mb-4">
        <h1 class="mb-2 bread">Đăng nhập</h1>
        <p class="breadcrumbs">
          <span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
          <span>Đăng nhập <i class="ion-ios-arrow-forward"></i></span>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Form đăng nhập -->
<section class="login-section">
  <div class="container">
    <div class="login-card">
      <h2>Đăng nhập</h2>

      <span>
        <?php
        if (isset($login_check)) {
          echo $login_check;
        }
        ?>
      </span>

      <form action="login.php" method="post">
        <div class="form-group">
          <label>Số điện thoại</label>
          <input type="text" name="sdt" class="form-control" placeholder="Nhập số điện thoại" required>
        </div>

        <div class="form-group">
          <label>Mật khẩu</label>
          <input type="password" name="pass" class="form-control" placeholder="Nhập mật khẩu" required>
        </div>

        <div class="form-group mt-4">
          <button type="submit" class="btn btn-primary btn-login">Đăng nhập</button>
        </div>
      </form>

      <div class="register-link">
        Chưa có tài khoản? <a href="dangky.php">Đăng ký</a>
      </div>
    </div>
  </div>
</section>

<?php include 'inc/footer.php'; ?>