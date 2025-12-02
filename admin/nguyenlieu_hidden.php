<?php 
include 'inc/header.php'; 
include 'inc/sidebar.php'; 
include '../classes/nguyenvatlieu.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Quyền admin + bếp
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập trang này!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$nl = new nguyenvatlieu();

// Xử lý khôi phục
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    $nl->restore_nguyen_lieu($id);

    echo "<script>
        alert('Khôi phục nguyên liệu thành công!');
        window.location = 'nguyenlieu_list.php';
    </script>";
    exit();
}


// Xử lý xóa vĩnh viễn (nếu thích)
if (isset($_GET['harddelete'])) {
    $id = (int)$_GET['harddelete'];
    $nl->hard_delete_nguyen_lieu($id);  // Bạn sẽ thêm hàm này vào class
    echo "<script>
        alert('Xóa vĩnh viễn thành công!');
        window.location = 'nguyenlieu_deleted.php';
    </script>";
    exit();
}

$list_deleted = $nl->show_nguyen_lieu_deleted();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
.thead-blue th { 
    background:#6c757d !important; color:#fff !important; 
    font-size:14px; text-transform:uppercase;
}
.btn-restore { background:#28a745; color:#fff; padding:5px 10px; border-radius:6px; }
.btn-delete { background:#dc3545; color:#fff; padding:5px 10px; border-radius:6px; }
.btn-back { 
    background:#007bff; color:#fff; padding:7px 15px; 
    border-radius:6px; text-decoration:none; font-weight:bold;
}
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách nguyên vật liệu đã xóa</h2>

        <div style="margin-bottom: 15px;">
            <a href="nguyenlieu_list.php" class="btn-back">&larr; Quay lại danh sách</a>
        </div>

        <div class="block">
            <table class="table table-bordered display" id="deletedTable">
                <thead class="thead-blue">
                    <tr>
                        <th>#</th>
                        <th>Tên nguyên liệu</th>
                        <th>Đơn vị</th>
                        <th>Tồn kho</th>
                        <th>Giá nhập TB</th>
                        <th>Ghi chú</th>
                        <th>Khôi phục</th>
                        <th>Xóa vĩnh viễn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($list_deleted) {
                        $i = 0;
                        while ($r = $list_deleted->fetch_assoc()) {
                            $i++;
                            echo "<tr>
                                <td>$i</td>
                                <td>".htmlspecialchars($r['ten_nl'])."</td>
                                <td>{$r['don_vi']}</td>
                                <td>{$r['so_luong_ton']}</td>
                                <td>".number_format($r['gia_nhap_tb'],0,',','.')." VNĐ</td>
                                <td>".htmlspecialchars($r['ghichu'] ?? '')."</td>

                                <td>
                                    <a class='btn-restore'
                                       href='?restore={$r['id_nl']}'
                                       onclick='return confirm(\"Khôi phục nguyên liệu này?\")'>
                                       Khôi phục</a>
                                </td>

                                <td>
                                    <a class='btn-delete'
                                       href='?harddelete={$r['id_nl']}'
                                       onclick='return confirm(\"Xóa vĩnh viễn? Không thể hoàn tác!\")'>
                                       Xóa</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>Không có dữ liệu.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#deletedTable').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: {
            search: "Tìm kiếm:",
            searchPlaceholder: "nhập tên nguyên liệu...",
            paginate: { previous: "Trước", next: "Sau" },
            emptyTable: "Không có dữ liệu",
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>
