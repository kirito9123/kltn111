<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/phong.php'; ?> 
<?php include '../classes/trangthietbi.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
// Khởi tạo đối tượng
$fm = new Format();
$thietbi = new trangthietbi(); // ✅ Dòng này đã được thêm vào để khắc phục lỗi

// Xử lý Khôi phục (restoreid)
if (isset($_GET['restoreid'])) {
    $id = $_GET['restoreid'];
    $thietbi->restore_thietbi($id);
}

// Xử lý Xóa vĩnh viễn (delid)
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $thietbi->delete_thietbi_permanently($id);
}

// Hàm dịch mã trạng thái sang tên hiển thị (Không cần thẻ span màu mè như trang list thường)
function display_tinhtrang_hidden($code) {
    switch ($code) {
        case 1: return 'Hoạt động';
        case 2: return 'Hư hỏng';
        case 3: return 'Cũ';
        default: return 'Không rõ';
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    /* Style tương tự như equipmentlist.php */
    .table-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
    }
    .btn-action {
        display: inline-block;
        padding: 6px 12px;
        margin: 2px;
        border-radius: 5px;
        font-size: 14px;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .btn-restore { background-color: #3498db; color: white; }
    .btn-delete-perm { background-color: #c0392b; color: white; }
    /* Nút Quay lại */
    .btn-back {
        font-size: 13px; 
        padding: 8px 15px; 
        background-color: #95a5a6; /* Màu xám đậm */
        color: white;
        border-radius: 6px;
        text-decoration: none;
        transition: background-color 0.3s;
        margin-right: 10px;
    }
    .btn-back:hover {
        background-color: #7f8c8d;
    }
    .table-header-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    /* ... (Thêm các style DataTables khác nếu cần) ... */
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="table-header-controls">
            <h2>Danh Sách Trang Thiết Bị Đã Ẩn</h2>
            <a href="equipmentlist.php" class="btn-back">← Quay lại danh sách chính</a>
        </div>
        
        <div class="block">  
            <div id="table-container">          
                <table class="data display" id="example">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Thiết Bị</th>
                            <th>Phòng</th>
                            <th>Trạng Thái</th> 
                            <th>Ghi Chú</th>
                            <th>Hình Ảnh</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Gọi hàm lấy danh sách thiết bị đã bị ẩn (xoa = 1)
                        $list_thietbi_an = $thietbi->show_thietbi_an();
                        if ($list_thietbi_an) {
                            $i = 0;
                            while ($result = $list_thietbi_an->fetch_assoc()) {
                                $i++;
                        ?>
                                <tr class="odd gradeX">
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $result['tenthietbi']; ?></td>
                                    <td><?php echo $result['tenphong']; ?></td>
                                    <td><?php echo display_tinhtrang_hidden($result['tinhtrang_trangthietbi']); ?></td> 
                                    <td><?php echo $fm->textShorten($result['ghichu'], 50); ?></td>
                                    <td>
                                        <?php if (!empty($result['hinhanh_thietbi'])): ?>
                                            <img class="table-img" src="../images/equipment/<?php echo $result['hinhanh_thietbi']; ?>" alt="Hình ảnh thiết bị">
                                        <?php else: ?>
                                            (Không ảnh)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a onclick="return confirm('Bạn có chắc muốn KHÔI PHỤC thiết bị này?')" 
                                           href="?restoreid=<?php echo $result['id_thietbi']; ?>" class="btn-action btn-restore">Khôi phục</a> 
                                        | 
                                        <a onclick="return confirm('Bạn CÓ CHẮC muốn XÓA VĨNH VIỄN thiết bị này? Hành động này không thể hoàn tác.')" 
                                           href="?delid=<?php echo $result['id_thietbi']; ?>" class="btn-action btn-delete-perm">Xóa Vĩnh Viễn</a>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        const table = $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm thiết bị...",
                paginate: { previous: "Trang trước", next: "Trang sau" },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ thiết bị đã ẩn",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });
        
        // Thêm bộ lọc nếu cần (tương tự equipmentlist.php)
        // ...
        
        table.on('page.dt', function () {
            $('html, body').animate({
                scrollTop: $('#table-container').offset().top 
            }, 'slow');
        });
    });
</script>

<?php include 'inc/footer.php'; ?>