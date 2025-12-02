<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/donvitinh.php';

// 1. KIỂM TRA QUYỀN HẠN
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>alert('Không có quyền!'); window.location.href='index.php';</script>";
    exit();
}

// 2. LẤY ID TỪ URL
$dvt = new donvitinh();
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'donvitinh_list.php'</script>";
    exit();
} else {
    $id = (int)$_GET['id'];
}

// 3. XỬ LÝ FORM SUBMIT (CẬP NHẬT)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $ten_dvt = $_POST['ten_dvt'] ?? '';
    $nhom    = $_POST['nhom']    ?? '';
    $he_so   = $_POST['he_so']   ?? '';

    if ($ten_dvt == '' || $nhom == '' || $he_so == '') {
        $msg = "<span style='color:red;'>Vui lòng điền đầy đủ thông tin!</span>";
    } else {
        $result = $dvt->update_don_vi_tinh($id, $ten_dvt, $nhom, $he_so);

        if ($result) {
            echo "<script>
                alert('Cập nhật đơn vị tính thành công!');
                window.location = 'donvitinh_list.php';
            </script>";
            exit();
        } else {
            $msg = "<span style='color:red;'>Cập nhật thất bại.</span>";
        }
    }
}
?>

<style>
    /* CSS giữ nguyên form mẫu */
    .form-wrapper {
        max-width: 750px; margin: 40px auto; padding: 30px 40px;
        background-color: #fff; border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08); font-family: 'Segoe UI', sans-serif;
    }
    .form-wrapper h2 { text-align: center; margin-bottom: 30px; font-size: 26px; color: #2c3e50; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
    .form-group input, .form-group select {
        width: 100%; padding: 10px 14px; border-radius: 8px;
        border: 1px solid #ccc; font-size: 15px; background-color: #fff;
    }
    .form-actions { text-align: center; margin-top: 25px; }
    .btn-main {
        background-color: #007bff; color: white; padding: 10px 30px; font-size: 16px;
        border: none; border-radius: 8px; cursor: pointer; margin-right: 12px;
    }
    .btn-main:hover { background-color: #0056b3; }
    .btn-back {
        background-color: #6c757d; color: white; padding: 10px 22px; font-size: 15px;
        border: none; border-radius: 8px; cursor: pointer;
    }
    .btn-back:hover { background-color: #5a6268; }
    .helper-text { font-size: 13px; color: #666; margin-top: 5px; font-style: italic; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Cập nhật Đơn Vị Tính</h2>

            <?php
            // 4. LẤY DỮ LIỆU CŨ ĐỂ ĐIỀN VÀO FORM
            $getDvt = $dvt->get_don_vi_by_id($id);
            if ($getDvt && $result = $getDvt->fetch_assoc()):
            ?>

            <form action="" method="post">

                <div class="form-group">
                    <label>Tên đơn vị</label>
                    <input type="text" name="ten_dvt" 
                           value="<?= htmlspecialchars($result['ten_dvt']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Nhóm quy đổi</label>
                    <select name="nhom" required>
                        <option value="">--- Chọn nhóm ---</option>
                        
                        <option value="khoi_luong" 
                            <?= ($result['nhom'] == 'khoi_luong') ? 'selected' : '' ?>>
                            Khối lượng (g, kg, mg...)
                        </option>
                        
                        <option value="the_tich" 
                            <?= ($result['nhom'] == 'the_tich') ? 'selected' : '' ?>>
                            Thể tích (ml, l, can...)
                        </option>
                        
                        <option value="so_luong" 
                            <?= ($result['nhom'] == 'so_luong') ? 'selected' : '' ?>>
                            Số lượng (cái, thùng, hộp...)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Hệ số quy đổi</label>
                    <input type="number" name="he_so" step="0.0001" min="0.0001"
                           value="<?= floatval($result['he_so']) ?>" required>
                    
                    <div class="helper-text">
                        <b>Lưu ý:</b> Thay đổi hệ số có thể ảnh hưởng đến tính toán tồn kho.<br>
                        (1 = Đơn vị chuẩn | 1000 = Lớn hơn chuẩn | 0.001 = Nhỏ hơn chuẩn)
                    </div>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" class="btn-main" value="Cập nhật">
                    <a href="donvitinh_list.php"><button type="button" class="btn-back">Quay lại</button></a>

                    <?php
                        if (!empty($msg)) {
                            echo "<div style='margin-top:15px;'>$msg</div>";
                        }
                    ?>
                </div>

            </form>

            <?php else: ?>
                <p style="color:red;text-align:center;">Không tìm thấy dữ liệu đơn vị tính.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>