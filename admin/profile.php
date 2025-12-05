<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include_once '../classes/nhansu.php';

$ns = new NhanSu();

// --- TỰ ĐỘNG DÒ TÌM ID ADMIN TRONG SESSION ---
$id_admin = null;
if (Session::get('adminId')) $id_admin = Session::get('adminId');
elseif (Session::get('adminid')) $id_admin = Session::get('adminid');
elseif (Session::get('admin_id')) $id_admin = Session::get('admin_id');
elseif (Session::get('id')) $id_admin = Session::get('id');

// Nếu vẫn không tìm thấy, thử tìm thủ công
if (!$id_admin && isset($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        if (strpos(strtolower($key), 'id') !== false && is_numeric($value)) {
            $id_admin = $value;
            break;
        }
    }
}

if (!$id_admin) {
    echo "<div style='padding:20px; color:red; font-weight:bold;'>LỖI: Không tìm thấy ID đăng nhập.</div>";
    exit;
}

// Xử lý nút LƯU
$msg = ""; // Biến lưu thông báo để JS xử lý
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $updateResult = $ns->updateNhanSu($id_admin, $_POST, $_FILES);
    // Kiểm tra kết quả trả về từ class để set thông báo cho JS
    if (strpos($updateResult, 'thành công') !== false) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}

// Lấy thông tin (Bao gồm cả lương nhờ đã sửa class NhanSu)
$getProfile = $ns->getNhanSuByAdminId($id_admin);
if ($getProfile) {
    $result = $getProfile->fetch_assoc();
} else {
    // Tạo dữ liệu giả nếu chưa có hồ sơ
    $name = Session::get('adminName') ? Session::get('adminName') : 'Admin';
    $result = [
        'mans' => '(Mới)',
        'hoten' => $name,
        'anh_dai_dien' => '',
        'ngayvaolam' => date('Y-m-d'),
        'trangthai' => 1,
        'ngaysinh' => '',
        'gioitinh' => 'Nam',
        'quoctich' => 'Việt Nam',
        'dantoc' => 'Kinh',
        'noisinh' => '',
        'quequan' => '',
        'diachi' => '',
        'cccd' => '',
        'ngaycap_cccd' => '',
        'noicap_cccd' => '',
        'hoten_cha' => '',
        'namsinh_cha' => '',
        'nghenghiep_cha' => '',
        'sdt_cha' => '',
        'hoten_me' => '',
        'namsinh_me' => '',
        'nghenghiep_me' => '',
        'sdt_me' => '',
        'thongtin_them' => '',
        'luong_ca' => 0,
        'phu_cap' => 0
    ];
}
?>

