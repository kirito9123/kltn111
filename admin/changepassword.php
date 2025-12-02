<?php

include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/adminlogin.php';

$admin = new adminlogin();
$message = '';
$old = '';
$new = '';
$confirm = '';

// Lưu id admin từ session
$id = Session::get('idadmin');

// Nếu chưa đăng nhập thì sẽ tự động về login.php vì đã checkSession ở trên!

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $message = $admin->change_password($id, $old, $new, $confirm);
}
?>

<style>
    .form-container {
        max-width: 500px;
        margin: 40px auto;
        background-color: #f9f9f9;
        border-radius: 16px;
        padding: 35px 40px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        font-family: 'Segoe UI', sans-serif;
    }
    .form-container h2 {
        text-align: center; font-size: 26px; color: #333; margin-bottom: 30px;
    }
    .form-group {margin-bottom: 20px;}
    .form-group label {
        font-weight: 600; display: block; margin-bottom: 8px; color: #444; font-size: 14px;
    }
    .form-group input {
        width: 100%; padding: 10px 14px; border: 1px solid #ccc; border-radius: 10px;
        font-size: 15px; transition: 0.3s;
    }
    .form-group input:focus {
        border-color: #007bff; box-shadow: 0 0 5px rgba(0, 123, 255, 0.3); outline: none;
    }
    .popup {
        background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: bold;
    }
    .popup.success {
        background-color: #d4edda; color: #155724; border-color: #c3e6cb;
    }
    .btn-submit {
        width: 100%; padding: 12px; background-color: #007bff; color: white;
        border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-submit:hover {background-color: #0056b3;}
</style>

<div class="form-container">
    <h2>Đổi mật khẩu</h2>
    <?php if (!empty($message)): ?>
        <?= $message ?>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="old_password">Mật khẩu cũ</label>
            <input type="password" name="old_password" id="old_password" value="<?= htmlspecialchars($old) ?>" placeholder="Nhập mật khẩu cũ" required>
        </div>
        <div class="form-group">
            <label for="new_password">Mật khẩu mới</label>
            <input type="password" name="new_password" id="new_password" value="<?= htmlspecialchars($new) ?>" placeholder="Nhập mật khẩu mới" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" id="confirm_password" value="<?= htmlspecialchars($confirm) ?>" placeholder="Xác nhận mật khẩu mới" required>
        </div>
        <button type="submit" name="submit" class="btn-submit">Cập nhật mật khẩu</button>
    </form>
</div>

<?php include 'inc/footer.php'; ?>
