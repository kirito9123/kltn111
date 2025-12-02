<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/baiviet.php'; ?> 
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$baiviet = new baiviet();

// Xử lý Khôi phục (restoreid)
if (isset($_GET['restoreid'])) {
    $id = $_GET['restoreid'];
    $baiviet->restore_baiviet($id);
}

// Xử lý Xóa vĩnh viễn (delid)
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $baiviet->delete_baiviet_permanently($id);
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<style>
    /* ... (CSS tương tự baivietlist.php) ... */
    .table-wrapper {
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    table.dataTable {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    .img-thumb {
        width: 80px;
        height: auto;
        border-radius: 4px;
        object-fit: cover;
    }
    .btn-action {
        display: inline-block;
        padding: 5px 10px;
        margin: 2px 0;
        border-radius: 4px;
        text-decoration: none;
        color: white;
        font-weight: bold;
        text-align: center;
        transition: background-color 0.3s;
    }
    .btn-restore { background-color: #1abc9c; }
    .btn-restore:hover { background-color: #16a085; }
    .btn-delete-perm { background-color: #e74c3c; }
    .btn-delete-perm:hover { background-color: #c0392b; }
</style>

<div class="grid_10">
    <div class="box round first grid" id="table-container">
        <h2>Danh Sách Bài Viết Đã Ẩn</h2>
        <div class="block">
            <div class="table-wrapper">
                <table class="data display" id="example">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu Đề</th>
                            <th>Ảnh Chính</th>
                            <th>Nội Dung Tóm Tắt</th>
                            <th>Ngày Tạo</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $baivietlist = $baiviet->show_baiviet(1); // Lấy các bài viết có xoa = 1 (Đã ẩn)
                        if ($baivietlist) {
                            $i = 0;
                            while ($result = $baivietlist->fetch_assoc()) {
                                $i++;
                        ?>
                                <tr class="odd gradeX">
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $fm->textShorten($result['ten_baiviet'], 50); ?></td>
                                    <td>
                                        <?php if (!empty($result['anh_chinh'])): ?>
                                            <img src="../images/baiviet/<?php echo $result['anh_chinh']; ?>" alt="Ảnh Chính" class="img-thumb">
                                        <?php else: ?>
                                            <span>(Không ảnh)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $fm->textShorten($result['noidung_tongquan'], 100); ?></td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($result['ngay_tao'])); ?></td>
                                    <td>
                                        <a href="?restoreid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-restore">Khôi Phục</a> | 
                                        <a onclick="return confirm('Bạn CÓ CHẮC muốn XÓA VĨNH VIỄN bài viết này? Hành động này không thể hoàn tác.')" 
                                           href="?delid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-delete-perm">Xóa Vĩnh Viễn</a>
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

<!-- Scripts cho Datatables -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        const table = $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm bài viết đã ẩn...",
                paginate: { previous: "Trang trước", next: "Trang sau" },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ bài viết đã ẩn",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });
        
        table.on('page.dt', function () {
            $('html, body').animate({
                scrollTop: $('#table-container').offset().top 
            }, 'slow');
        });
    });
</script>

<?php include 'inc/footer.php'; ?>
