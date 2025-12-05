<?php
// FILE: admin/suanhansu.php

include 'inc/header.php';
include 'inc/sidebar.php';

$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../classes/nhansu.php');
include_once($filepath . '/../helpers/format.php');

// Chỉ Admin cấp cao mới được vào trang này
if (Session::get('adminlevel') != 0) {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location.href='quanlynhansu_list.php';</script>";
    exit();
}

$nhansu_class = new NhanSu();
$fm = new Format();

// Lấy ID từ URL
if (!isset($_GET['mans']) || $_GET['mans'] == NULL) {
    echo "<script>window.location = 'quanlynhansu_list.php';</script>";
} else {
    $mans = $_GET['mans'];
}

// Xử lý Submit và Hiện thông báo Alert
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Gọi hàm cập nhật
    $updateResult = $nhansu_class->capNhatHoSoNhanSu($mans, $_POST, $_FILES);

    // Xử lý thông báo bằng Javascript Alert
    if ($updateResult) {
        // Lọc bỏ thẻ HTML để hiện alert sạch đẹp
        $msgClean = strip_tags($updateResult);
        $msgClean = addslashes($msgClean);

        echo "<script>
            alert('$msgClean');
            // Chuyển hướng về trang danh sách sau khi bấm OK
            window.location.href = 'quanlynhansu_list.php';
        </script>";
    }
}

// Lấy thông tin nhân sự hiện tại
$result = $nhansu_class->layThongTinNhanSu($mans);
if (!$result) {
    echo "<script>alert('Không tìm thấy nhân sự!'); window.location.href='quanlynhansu_list.php';</script>";
    exit;
}
?>

