<?php
// FILE: admin/quanlynhansu_list.php

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

// ========== XỬ LÝ ACTION ẨN (Hide) ==========
$hideMsg = ''; // Biến lưu thông báo ẩn
if (isset($_GET['an_mans'])) { // Tham số để ẩn
     $mans_hide = (int)$_GET['an_mans'];
     $new_status = 0; // Trạng thái mới là 0 (ẩn/nghỉ)

     // Chỉ admin cấp 0 hoặc kế toán (level 1) mới được ẩn?
     if (Session::get('adminlevel') == 0 || Session::get('adminlevel') == 1) {
         // Gọi hàm anHienNhanSu với trạng thái mới là 0
         $hideMsg = $nhansu_class->anHienNhanSu($mans_hide, $new_status);
     } else {
         $hideMsg = "<span class='error' style='color:red;'>Bạn không có quyền ẩn nhân sự!</span>";
     }
      // Xóa tham số khỏi URL sau khi xử lý
     echo "<script>window.history.replaceState(null, null, window.location.pathname);</script>";
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    /* Ảnh đại diện */
    .table-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd; display: block; margin: auto; }
    /* Nút bấm chung */
    .btn-action { display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 5px; font-size: 13px; text-decoration: none; transition: background-color 0.3s ease, color 0.3s ease, transform 0.1s ease; text-align: center; border: none; cursor: pointer; font-weight: 500; color: white; /* Mặc định chữ trắng */ }
    .btn-action:active { transform: scale(0.95); }
    /* Các màu nút */
    .btn-add { background-color: #0d6efd; } .btn-add:hover { background-color: #0b5ed7; }
    .btn-view { background-color: #17a2b8; } .btn-view:hover { background-color: #138496; }
    .btn-edit { background-color: #ffc107; color: #212529 !important; } .btn-edit:hover { background-color: #e0a800; } /* Nút sửa chữ đen */
    .btn-hide { background-color: #6c757d; } .btn-hide:hover { background-color: #5a6268;} /* Nút Ẩn */
    /* Trạng thái */
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; min-width: 80px; }
    .status-active { background-color: #28a745; }
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
    /* Nút xem danh sách ẩn */
     .btn-hidden-list { font-size: 13px; padding: 8px 15px; background-color: #6c757d; color: white; border-radius: 6px; text-decoration: none; transition: background-color 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; margin-top: 15px; }
     .btn-hidden-list:hover { background-color: #5a6268; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>👥 Danh sách nhân sự (Đang hoạt động)</h2>
        <div class="block" id="table-container">

            <?php if ($hideMsg) echo "<p class='message " . (strpos($hideMsg, 'thành công') !== false ? 'success' : 'error') . "'>$hideMsg</p>"; ?>

            <table id="nhanSuTable" class="display" style="width:100%">
                <thead>
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
                    // Lấy danh sách nhân sự đang làm việc (trangthai = 1)
                    $danhsach = $nhansu_class->layDanhSachNhanSu(1);
                    $i = 0;
                    if ($danhsach) {
                        while ($row = $danhsach->fetch_assoc()) {
                            $i++;
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
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
                                <td><span class='status-badge status-active'>Đang làm</span></td>
                                <td>
                                    <a href="xemnhansu_chitiet.php?mans=<?php echo $row['mans']; ?>" class="btn-action btn-view" title="Xem chi tiết">Xem</a>
                                    <?php if (Session::get('adminlevel') == 0): ?>
                                        <a href="suanhansu.php?mans=<?php echo $row['mans']; ?>" class="btn-action btn-edit" title="Sửa thông tin">Sửa</a>
                                    <?php endif; ?>
                                    <?php if (Session::get('adminlevel') == 0 || Session::get('adminlevel') == 1): ?>
                                        <a href="?an_mans=<?php echo $row['mans']; ?>" class="btn-action btn-hide" title="Ẩn nhân sự (Cho nghỉ)" onclick="return confirm('Xác nhận ẩn nhân sự này (cho nghỉ việc)?')">Ẩn</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        // Hiển thị nếu không có nhân sự nào đang làm
                        echo "<tr><td colspan='8'>Không có nhân sự nào đang hoạt động.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div style="text-align: right;">
                <a href="quanlynhansu_hidden_list.php" class="btn-hidden-list">
                    Xem danh sách nhân sự đã ẩn/nghỉ
                </a>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#nhanSuTable').DataTable({
            pageLength: 10,       // Số dòng mặc định
            lengthChange: false, // Bỏ tùy chọn thay đổi số dòng
            language: {          // Tiếng Việt
                search: "",
                searchPlaceholder: "Tìm kiếm nhân sự...",
                paginate: { previous: "Trước", next: "Sau" },
                info: "Hiển thị _START_–_END_ trong tổng số _TOTAL_ nhân sự",
                infoEmpty: "Không có dữ liệu",
                emptyTable: "Không có nhân sự nào đang hoạt động",
                zeroRecords: "Không tìm thấy kết quả phù hợp"
            }
            // Không cần bộ lọc chức vụ ở trang này (đơn giản giống kmlist)
        });

        // setSidebarHeight(); // Gọi hàm này nếu bạn có nó
    });
</script>

<?php include 'inc/footer.php'; // Footer chung ?>