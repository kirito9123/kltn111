<?php
// FILE: admin/quanlynhansu_hidden_list.php

// ========== INCLUDES VÀ KHỞI TẠO ==========
include 'inc/header.php'; // Header chung
include 'inc/sidebar.php'; // Sidebar chung

// Include Class NhanSu và Format Helper
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../classes/nhansu.php');
include_once($filepath . '/../helpers/format.php');

// Khởi tạo đối tượng
$nhansu_class = new NhanSu();
$fm = new Format();

// ========== XỬ LÝ ACTIONS (RESTORE, DELETE PERMANENTLY) ==========
$restoreMsg = ''; // Biến lưu thông báo khôi phục
$deletePermMsg = ''; // Biến lưu thông báo xóa vĩnh viễn

// --- Xử lý yêu cầu Khôi phục (Hiện lại) ---
if (isset($_GET['khoiphuc_mans'])) { // Tham số để khôi phục
     $mans_restore = (int)$_GET['khoiphuc_mans'];
     $new_status = 1; // Trạng thái mới là 1 (hiện/làm lại)

     // Chỉ admin cấp 0 hoặc kế toán (level 1) mới được khôi phục?
     if (Session::get('adminlevel') == 0 || Session::get('adminlevel') == 1) {
         // Gọi hàm anHienNhanSu với trạng thái mới là 1
         $restoreMsg = $nhansu_class->anHienNhanSu($mans_restore, $new_status);
     } else {
         $restoreMsg = "<span class='error' style='color:red;'>Bạn không có quyền khôi phục nhân sự!</span>";
     }
      // Xóa tham số khỏi URL sau khi xử lý
     echo "<script>window.history.replaceState(null, null, window.location.pathname);</script>";
}

