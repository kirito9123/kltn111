<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/user.php'; ?>

<?php
$kh = new user();

if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'customerlist.php';</script>";
    exit();
} else {
    $id = $_GET['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten    = $_POST['ten'];
    $sdt1   = $_POST['sdt1'];
    $email  = $_POST['email'];
    $sex    = $_POST['gioitinh'];
    // Gọi đúng hàm cho admin, bỏ ghi chú
    $update_user = $kh->update_user_admin($ten, $sdt1, $sex, $email, $id);
}
?>

<style>
    .form-container {
        max-width: 600px;
        margin: 40px auto;
        background-color: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .form-container h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #444;
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }
    .form-group input:focus,
    .form-group select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 4px rgba(0,123,255,0.25);
    }
    .form-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin-top: 20px;
    }
    .btn-custom {
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        border: none;
        transition: background-color 0.3s ease;
    }
    .btn-save {
        background-color: #007bff;
        color: #fff;
    }
    .btn-save:hover {
        background-color: #0056b3;
    }
    .btn-back {
        background-color: #6c757d;
        color: #fff;
    }
    .btn-back:hover {
        background-color: #5a6268;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>Sửa thông tin khách hàng</h2>
            <?php if (isset($update_user)) echo "<p style='color:green; text-align:center; font-weight:bold;'>$update_user</p>"; ?>

            <?php
            $get_user = $kh->show_thongtin($id);
            if ($get_user && $result = $get_user->fetch_assoc()):
            ?>
            <form action="" method="post">
                <div class="form-group">
                    <label>Tên khách hàng</label>
                    <input type="text" name="ten" value="<?= htmlspecialchars($result['ten']) ?>" required />
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="sdt1" value="<?= htmlspecialchars($result['sodienthoai']) ?>" required />
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($result['email']) ?>" required />
                </div>
                <div class="form-group">
                    <label>Giới tính</label>
                    <select name="gioitinh">
                        <option value="1" <?= $result['gioitinh'] == 1 ? 'selected' : '' ?>>Nam</option>
                        <option value="0" <?= $result['gioitinh'] == 0 ? 'selected' : '' ?>>Nữ</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-custom btn-save">Cập nhật</button>
                    <button type="button" onclick="window.location.href='customerlist.php'" class="btn-custom btn-back">Quay lại</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