<style>
    .form-container {
        max-width: 1000px;
        margin: 20px auto;
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: #0d6efd;
        margin-bottom: 25px;
        font-weight: 700;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    fieldset {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f9f9f9;
    }

    legend {
        font-weight: bold;
        color: #495057;
        padding: 0 10px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    label {
        font-weight: 600;
        font-size: 13px;
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    input[type="text"],
    input[type="date"],
    input[type="number"],
    select,
    textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 13px;
        box-sizing: border-box;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #0d6efd;
        outline: none;
    }

    /* Style cho ô bị khóa */
    input[disabled],
    select[disabled] {
        background-color: #e9ecef;
        cursor: not-allowed;
        color: #6c757d;
    }

    .btn-save {
        background-color: #ffc107;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        margin-top: 10px;
        font-size: 16px;
        transition: 0.3s;
    }

    .btn-save:hover {
        background-color: #e0a800;
    }

    .current-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #ddd;
        margin-top: 10px;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>✏️ Cập Nhật Hồ Sơ Nhân Sự: <?php echo htmlspecialchars($result['hoten'] ?? ''); ?></h2>

            <form action="" method="post" enctype="multipart/form-data">

                <div class="form-grid">
                    <div class="col-left">
                        <fieldset>
                            <legend>👤 Thông tin chung & Tài khoản</legend>
                            <div style="margin-bottom: 10px;">
                                <label>Mã Nhân Sự</label>
                                <input type="text" value="<?php echo $result['mans']; ?>" disabled />
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Họ và Tên *</label>
                                <input type="text" name="hoten" value="<?php echo htmlspecialchars($result['hoten'] ?? ''); ?>" required />
                            </div>
                            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <div>
                                    <label>Tên đăng nhập</label>
                                    <input type="text" name="adminuser" value="<?php echo htmlspecialchars($result['adminuser'] ?? ''); ?>" required />
                                </div>
                                <div>
                                    <label>Chức vụ (Không sửa)</label>
                                    <select disabled>
                                        <?php
                                        $roles = $nhansu_class->layDanhSachVaiTro();
                                        foreach ($roles as $role) {
                                            $selected = ($result['level'] == $role['id_role']) ? 'selected' : '';
                                            echo "<option value='{$role['id_role']}' $selected>{$role['ten_role']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="level" value="<?php echo $result['level']; ?>" />
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Ngày sinh</label>
                                <input type="date" name="ngaysinh" value="<?php echo $result['ngaysinh']; ?>" required />
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Giới tính</label>
                                <select name="gioitinh">
                                    <option value="Nam" <?php if (isset($result['gioitinh']) && $result['gioitinh'] == 'Nam') echo 'selected'; ?>>Nam</option>
                                    <option value="Nữ" <?php if (isset($result['gioitinh']) && $result['gioitinh'] == 'Nữ') echo 'selected'; ?>>Nữ</option>
                                    <option value="Khác" <?php if (isset($result['gioitinh']) && $result['gioitinh'] == 'Khác') echo 'selected'; ?>>Khác</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Ngày vào làm</label>
                                <input type="date" name="ngayvaolam" value="<?php echo $result['ngayvaolam']; ?>" required />
                            </div>
                            <div>
                                <label>Ảnh đại diện</label>
                                <input type="file" name="anh_dai_dien" />
                                <?php if (!empty($result['anh_dai_dien'])): ?>
                                    <img src="../images/avt/<?php echo $result['anh_dai_dien']; ?>" class="current-img" alt="Avatar">
                                <?php endif; ?>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>🏠 Địa chỉ & Xuất thân</legend>
                            <div style="margin-bottom: 10px;">
                                <label>Địa chỉ thường trú *</label>
                                <input type="text" name="diachi" value="<?php echo htmlspecialchars($result['diachi'] ?? ''); ?>" required />
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Quê quán</label>
                                <input type="text" name="quequan" value="<?php echo htmlspecialchars($result['quequan'] ?? ''); ?>" />
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Nơi sinh</label>
                                <input type="text" name="noisinh" value="<?php echo htmlspecialchars($result['noisinh'] ?? ''); ?>" />
                            </div>
                            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label>Dân tộc</label>
                                    <input type="text" name="dantoc" value="<?php echo htmlspecialchars($result['dantoc'] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label>Quốc tịch</label>
                                    <input type="text" name="quoctich" value="<?php echo htmlspecialchars($result['quoctich'] ?? ''); ?>" />
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-right">
                        <fieldset>
                            <legend>🆔 Căn Cước Công Dân</legend>
                            <div style="margin-bottom: 10px;">
                                <label>Số CCCD *</label>
                                <input type="text" name="cccd" value="<?php echo htmlspecialchars($result['cccd'] ?? ''); ?>" required />
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label>Ngày cấp</label>
                                <input type="date" name="ngaycap_cccd" value="<?php echo $result['ngaycap_cccd']; ?>" required />
                            </div>
                            <div>
                                <label>Nơi cấp</label>
                                <input type="text" name="noicap_cccd" value="<?php echo htmlspecialchars($result['noicap_cccd'] ?? ''); ?>" required />
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>👨‍👩‍👦 Thông tin Gia Đình</legend>
                            <div style="border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 10px;">
                                <label style="color: #0d6efd;">Thông tin Cha:</label>
                                <div class="form-grid" style="grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 5px;">
                                    <input type="text" name="hoten_cha" placeholder="Họ tên cha" value="<?php echo htmlspecialchars($result['hoten_cha'] ?? ''); ?>" />
                                    <input type="number" name="namsinh_cha" placeholder="Năm sinh" value="<?php echo $result['namsinh_cha']; ?>" />
                                </div>
                                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <input type="text" name="nghenghiep_cha" placeholder="Nghề nghiệp" value="<?php echo htmlspecialchars($result['nghenghiep_cha'] ?? ''); ?>" />
                                    <input type="text" name="sdt_cha" placeholder="SĐT Cha" value="<?php echo htmlspecialchars($result['sdt_cha'] ?? ''); ?>" />
                                </div>
                            </div>

                            <div>
                                <label style="color: #d63384;">Thông tin Mẹ:</label>
                                <div class="form-grid" style="grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 5px;">
                                    <input type="text" name="hoten_me" placeholder="Họ tên mẹ" value="<?php echo htmlspecialchars($result['hoten_me'] ?? ''); ?>" />
                                    <input type="number" name="namsinh_me" placeholder="Năm sinh" value="<?php echo $result['namsinh_me']; ?>" />
                                </div>
                                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <input type="text" name="nghenghiep_me" placeholder="Nghề nghiệp" value="<?php echo htmlspecialchars($result['nghenghiep_me'] ?? ''); ?>" />
                                    <input type="text" name="sdt_me" placeholder="SĐT Mẹ" value="<?php echo htmlspecialchars($result['sdt_me'] ?? ''); ?>" />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>📝 Ghi chú thêm</legend>
                            <textarea name="thongtin_them" rows="3" style="width:100%; border-color:#ccc;"><?php echo htmlspecialchars($result['thongtin_them'] ?? ''); ?></textarea>
                        </fieldset>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-save">LƯU CẬP NHẬT</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="quanlynhansu_list.php" style="text-decoration: none; color: #6c757d; font-weight: 500;">&laquo; Quay lại danh sách</a>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>