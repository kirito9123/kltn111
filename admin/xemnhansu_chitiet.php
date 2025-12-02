<?php
// FILE: admin/xemnhansu_chitiet.php

// ========== INCLUDES VÀ KHỞI TẠO ==========
include 'inc/header.php';
include 'inc/sidebar.php';

$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../classes/nhansu.php');
include_once($filepath . '/../helpers/format.php');

$nhansu_class = new NhanSu();
$fm = new Format();

// ========== LẤY ID VÀ DỮ LIỆU ==========
if (!isset($_GET['mans']) || !is_numeric($_GET['mans'])) {
    echo "<script>alert('Mã nhân sự không hợp lệ!'); window.location.href='quanlynhansu_list.php';</script>";
    exit();
}
$mans = (int)$_GET['mans'];

$nhansu_info = $nhansu_class->layThongTinNhanSu($mans);

if (!$nhansu_info) {
    echo "<script>alert('Không tìm thấy nhân sự với mã: $mans'); window.location.href='quanlynhansu_list.php';</script>";
    exit();
}
?>

<style>
    .detail-container { max-width: 800px; margin: 30px auto; background-color: #fff; padding: 30px 40px; border-radius: 10px; box-shadow: 0 4px 25px rgba(0,0,0,0.1); font-family: 'Segoe UI', sans-serif; }
    h2 { text-align: center; margin-bottom: 30px; font-weight: 700; font-size: 1.8rem; color: #007bff; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px 40px; margin-top: 20px; }
    .info-item { margin-bottom: 15px; }
    .info-item label { display: block; font-weight: 600; color: #555; margin-bottom: 5px; font-size: 0.9rem; text-transform: uppercase; }
    .info-item span { display: block; font-size: 1.05rem; color: #333; padding: 8px 0; border-bottom: 1px solid #eee; }
    .profile-image-section { text-align: center; margin-bottom: 30px; }
    .profile-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #007bff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .status-section { text-align: center; margin-top: 25px; }
    .status-badge { display: inline-block; padding: 6px 15px; border-radius: 15px; font-size: 1rem; font-weight: bold; color: white; }
    .status-active { background-color: #28a745; }
    .status-inactive { background-color: #6c757d; }
    .back-link { display: block; margin-top: 30px; text-align: center; color: #007bff; text-decoration: none; font-weight: 600; }
    .back-link:hover { text-decoration: underline; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="detail-container">
            <h2>📄 Chi Tiết Thông Tin Nhân Sự</h2>

            <div class="profile-image-section">
                 <?php if (!empty($nhansu_info['anh_dai_dien']) && file_exists("../images/avt/" . $nhansu_info['anh_dai_dien'])): ?>
                    <img src="../images/avt/<?php echo $nhansu_info['anh_dai_dien']; ?>" alt="Ảnh đại diện" class="profile-image">
                 <?php else: ?>
                    <img src="../images/avt/default.png" alt="Ảnh mặc định" class="profile-image">
                 <?php endif; ?>
            </div>

            <div class="info-grid">
                <div class="info-item"><label>Mã Nhân Sự</label> <span><?php echo htmlspecialchars($nhansu_info['mans']); ?></span></div>
                <div class="info-item"><label>Họ Tên</label> <span><?php echo htmlspecialchars($nhansu_info['hoten']); ?></span></div>
                <div class="info-item"><label>Tên Đăng Nhập</label> <span><?php echo htmlspecialchars($nhansu_info['adminuser']); ?></span></div>
                <div class="info-item"><label>Chức Vụ</label> <span><?php echo $nhansu_class->layTenVaiTro($nhansu_info['level']); ?></span></div>
                <div class="info-item"><label>Ngày Sinh</label> <span><?php echo !empty($nhansu_info['ngaysinh']) ? date('d/m/Y', strtotime($nhansu_info['ngaysinh'])) : 'Chưa cập nhật'; ?></span></div>
                <div class="info-item"><label>Giới Tính</label> <span><?php echo htmlspecialchars($nhansu_info['gioitinh'] ?? 'Chưa cập nhật'); ?></span></div>
                <div class="info-item"><label>Địa Chỉ</label> <span><?php echo htmlspecialchars($nhansu_info['diachi'] ?? 'Chưa cập nhật'); ?></span></div>
                <div class="info-item"><label>Ngày Vào Làm</label> <span><?php echo !empty($nhansu_info['ngayvaolam']) ? date('d/m/Y', strtotime($nhansu_info['ngayvaolam'])) : 'Chưa cập nhật'; ?></span></div>
                <div class="info-item"><label>Số CCCD</label> <span><?php echo htmlspecialchars($nhansu_info['cccd'] ?? 'Chưa cập nhật'); ?></span></div>
                <div class="info-item"><label>Ngày Cấp CCCD</label> <span><?php echo !empty($nhansu_info['ngaycap_cccd']) ? date('d/m/Y', strtotime($nhansu_info['ngaycap_cccd'])) : 'Chưa cập nhật'; ?></span></div>
                <div class="info-item"><label>Nơi Cấp CCCD</label> <span><?php echo htmlspecialchars($nhansu_info['noicap_cccd'] ?? 'Chưa cập nhật'); ?></span></div>
                <div class="info-item"><label>Quê Quán</label> <span><?php echo htmlspecialchars($nhansu_info['quequan'] ?? 'Chưa cập nhật'); ?></span></div>
                <div class="info-item"><label>Dân tộc</label> <span><?php echo htmlspecialchars($nhansu_info['dantoc'] ?? 'Chưa cập nhật'); ?></span></div>
                 <div class="info-item"><label>Quốc tịch</label> <span><?php echo htmlspecialchars($nhansu_info['quoctich'] ?? 'Chưa cập nhật'); ?></span></div>
                 <div class="info-item"><label>Nơi sinh</label> <span><?php echo htmlspecialchars($nhansu_info['noisinh'] ?? 'Chưa cập nhật'); ?></span></div>
                 </div>

             <div class="status-section">
                 <label>Trạng thái làm việc:</label>
                 <?php echo ($nhansu_info['trangthai'] == 1) ? "<span class='status-badge status-active'>Đang làm việc</span>" : "<span class='status-badge status-inactive'>Đã nghỉ việc / Ẩn</span>"; ?>
            </div>

            <a href="quanlynhansu_list.php" class="back-link">&laquo; Quay lại danh sách</a>

        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>