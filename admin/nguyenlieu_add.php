<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/nguyenvatlieu.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Kiểm tra quyền hạn (chỉ cho admin truy cập)
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập trang này!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$insert_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $nl = new nguyenvatlieu();

    $ten_nl       = $_POST['ten_nl']       ?? '';
    $don_vi       = $_POST['don_vi']       ?? '';
    $so_luong_ton = $_POST['so_luong_ton'] ?? 0;
    $gia_nhap_tb  = $_POST['gia_nhap_tb']  ?? 0;
    $ghichu       = $_POST['ghichu']       ?? '';

    // Có thể kiểm tra ràng buộc đơn giản
    if (trim($ten_nl) === '' || trim($don_vi) === '') {
        $insert_msg = "<span style='color:red;'>Vui lòng nhập đầy đủ Tên nguyên liệu và Đơn vị.</span>";
    } else {
        $result = $nl->insert_nguyen_lieu($ten_nl, $don_vi, $so_luong_ton, $gia_nhap_tb, $ghichu);

        if ($result) {
            // Thêm thành công -> alert + quay về danh sách
            echo "<script>
                alert('Thêm nguyên liệu thành công!');
                window.location = 'nguyenlieu_list.php';
            </script>";
            exit();
        } else {
            $insert_msg = "<span style='color:red;'>Thêm nguyên liệu thất bại, vui lòng thử lại.</span>";
        }
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
    .form-group input[type="number"],
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
        min-height: 90px;
    }
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #007bff;
        outline: none;
    }
    .form-actions {
        text-align: center;
        margin-top: 25px;
    }
    .btn-main {
        background-color: #007bff;
        color: white;
        padding: 10px 30px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-right: 10px;
    }
    .btn-main:hover {
        background-color: #0056b3;
    }
    .btn-back {
        background-color: #6c757d;
        color: white;
        padding: 10px 22px;
        font-size: 15px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .btn-back:hover {
        background-color: #5a6268;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Thêm nguyên vật liệu</h2>

            <form action="nguyenlieu_add.php" method="post">
                <div class="form-group">
                    <label>Tên nguyên liệu</label>
                    <input type="text" name="ten_nl" required placeholder="Nhập tên nguyên liệu...">
                </div>

                <div class="form-group">
                    <label>Đơn vị tính</label>
                    <input type="text" name="don_vi" required placeholder="kg, g, l, ml, quả...">
                </div>

                <div class="form-group">
                    <label>Tồn kho ban đầu</label>
                    <input type="number" name="so_luong_ton" min="0" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label>Giá nhập trung bình (VNĐ)</label>
                    <input type="number" name="gia_nhap_tb" min="0" step="100" value="0">
                </div>

                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghichu" placeholder="Ghi chú thêm (nếu có)..."></textarea>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu nguyên liệu" class="btn-main">
                    <a href="nguyenlieu_list.php"><button type="button" class="btn-back">Quay lại</button></a>

                    <?php
                    if (!empty($insert_msg)) {
                        echo "<div style='margin-top:15px;'>$insert_msg</div>";
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
