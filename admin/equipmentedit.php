<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/phong.php'; ?>
<?php include '../classes/trangthietbi.php'; ?>
<style>
    /* 1. Tổng quan và Khung chính */
    * {
        box-sizing: border-box;
    }

    .form-wrapper {
        max-width: 750px;
        /* Tăng chiều rộng một chút */
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

    /* 2. Nhóm Input */
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

    /* 3. Thiết kế Input, Select, Textarea */
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

    /* 4. Hiển thị Ảnh hiện tại */
    .image-preview {
        display: block;
        max-width: 180px;
        /* Ảnh lớn hơn để dễ xem */
        height: auto;
        border: 3px solid #ecf0f1;
        border-radius: 8px;
        margin: 15px 0 20px 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    /* 5. Nút Hành động */
    .form-actions {
        text-align: center;
        margin-top: 30px;
    }

    .form-actions input[type="submit"] {
        padding: 14px 35px;
        background-color: #f39c12;
        /* Màu cam nổi bật cho Cập nhật */
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 17px;
        font-weight: 700;
        transition: background-color 0.3s, transform 0.1s;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #e67e22;
        transform: translateY(-2px);
    }

    /* 6. Styling cho thông báo */
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
<?php
$thietbi = new trangthietbi();
$phong_cls = new phong();

// Lấy ID thiết bị từ URL
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'equipmentlist.php'</script>";
    exit();
} else {
    $id = $_GET['id'];
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $updateThietBi = $thietbi->update_thietbi($_POST, $_FILES, $id);
}

// Lấy thông tin thiết bị cũ
$get_thietbi = $thietbi->getthietbibyid($id);
?>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Cập Nhật Trang Thiết Bị</h2>

            <?php
            if (isset($updateThietBi)) {
                echo $updateThietBi;
            }

            if ($get_thietbi) {
                while ($result_thietbi = $get_thietbi->fetch_assoc()) {
            ?>
                    <form action="" method="post" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="tenthietbi">Tên Thiết Bị</label>
                            <input type="text" name="tenthietbi" value="<?php echo htmlspecialchars($result_thietbi['tenthietbi']); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="id_phong">Chọn Phòng</label>
                            <select name="id_phong" required>
                                <option value="">-- Chọn Phòng --</option>
                                <?php
                                $listphong = $phong_cls->show_phong_all();
                                if ($listphong) {
                                    while ($result = $listphong->fetch_assoc()) {
                                        $selected = ($result['id_phong'] == $result_thietbi['id_phong']) ? 'selected' : '';
                                        $display_name = $result['tenphong'] . (isset($result['tenloaiphong']) ? ' (Loại: ' . $result['tenloaiphong'] . ')' : '');
                                        echo '<option value="' . $result['id_phong'] . '" ' . $selected . '>' . $display_name . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tinhtrang">Trạng thái Thiết Bị</label>
                            <select name="tinhtrang" required>
                                <option value="">-- Chọn trạng thái --</option>
                                <option value="1" <?php if ($result_thietbi['tinhtrang_trangthietbi'] == 1) echo 'selected'; ?>>1. Hoạt động</option>
                                <option value="2" <?php if ($result_thietbi['tinhtrang_trangthietbi'] == 2) echo 'selected'; ?>>2. Hư hỏng</option>
                                <option value="3" <?php if ($result_thietbi['tinhtrang_trangthietbi'] == 3) echo 'selected'; ?>>3. Cũ</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ghichu">Ghi chú</label>
                            <textarea name="ghichu" class="tinymce"><?php echo htmlspecialchars($result_thietbi['ghichu']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Ảnh thiết bị hiện tại</label>
                            <?php if (!empty($result_thietbi['hinhanh_thietbi'])): ?>
                                <img class="image-preview" src="../images/equipment/<?php echo $result_thietbi['hinhanh_thietbi']; ?>" alt="Ảnh thiết bị" style="max-width: 150px;">
                            <?php else: ?>
                                <span>(Không có ảnh)</span>
                            <?php endif; ?>
                            <label for="image">Thay đổi hình ảnh (nếu muốn)</label>
                            <input type="file" name="image" />
                        </div>

                        <div class="form-actions">
                            <input type="submit" name="submit" value="Cập nhật thiết bị" />
                        </div>
                    </form>
            <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>