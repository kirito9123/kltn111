<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/lichdangky.php';

$lich = new lichdangky();

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_xinnghi = $_GET['id'];
    $action = $_GET['action'];
    $status = ($action == 'approve') ? 1 : 2;

    // BẢO MẬT: Chỉ Admin (0) mới được phép Duyệt (status=1)
    if ($status == 1 && Session::get('adminlevel') != 0) {
        echo "<script>alert('Bạn không có quyền DUYỆT đơn nghỉ!'); window.location='xinnghi_list.php';</script>";
        exit;
    }

    if ($lich->process_leave_request($id_xinnghi, $status)) {
        echo "<script>alert('Xử lý thành công!'); window.location='xinnghi_list.php';</script>";
    } else {
        echo "<script>alert('Có lỗi xảy ra!');</script>";
    }
}

$requests = $lich->get_leave_requests();
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh Sách Xin Nghỉ</h2>
        <div style="padding: 10px 15px;">
            <a href="xinnghi_add.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">+ Tạo Yêu Cầu Mới</a>
        </div>
        <div class="block">
            <table class="data display datatable" id="example">
                <thead>
                    <tr>
                        <th>Ngày Tạo</th>
                        <th>Nhân Viên</th>
                        <th>Ngày Nghỉ</th>
                        <th>Ca</th>
                        <th>Lý Do</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($requests) {
                        while ($row = $requests->fetch_assoc()) {
                            echo "<tr class='odd gradeX'>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['ngay_tao'])) . "</td>";
                            echo "<td>" . $row['hoten'] . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($row['ngay'])) . "</td>";
                            echo "<td>" . $row['ten_ca'] . "</td>";
                            echo "<td>" . $row['ly_do'] . "</td>";

                            $status_text = "Chờ duyệt";
                            $status_color = "orange";
                            if ($row['trang_thai'] == 1) {
                                $status_text = "Đã duyệt";
                                $status_color = "green";
                            } elseif ($row['trang_thai'] == 2) {
                                $status_text = "Từ chối";
                                $status_color = "red";
                            }

                            echo "<td style='color:$status_color; font-weight:bold;'>$status_text</td>";

                            echo "<td>";
                            if ($row['trang_thai'] == 0) {
                                // Chỉ Admin (0) mới thấy nút Duyệt
                                if (Session::get('adminlevel') == 0) {
                                    echo "<a href='?action=approve&id=" . $row['id_xinnghi'] . "' onclick=\"return confirm('Bạn có chắc muốn DUYỆT?');\">[Duyệt]</a> ";
                                }
                                // Ai vào được trang này cũng thấy nút Từ chối (Hủy duyệt)
                                echo "<a href='?action=reject&id=" . $row['id_xinnghi'] . "' onclick=\"return confirm('Bạn có chắc muốn TỪ CHỐI?');\">[Từ chối]</a>";
                            } else {
                                echo "Đã xử lý";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        setupLeftMenu();
        $('.datatable').dataTable();
        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>