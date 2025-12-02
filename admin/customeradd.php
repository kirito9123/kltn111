<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/user.php';

// Kiểm tra session và quyền hạn
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$level = Session::get('adminlevel');
if ($level != 0 && $level != 2) { 
    echo "<script>
        alert('Bạn không phải quản trị viên, vui lòng đăng nhập bằng tài khoản admin!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$us = new user();
$msg = '';

$ten    = '';
$sdt1   = '';
$email  = '';
$sex    = '1';
$pass1  = '';
$repass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $ten    = trim($_POST['ten'] ?? '');
    $sdt1   = trim($_POST['sdt1'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $sex    = $_POST['gioitinh'] ?? '1';
    $pass1  = $_POST['pass1'] ?? '';
    $repass = $_POST['repass'] ?? '';

    // 1. Kiểm tra dữ liệu đầu vào
    if ($pass1 !== $repass) {
        $msg = '<div class="alert alert-danger">Mật khẩu nhập lại không khớp!</div>';
    } 
    elseif (!preg_match("/^(03|05|07|08|09)[0-9]{8}$/", $sdt1)) {
        $msg = '<div class="alert alert-danger">Số điện thoại không hợp lệ (Phải là 10 số, đầu 03,05,07,08,09)!</div>';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '<div class="alert alert-danger">Email không đúng định dạng!</div>';
    } 
    else {
        // 2. Kiểm tra trùng lặp
        $phone_check = $us->test_phone($sdt1);
        $email_check = $us->check_email($email);

        if ($phone_check && $phone_check->num_rows > 0) {
            $msg = '<div class="alert alert-danger">Số điện thoại này đã tồn tại!</div>';
        } elseif ($email_check && $email_check->num_rows > 0) {
            $msg = '<div class="alert alert-danger">Email này đã tồn tại!</div>';
        } else {
            // 3. Thêm vào DB
            $insertUser = $us->admin_insert_user($ten, $sdt1, $sex, $email, md5($pass1), md5($repass));
            
            if ($insertUser) {
                echo "<script>
                    alert('Thêm khách hàng thành công!');
                    window.location.href = 'customerlist.php';
                </script>";
                exit(); 
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi xảy ra khi thêm khách hàng!</div>';
            }
        }
    }
}
?>

<style>
    .form-container { padding: 30px; background: #fff; max-width: 800px; margin: 0 auto; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    .form-control:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 5px rgba(13, 110, 253, 0.2); }
    .gender-options { display: flex; gap: 30px; align-items: center; padding-top: 5px; }
    .gender-options label { font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px; margin: 0; }
    
    /* CSS cho 2 nút bấm nằm ngang */
    .form-actions {
        display: flex;
        gap: 15px; /* Khoảng cách giữa 2 nút */
        margin-top: 30px;
    }

    .btn-submit, .btn-back {
        flex: 1; /* Chia đều chiều rộng */
        padding: 12px 30px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        text-align: center;
        text-decoration: none; /* Bỏ gạch chân link */
        display: inline-block;
    }

    .btn-submit {
        background-color: #0d6efd;
        color: white;
    }
    .btn-submit:hover { background-color: #0b5ed7; }

    .btn-back {
        background-color: #6c757d; /* Màu xám */
        color: white;
    }
    .btn-back:hover { background-color: #5a6268; }

    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center; }
    .alert-danger { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Thêm Khách Hàng Mới</h2>
        <div class="block">
            <div class="form-container">
                
                <?php if (!empty($msg)) echo $msg; ?>

                <form action="customeradd.php" method="post">
                    <div class="form-group">
                        <label>Họ và Tên</label>
                        <input type="text" name="ten" class="form-control" placeholder="Ví dụ: Nguyễn Văn A" value="<?= htmlspecialchars($ten) ?>" required />
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại (Tên đăng nhập)</label>
                        <input type="text" name="sdt1" class="form-control" placeholder="Ví dụ: 0912345678" pattern="[0]{1}[0-9]{9}" title="SĐT phải bắt đầu bằng số 0 và có 10 chữ số" value="<?= htmlspecialchars($sdt1) ?>" required />
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Ví dụ: email@domain.com" value="<?= htmlspecialchars($email) ?>" required />
                    </div>

                    <div class="form-group">
                        <label>Giới tính</label>
                        <div class="gender-options">
                            <label><input type="radio" name="gioitinh" value="1" <?= ($sex == '1') ? 'checked' : '' ?>> Nam</label>
                            <label><input type="radio" name="gioitinh" value="0" <?= ($sex == '0') ? 'checked' : '' ?>> Nữ</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="pass1" class="form-control" placeholder="Nhập mật khẩu" required />
                    </div>

                    <div class="form-group">
                        <label>Nhập lại mật khẩu</label>
                        <input type="password" name="repass" class="form-control" placeholder="Xác nhận mật khẩu" required />
                    </div>

                    <div class="form-actions">
                        <a href="customerlist.php" class="btn-back">Quay lại</a>
                        <input type="submit" name="submit" value="Thêm Khách Hàng" class="btn-submit" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>