<style>
    .profile-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .profile-left {
        width: 300px;
        flex-shrink: 0;
    }

    .profile-right {
        flex: 1;
        min-width: 400px;
    }

    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
        border: 1px solid #e1e1e1;
    }

    .card-header {
        background: #34495e;
        color: white;
        padding: 12px 15px;
        font-weight: bold;
        text-transform: uppercase;
        border-bottom: 3px solid #2c3e50;
    }

    .card-body {
        padding: 20px;
    }

    .avatar-box {
        text-align: center;
        padding: 30px 20px;
    }

    .avatar-img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #f1f1f1;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-upload-label {
        display: inline-block;
        padding: 8px 20px;
        background: #ecf0f1;
        color: #333;
        border-radius: 20px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        border: 1px solid #bdc3c7;
        margin-top: 10px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #34495e;
        font-size: 13px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .section-divider {
        display: flex;
        align-items: center;
        margin: 25px 0 15px;
        color: #e67e22;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 14px;
    }

    .section-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #eee;
        margin-left: 10px;
    }

    /* CSS CHO CÁC NÚT BẤM */
    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .btn-save-profile {
        background: #27ae60;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        flex: 2;
        transition: 0.2s;
    }

    .btn-save-profile:hover {
        background: #2ecc71;
    }

    .btn-back {
        background: #95a5a6;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        flex: 1;
        text-align: center;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-back:hover {
        background: #7f8c8d;
    }

    .alert-box {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: bold;
    }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: #f4f6f9; border:none; padding: 0;">
        <h2 style="margin-bottom: 20px; color: #2c3e50; border-left: 5px solid #3498db; padding-left: 15px;">HỒ SƠ CÁ NHÂN</h2>

        <?php if (isset($updateResult)) echo "<div class='alert-box' style='background: #d4edda; color: #155724; border: 1px solid #c3e6cb;'>$updateResult</div>"; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="profile-container">
                <div class="profile-left">
                    <div class="card">
                        <div class="card-body avatar-box">
                            <?php $avatarPath = !empty($result['anh_dai_dien']) ? "../images/avt/" . $result['anh_dai_dien'] : "img/default-user.png"; ?>
                            <img src="<?php echo $avatarPath; ?>" class="avatar-img" id="previewImg">
                            <h3 style="margin: 10px 0 5px; color: #2c3e50;"><?php echo htmlspecialchars($result['hoten']); ?></h3>
                            <p style="color:#7f8c8d; font-size:13px; margin:0;">Mã NS: <b>#<?php echo $result['mans']; ?></b></p>
                            <label for="fileInput" class="btn-upload-label"><i class="fa fa-camera"></i> Đổi ảnh đại diện</label>
                            <input type="file" name="anh_dai_dien" id="fileInput" style="display:none;" onchange="previewFile(this);" accept="image/*">
                            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">
                            <div style="text-align: left;">
                                <p><strong><i class="fa fa-calendar"></i> Vào làm:</strong> <?php echo date('d/m/Y', strtotime($result['ngayvaolam'])); ?></p>
                                <p><strong><i class="fa fa-check-circle"></i> Trạng thái:</strong>
                                    <?php echo ($result['trangthai'] == 1) ? '<span style="color:#27ae60; font-weight:bold;">Đang làm việc</span>' : '<span style="color:red;">Đã nghỉ</span>'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-right">
                    <div class="card">
                        <div class="card-header"><i class="fa fa-user"></i> Thông Tin Cơ Bản</div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group"><label>Họ và tên <span style="color:red">*</span></label><input type="text" name="hoten" class="form-control" value="<?php echo $result['hoten']; ?>" required></div>
                                <div class="form-group"><label>Ngày sinh</label><input type="date" name="ngaysinh" class="form-control" value="<?php echo $result['ngaysinh']; ?>"></div>
                                <div class="form-group"><label>Giới tính</label>
                                    <select name="gioitinh" class="form-control">
                                        <option value="Nam" <?php if ($result['gioitinh'] == 'Nam') echo 'selected'; ?>>Nam</option>
                                        <option value="Nữ" <?php if ($result['gioitinh'] == 'Nữ') echo 'selected'; ?>>Nữ</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Quốc tịch</label><input type="text" name="quoctich" class="form-control" value="<?php echo $result['quoctich']; ?>"></div>
                                <div class="form-group"><label>Dân tộc</label><input type="text" name="dantoc" class="form-control" value="<?php echo $result['dantoc']; ?>"></div>
                                <div class="form-group"><label>Nơi sinh</label><input type="text" name="noisinh" class="form-control" value="<?php echo $result['noisinh']; ?>"></div>
                            </div>
                            <div class="form-group"><label>Quê quán</label><input type="text" name="quequan" class="form-control" value="<?php echo $result['quequan']; ?>"></div>
                            <div class="form-group"><label>Địa chỉ thường trú <span style="color:red">*</span></label><input type="text" name="diachi" class="form-control" value="<?php echo $result['diachi']; ?>" required></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><i class="fa fa-id-card"></i> Thông Tin Định Danh</div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group"><label>Số CCCD <span style="color:red">*</span></label><input type="text" name="cccd" class="form-control" value="<?php echo $result['cccd']; ?>" required></div>
                                <div class="form-group"><label>Ngày cấp</label><input type="date" name="ngaycap_cccd" class="form-control" value="<?php echo $result['ngaycap_cccd']; ?>"></div>
                            </div>
                            <div class="form-group"><label>Nơi cấp</label><input type="text" name="noicap_cccd" class="form-control" value="<?php echo $result['noicap_cccd']; ?>"></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header" style="background: #27ae60;"><i class="fa fa-money"></i> Thông Tin Lương & Phúc Lợi</div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label style="color:#27ae60;">Lương theo ca (VNĐ)</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo isset($result['luong_ca']) ? number_format($result['luong_ca'], 0, ',', '.') : '0'; ?>"
                                        readonly style="background-color: #e9ecef; font-weight: bold; color: #2d3436;">
                                </div>
                                <div class="form-group">
                                    <label style="color:#27ae60;">Phụ cấp hàng tháng (VNĐ)</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo isset($result['phu_cap']) ? number_format($result['phu_cap'], 0, ',', '.') : '0'; ?>"
                                        readonly style="background-color: #e9ecef; font-weight: bold; color: #2d3436;">
                                </div>
                            </div>
                            <small style="color: #7f8c8d; font-style: italic;">* Thông tin lương được quản lý bởi bộ phận Kế toán. Vui lòng liên hệ nếu có sai sót.</small>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><i class="fa fa-users"></i> Quan Hệ Gia Đình</div>
                        <div class="card-body">
                            <div class="section-divider">THÔNG TIN CHA</div>
                            <div class="form-grid">
                                <div class="form-group"><label>Họ tên Cha</label><input type="text" name="hoten_cha" class="form-control" value="<?php echo $result['hoten_cha']; ?>"></div>
                                <div class="form-group"><label>Năm sinh</label><input type="number" name="namsinh_cha" class="form-control" value="<?php echo $result['namsinh_cha']; ?>"></div>
                                <div class="form-group"><label>Nghề nghiệp</label><input type="text" name="nghenghiep_cha" class="form-control" value="<?php echo $result['nghenghiep_cha']; ?>"></div>
                                <div class="form-group"><label>SĐT</label><input type="text" name="sdt_cha" class="form-control" value="<?php echo $result['sdt_cha']; ?>"></div>
                            </div>
                            <div class="section-divider">THÔNG TIN MẸ</div>
                            <div class="form-grid">
                                <div class="form-group"><label>Họ tên Mẹ</label><input type="text" name="hoten_me" class="form-control" value="<?php echo $result['hoten_me']; ?>"></div>
                                <div class="form-group"><label>Năm sinh</label><input type="number" name="namsinh_me" class="form-control" value="<?php echo $result['namsinh_me']; ?>"></div>
                                <div class="form-group"><label>Nghề nghiệp</label><input type="text" name="nghenghiep_me" class="form-control" value="<?php echo $result['nghenghiep_me']; ?>"></div>
                                <div class="form-group"><label>SĐT</label><input type="text" name="sdt_me" class="form-control" value="<?php echo $result['sdt_me']; ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-group"><label>Ghi chú</label><textarea name="thongtin_them" class="form-control" style="height: 80px;"><?php echo $result['thongtin_them']; ?></textarea></div>

                            <div class="btn-group">
                                <a href="index.php" class="btn-back"><i class="fa fa-arrow-left"></i> QUAY LẠI</a>
                                <button type="submit" name="submit" class="btn-save-profile"><i class="fa fa-floppy-o"></i> LƯU CẬP NHẬT HỒ SƠ</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Hàm xem trước ảnh
    function previewFile(input) {
        var file = $("input[type=file]").get(0).files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function() {
                $("#previewImg").attr("src", reader.result);
            }
            reader.readAsDataURL(file);
        }
    }

    // XỬ LÝ THÔNG BÁO (TOAST NOTIFICATION)
    <?php if (!empty($msg)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Cấu hình thông báo
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end', // Hiện ở góc trên bên phải
                showConfirmButton: false,
                timer: 3000, // Tự tắt sau 3 giây
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Kiểm tra kết quả PHP để hiện thông báo tương ứng
            <?php if ($msg === 'success'): ?>
                Toast.fire({
                    icon: 'success',
                    title: 'Đã lưu hồ sơ thành công!'
                });
            <?php else: ?>
                Toast.fire({
                    icon: 'error',
                    title: 'Có lỗi xảy ra khi lưu!'
                });
            <?php endif; ?>
        });
    <?php endif; ?>
</script>

<?php include 'inc/footer.php'; ?>