<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/khuyenmai.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Kiểm tra quyền hạn (chỉ cho admin truy cập)
$level = Session::get('adminlevel');
if ($level != 0) {
    echo "<script>
        alert('Bạn không phải quản trị viên, vui lòng đăng nhập bằng tài khoản admin!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$insert_km = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $km = new khuyenmai();
    try {
        ob_start(); // Bắt output buffer để hiện alert
        $km->insert_km($_POST, $_FILES);
        ob_end_flush();
        exit();
    } catch (Exception $e) {
        $insert_km = 'Lỗi: ' . $e->getMessage();
    }
}
?>


<style>
    .form-wrapper {
        max-width: 750px;
        margin: 40px auto;
        padding: 30px 40px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        font-family: 'Segoe UI', sans-serif;
    }

    .form-wrapper h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 26px;
        color: #2c3e50;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group input[type="date"],
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 15px;
        transition: border 0.2s ease;
        background-color: #fff;
    }

    .form-group textarea {
        min-height: 100px;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #007bff;
        outline: none;
    }

    .form-actions {
        text-align: center;
        margin-top: 30px;
    }

    .form-actions input[type="submit"] {
        background-color: #007bff;
        color: white;
        padding: 10px 30px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Thêm khuyến mãi</h2>
            <form action="kmadd.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Tên khuyến mãi</label>
                    <input type="text" name="name_km"  required placeholder="Nhập tên khuyến mãi...">
                </div>
                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="date" name="time_star" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" name="time_end" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Giảm giá (%)</label>
                    <input type="text" name="discout"  required placeholder="vd: 20">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghichu" required class="tinymce"></textarea>
                </div>
                <div class="form-group">
                    <label>Hình ảnh</label>
                    <input type="file" required name="image">
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu khuyến mãi">
                    <?php if (isset($insert_km)) echo "<div style='margin-top:15px; color:green; font-weight:bold;'>$insert_km</div>"; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Load TinyMCE -->
<script src="js/tiny-mce/jquery.tinymce.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function () {
        setupTinyMCE();
    });
</script>

<?php include 'inc/footer.php'; ?>
