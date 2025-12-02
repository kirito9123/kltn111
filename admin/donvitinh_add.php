<?php
include 'inc/header.php';
include 'inc/sidebar.php';
// Gọi class donvitinh vừa tạo ở bước trước
include '../classes/donvitinh.php';

// 1. KIỂM TRA ĐĂNG NHẬP & QUYỀN HẠN
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập trang này!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

// 2. XỬ LÝ FORM SUBMIT
$msg = '';
$dvt = new donvitinh(); // Khởi tạo đối tượng

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    
    $ten_dvt = $_POST['ten_dvt'] ?? '';
    $nhom    = $_POST['nhom']    ?? '';
    $he_so   = $_POST['he_so']   ?? '';

    // Validate cơ bản
    if ($ten_dvt == '' || $nhom == '' || $he_so == '') {
        $msg = "<span style='color:red;'>Vui lòng điền đầy đủ thông tin!</span>";
    } else {
        // Gọi hàm insert từ class
        $result = $dvt->insert_don_vi_tinh($ten_dvt, $nhom, $he_so);

        if ($result) {
            echo "<script>
                alert('Thêm đơn vị tính thành công!');
                window.location = 'donvitinh_list.php';
            </script>";
            exit();
        } else {
            $msg = "<span style='color:red;'>Lỗi: Không thể thêm đơn vị (Có thể tên đã tồn tại).</span>";
        }
    }
}
?>

<style>
    .form-wrapper {
        max-width: 750px; margin: 40px auto; padding: 30px 40px;
        background-color: #fff; border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        font-family: 'Segoe UI', sans-serif;
    }
    .form-wrapper h2 { text-align: center; margin-bottom: 30px; font-size: 26px; color: #2c3e50; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select { /* Thêm style cho thẻ select */
        width: 100%; padding: 10px 14px; border-radius: 8px;
        border: 1px solid #ccc; font-size: 15px;
        background-color: #fff;
    }
    
    .form-group input:focus, .form-group select:focus { border-color: #007bff; outline: none; }
    
    .form-actions { text-align: center; margin-top: 25px; }
    
    .btn-main {
        background-color: #007bff; color: white; padding: 10px 30px;
        font-size: 16px; border: none; border-radius: 8px; cursor: pointer;
        transition: background-color 0.3s ease; margin-right: 10px;
    }
    .btn-main:hover { background-color: #0056b3; }
    
    .btn-back {
        background-color: #6c757d; color: white; padding: 10px 22px;
        font-size: 15px; border: none; border-radius: 8px; cursor: pointer;
    }
    .btn-back:hover { background-color: #5a6268; }
    
    .helper-text { font-size: 13px; color: #666; margin-top: 5px; font-style: italic; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Thêm Đơn Vị Tính Mới</h2>

            <form action="donvitinh_add.php" method="post">
                
                <div class="form-group">
                    <label>Tên đơn vị</label>
                    <input type="text" name="ten_dvt" required placeholder="Ví dụ: kg, Thùng, Hộp...">
                </div>

                <div class="form-group">
                    <label>Nhóm quy đổi</label>
                    <select name="nhom" required>
                        <option value="">--- Chọn nhóm ---</option>
                        <option value="khoi_luong">Khối lượng (g, kg, mg...)</option>
                        <option value="the_tich">Thể tích (ml, l, can...)</option>
                        <option value="so_luong">Số lượng (cái, thùng, hộp...)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Hệ số quy đổi (So với đơn vị chuẩn)</label>
                    <input type="number" name="he_so" required min="0.0001" step="0.0001" placeholder="Nhập số...">
                    
                    <div class="helper-text">
                        <b>Gợi ý nhập:</b><br>
                        - Nếu là đơn vị chuẩn (g, ml, cái): Nhập <b>1</b><br>
                        - Lớn hơn chuẩn (kg, lít): Nhập <b>1000</b><br>
                        - Thùng bia (24 lon): Nhập <b>24</b><br>
                        - Nhỏ hơn chuẩn (mg): Nhập <b>0.001</b>
                    </div>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu đơn vị" class="btn-main">
                    <a href="donvitinh_list.php"><button type="button" class="btn-back">Hủy bỏ</button></a>

                    <?php
                    if (!empty($msg)) {
                        echo "<div style='margin-top:15px;'>$msg</div>";
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>