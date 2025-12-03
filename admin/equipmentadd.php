<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/phong.php'; // Cần để lấy danh sách phòng
include '../classes/trangthietbi.php';

// Kiểm tra đăng nhập và quyền hạn (giữ nguyên logic từ productadd.php)
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$thietbi = new trangthietbi();
$phong_cls = new phong();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $insertThietBi = $thietbi->insert_thietbi($_POST, $_FILES);
}
?>

<style>
    /* Reset và căn chỉnh cơ bản */
    * {
        box-sizing: border-box;
    }

    /* Vỏ bọc chính của Form */
    .form-wrapper {
        max-width: 700px;
        margin: 40px auto;
        padding: 30px 40px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        font-family: 'Segoe UI', sans-serif;
    }

    .form-wrapper h2 {
        text-align: center;
        margin-bottom: 35px;
        font-size: 28px;
        color: #2c3e50;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 15px;
    }

    /* Nhóm input */
    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }

    /* Thiết kế Input, Select, Textarea */
    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #bdc3c7;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s, box-shadow 0.3s;
        background-color: #f8f9fa;
    }

    .form-group input[type="text"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #3498db;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        background-color: #fff;
    }

    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }

    /* Tùy chỉnh input file */
    .form-group input[type="file"] {
        padding: 10px;
    }

    /* Nút hành động */
    .form-actions {
        text-align: center;
        margin-top: 30px;
    }

    .form-actions input[type="submit"] {
        padding: 14px 35px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 17px;
        font-weight: 700;
        transition: background-color 0.3s, transform 0.1s;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
    }

    /* Styling cho thông báo */
    .error,
    .success {
        display: block;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: 600;
        text-align: center;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Thêm Trang Thiết Bị Mới</h2>

            <?php
            if (isset($insertThietBi)) {
                echo $insertThietBi;
            }
            ?>

            <form action="equipmentadd.php" method="post" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="tenthietbi">Tên Thiết Bị</label>
                    <input type="text" name="tenthietbi" placeholder="Nhập tên trang thiết bị..." required />
                </div>

                <div class="form-group">
                    <label for="id_phong">Chọn Phòng</label>
                    <select name="id_phong" required>
                        <option value="">-- Chọn Phòng --</option>
                        <?php
                        $listphong = $phong_cls->show_phong_all();
                        if ($listphong) {
                            while ($result = $listphong->fetch_assoc()) {
                                // Sử dụng id_phong làm value
                                $display_name = $result['tenphong'] . (isset($result['tenloaiphong']) ? ' (Loại: ' . $result['tenloaiphong'] . ')' : '');
                                echo '<option value="' . $result['id_phong'] . '">' . $display_name . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tinhtrang">Trạng thái Thiết Bị</label>
                    <select name="tinhtrang" required>
                        <option value="1" selected>1. Hoạt động</option>
                        <option value="2">2. Hư hỏng</option>
                        <option value="3">3. Cũ</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ghichu">Ghi chú / Mô tả</label>
                    <textarea name="ghichu" class="tinymce" placeholder="Nhập ghi chú hoặc mô tả chi tiết về thiết bị..."></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Tải hình ảnh</label>
                    <input type="file" name="image" />
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">Chọn hình ảnh cho thiết bị (Tùy chọn)</small>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu lại" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
<select name="tinhtrang" required>
    <option value="1" selected>1. Hoạt động</option>
    <option value="2">2. Hư hỏng</option>
    <option value="3">3. Cũ</option>
</select>
</div>

<div class="form-group">
    <label for="ghichu">Ghi chú / Mô tả</label>
    <textarea name="ghichu" class="tinymce" placeholder="Nhập ghi chú hoặc mô tả chi tiết về thiết bị..."></textarea>
</div>

<?php
if (isset($insertThietBi)) {
    echo $insertThietBi;
}
?>

<form action="equipmentadd.php" method="post" enctype="multipart/form-data">
    <option value="2">2. Hư hỏng</option>
    <option value="3">3. Cũ</option>
    </select>
    </div>

    <div class="form-group">
        <label for="ghichu">Ghi chú / Mô tả</label>
        <textarea name="ghichu" class="tinymce" placeholder="Nhập ghi chú hoặc mô tả chi tiết về thiết bị..."></textarea>
    </div>

    <div class="form-group">
        <label for="image">Tải hình ảnh</label>
        <input type="file" name="image" />
        <small style="color: #7f8c8d; display: block; margin-top: 5px;">Chọn hình ảnh cho thiết bị (Tùy chọn)</small>
    </div>

    <div class="form-actions">
        <input type="submit" name="submit" value="Lưu lại" />
    </div>
</form>
</div>
</div>
</div>

<?php include 'inc/footer.php'; ?>