// --- Xử lý yêu cầu Xóa Vĩnh Viễn ---
if (isset($_GET['delete_perm_mans'])) { // Tham số để xóa vĩnh viễn
    // Thêm bước xác nhận bằng JavaScript
    if (!isset($_GET['confirm_perm']) || $_GET['confirm_perm'] !== 'yes') {
        $mans_confirm_perm = (int)$_GET['delete_perm_mans'];
        echo "<script>
            if(confirm('⛔ CẢNH BÁO CỰC KỲ NGHIÊM TRỌNG!\\nBạn có chắc chắn muốn XÓA VĨNH VIỄN nhân sự này (Mã NS: " . $mans_confirm_perm . ")?\\nHành động này sẽ xóa TẤT CẢ dữ liệu liên quan và KHÔNG THỂ HOÀN TÁC!')) {
                window.location.href = '?delete_perm_mans=" . $mans_confirm_perm . "&confirm_perm=yes';
            } else {
                window.location.href = 'quanlynhansu_hidden_list.php'; // Quay lại trang ẩn nếu hủy
            }
        </script>";
        exit;
    } else {
        // Nếu đã xác nhận (confirm_perm=yes)
        $mans_to_delete_perm = (int)$_GET['delete_perm_mans'];
        // Chỉ admin cấp 0 mới được xóa vĩnh viễn
        if (Session::get('adminlevel') == 0) {
             $deletePermMsg = $nhansu_class->xoaVinhVienNhanSu($mans_to_delete_perm);
        } else {
             $deletePermMsg = "<span class='error' style='color:red;'>Bạn không có quyền xóa vĩnh viễn nhân sự!</span>";
        }
         // Xóa tham số khỏi URL sau khi xử lý
        echo "<script>window.history.replaceState(null, null, window.location.pathname);</script>";
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    /* Ảnh đại diện */
    .table-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd; display: block; margin: auto; opacity: 0.7; /* Làm mờ ảnh */ }
    /* Nút bấm chung */
    .btn-action { display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 5px; font-size: 13px; text-decoration: none; transition: background-color 0.3s ease, color 0.3s ease, transform 0.1s ease; text-align: center; border: none; cursor: pointer; font-weight: 500; color: white; }
    .btn-action:active { transform: scale(0.95); }
    /* Các màu nút */
    .btn-restore { background-color: #20c997; } .btn-restore:hover { background-color: #1aa07a; } /* Nút Khôi phục */
    .btn-delete-perm { background-color: #dc3545; } .btn-delete-perm:hover { background-color: #a71d2a; } /* Nút Xóa vĩnh viễn */
    /* Trạng thái */
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; min-width: 80px; }
    .status-inactive { background-color: #6c757d; }
    /* Căn giữa */
    td, th { text-align: center !important; vertical-align: middle !important; }
    th { font-size: 14px; }
    /* CSS DataTables */
    .dataTables_wrapper .dataTables_filter { float: right; margin-bottom: 15px; }
    .dataTables_wrapper .dataTables_filter label { font-weight: 600; color: #333; font-size: 14px; }
    .dataTables_wrapper .dataTables_filter input { padding: 8px 12px; margin-left: 10px; border: 1px solid #ccc; border-radius: 8px; outline: none; font-size: 14px; transition: border-color 0.3s; height: 38px; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: #007bff; }
    .dataTables_wrapper .dataTables_info { font-size: 14px; margin-top: 10px; font-weight: 500; color: #555; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 15px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 6px; background-color: #f1f1f1; border: 1px solid #ddd; font-size: 14px; color: #007bff !important; cursor: pointer; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #007bff; color: white !important; border-color: #007bff; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background-color: #0056b3; color: white !important; border-color: #0056b3; }
    /* Thông báo */
     .message { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
     .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
     .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
     .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>🗑️ Danh sách nhân sự đã ẩn / nghỉ việc</h2>
        <div class="block" id="table-container">

            <?php
            if ($restoreMsg) echo "<p class='message " . (strpos($restoreMsg, 'thành công') !== false ? 'success' : 'error') . "'>$restoreMsg</p>";
            if ($deletePermMsg) echo "<p class='message " . (strpos($deletePermMsg, 'thành công') !== false ? 'success' : 'error') . "'>$deletePermMsg</p>";
            ?>

            <table id="nhanSuHiddenTable" class="display" style="width:100%"> <thead>
                    <tr>
                        <th style="width: 5%;">STT</th>
                        <th style="width: 5%;">Mã NS</th>
                        <th style="width: 10%;">Ảnh</th>
                        <th style="width: 25%;">Họ tên</th>
                        <th style="width: 15%;">Tên đăng nhập</th>
                        <th style="width: 15%;">Chức vụ</th>
                        <th style="width: 10%;">Trạng thái</th>
                        <th style="width: 15%;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Lấy danh sách nhân sự đã ẩn (trangthai = 0)
                    $danhsach_an = $nhansu_class->layDanhSachNhanSu(0);
                    $i = 0;
                    if ($danhsach_an) {
                        while ($row = $danhsach_an->fetch_assoc()) {
                            $i++;
                            ?>
                            <tr style="opacity: 0.7;"> <td><?php echo $i; ?></td>
                                <td><?php echo $row['mans']; ?></td>
                                <td>
                                    <?php if (!empty($row['anh_dai_dien']) && file_exists("../images/avt/" . $row['anh_dai_dien'])): ?>
                                        <img src="../images/avt/<?php echo $row['anh_dai_dien']; ?>" alt="Ảnh" class="table-img">
                                    <?php else: ?>
                                        <img src="../images/avt/default.png" alt="Ảnh" class="table-img">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['hoten']); ?></td>
                                <td><?php echo htmlspecialchars($row['adminuser']); ?></td>
                                <td><?php echo $nhansu_class->layTenVaiTro($row['level']); ?></td>
                                <td><span class='status-badge status-inactive'>Đã nghỉ/Ẩn</span></td>
                                <td>
                                    <?php if (Session::get('adminlevel') == 0 || Session::get('adminlevel') == 1): ?>
                                        <a href="?khoiphuc_mans=<?php echo $row['mans']; ?>" class="btn-action btn-restore" title="Khôi phục (Cho làm lại)" onclick="return confirm('Xác nhận khôi phục nhân sự này (cho làm việc trở lại)?')">Khôi phục</a>
                                    <?php endif; ?>
                                    <?php if (Session::get('adminlevel') == 0): ?>
                                        <a href="?delete_perm_mans=<?php echo $row['mans']; ?>" class="btn-action btn-delete-perm" title="Xóa Vĩnh Viễn">Xóa VV</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        // Hiển thị nếu không có nhân sự nào bị ẩn
                        echo "<tr><td colspan='8'>Không có nhân sự nào trong danh sách ẩn/đã nghỉ.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#nhanSuHiddenTable').DataTable({ // Đổi ID bảng
            pageLength: 10,
            lengthChange: false, // Giống kmlist
            language: {          // Tiếng Việt
                search: "",
                searchPlaceholder: "Tìm nhân sự đã ẩn...",
                paginate: { previous: "Trước", next: "Sau" },
                info: "Hiển thị _START_–_END_ trong tổng số _TOTAL_ nhân sự đã ẩn",
                infoEmpty: "Không có dữ liệu",
                emptyTable: "Không có nhân sự nào đã ẩn",
                zeroRecords: "Không tìm thấy kết quả phù hợp"
            }
        });

        // setSidebarHeight(); // Gọi hàm này nếu bạn có nó
    });
</script>

<?php include 'inc/footer.php'; // Footer chung ?>