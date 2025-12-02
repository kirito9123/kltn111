<?php
include 'inc/header.php';
include_once 'classes/user.php';
$us = new user();

$result = '';
// Biến lưu giá trị đã nhập
$ten    = '';
$email  = '';
$sdt1   = '';
$sex    = '1'; // Mặc định là Nam
$pass1  = '';
$repass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ten    = $_POST['ten'] ?? '';
  $email  = $_POST['email'] ?? '';
  $sdt1   = $_POST['sdt1'] ?? '';
  $sex    = $_POST['gioitinh'] ?? '1';
  $pass1  = $_POST['pass1'] ?? '';
  $repass = $_POST['repass'] ?? '';

  // Kiểm tra mật khẩu nhập lại
  if ($pass1 !== $repass) {
    $result = '<div class="popup">Mật khẩu nhập lại không khớp. Vui lòng thử lại!</div>';
  } else {
    // Giữ nguyên cơ chế mã hoá hiện tại của bạn (md5)
    $pass1_md5  = md5($pass1);
    $repass_md5 = md5($repass);

    // Kiểm tra trùng SĐT/Email như cũ
    $phone_check = $us->test_phone($sdt1);
    $email_check = $us->check_email($email);

    if ($phone_check && $phone_check->num_rows > 0) {
      $result = '<div class="popup">Số điện thoại này đã được đăng ký. Vui lòng dùng số khác!</div>';
    } else if ($email_check && $email_check->num_rows > 0) {
      $result = '<div class="popup">Email này đã được đăng ký. Vui lòng dùng email khác!</div>';
    } else {
      // 👉 Thay vì gọi insert_user (vì insert_user sẽ alert + redirect),
      // mình dùng hàm mới registerWithActivation() trong class user
      // để: INSERT (is_active=0) + tạo token 10 phút + gửi email.
      $msg = $us->registerWithActivation($ten, $sdt1, $sex, $email, $pass1_md5);

      // Hiển thị thông báo thân thiện
      $result = '<div class="popup success">'.htmlspecialchars($msg).'</div>';

      // Nếu muốn reset form sau khi đăng ký thành công, bỏ comment dòng dưới:
      // $ten = $email = $sdt1 = $pass1 = $repass = ''; $sex = '1';
    }
  }
}
?>

<style>
  label { color: black; font-weight: bold; }
  .register-section { padding: 60px 0; background-color: #f8f9fa; }
  .register-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 40px; max-width: 600px; margin: auto; }
  .register-card h2 { font-size: 32px; font-weight: bold; margin-bottom: 30px; text-align: center; color: #343a40; }
  .form-group label { font-weight: 600; margin-bottom: 8px; display: block; }
  .form-control { border-radius: 8px; height: 45px; }
  .btn-register { width: 100%; padding: 12px; font-size: 16px; font-weight: bold; border-radius: 8px; }
  .gender-options { display: flex; gap: 20px; }
  .gender-options label { font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer; }
  .popup { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: bold; }
  @media (max-width: 768px) {
    .register-card { padding: 25px; }
    .register-card h2 { font-size: 26px; }
  }
  .popup.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
</style>



<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate text-center mb-4">
        <h1 class="mb-2 bread">Đăng ký</h1>
        <p class="breadcrumbs">
          <span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
          <span>Đăng ký <i class="ion-ios-arrow-forward"></i></span>
        </p>
      </div>
    </div>
  </div>
</section>

<section class="register-section">
  <div class="container">
    <div class="register-card">
      <h2>Đăng ký tài khoản</h2>
      <span>
        <?php if (isset($result)) echo $result; ?>
      </span>
      <form class="login-form" action="dangky.php" method="post">
        <div class="form-group">
          <label>Họ và Tên</label>
          <input type="text" class="form-control" name="ten" placeholder="Ví dụ: Nguyen Minh Tri" required value="<?= htmlspecialchars($ten) ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" name="email" placeholder="Ví dụ: ten@email.com" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <div class="form-group">
          <label>SĐT (dùng để đăng nhập)</label>
          <input type="text" class="form-control" name="sdt1" pattern="[0]{1}[0-9]{9}" placeholder="Ví dụ: 086938XXXX" required value="<?= htmlspecialchars($sdt1) ?>">
        </div>
        <div class="form-group">
          <label>Giới tính</label>
          <div class="gender-options">
            <label>
              <input type="radio" name="gioitinh" value="1" <?= ($sex == '1') ? 'checked' : '' ?>> Nam
            </label>
            <label>
              <input type="radio" name="gioitinh" value="0" <?= ($sex == '0') ? 'checked' : '' ?>> Nữ
            </label>
          </div>
        </div>
        <div class="form-group">
          <label>Mật khẩu</label>
          <input type="password" class="form-control" name="pass1" required value="<?= htmlspecialchars($pass1) ?>">
        </div>
        <div class="form-group">
          <label>Nhập lại mật khẩu</label>
          <input type="password" class="form-control" name="repass" required value="<?= htmlspecialchars($repass) ?>">
        </div>
        <div class="form-group mt-4">
          <button type="submit" class="btn btn-primary btn-register">Đăng ký</button>
        </div>
        <div class="register-link">
          Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
      </form>
    </div>
  </div>
</section>

<?php include 'inc/footer.php'; ?>
