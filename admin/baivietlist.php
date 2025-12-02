<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/baiviet.php'; ?> 
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$baiviet = new baiviet();

// Xử lý ẩn (chuyển xoa = 1)
$delMsg = '';
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delResult = $baiviet->del_baiviet($id); 
    if ($delResult) {
        $delMsg = '<span style="color: green; font-weight: bold;">Đã chuyển bài viết ID ' . $id . ' vào danh sách ẩn thành công.</span>';
    } else {
        $delMsg = '<span style="color: red; font-weight: bold;">Ẩn bài viết thất bại.</span>';
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
<style>
    /* ... (CSS cho bảng) ... */
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
    /* Style cho cột Action */
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
    .btn-edit { background-color: #3498db; }
    .btn-edit:hover { background-color: #2980b9; }
    .btn-delete { background-color: #e74c3c; }
    .btn-delete:hover { background-color: #c0392b; }

    /* STYLE MỚI: Cho nút Khôi phục/Ẩn */
    .btn-hidden-list {
        font-size: 13px; 
        padding: 8px 15px; 
        background-color: #6c757d; /* Màu xám */
        color: white;
        border-radius: 6px;
        text-decoration: none;
        transition: background-color 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block; /* Quan trọng để căn phải */
        margin-top: 15px; /* Tạo khoảng cách với bảng */
    }
    .btn-hidden-list:hover {
        background-color: #5a6268;
    }
    /* End STYLE MỚI */
</style>

<div class="grid_10">
    <div class="box round first grid" id="table-container">
        <h2>Danh Sách Bài Viết (Đang Hiển Thị)</h2>
        
        <?php 
        // Hiển thị thông báo sau khi ẩn
        if ($delMsg) {
            echo '<p style="padding: 10px; background: #e0f7fa; border-left: 5px solid #00bcd4; margin-bottom: 15px;">' . $delMsg . '</p>';
        }
        ?>

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
                        // Sửa tên phương thức lấy dữ liệu nếu cần, hiện tại là show_baiviet(0)
                        $baivietlist = $baiviet->show_baiviet(0); // Lấy các bài viết có xoa = 0
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
                                        <a href="baivietedit.php?baivietid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-edit">Sửa</a> | 
                                        <a onclick="return confirm('Bạn có chắc muốn ẨN bài viết này? Bài viết sẽ được chuyển vào trang khôi phục.')" 
                                            href="?delid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-delete">Xóa</a>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- VỊ TRÍ NÚT CHUYỂN TRANG KHÔI PHỤC - CĂN PHẢI -->
            <div style="text-align: right;">
                <a href="baivietlist_hidden.php"
                class="btn-hidden-list"
                >Khôi phục bài viết đã xóa
                </a>
            </div>
            <!-- KẾT THÚC VỊ TRÍ NÚT -->

        </div>
    </div>
</div>

<!-- Scripts cho Datatables -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm bài viết...",
                paginate: { previous: "Trang trước", next: "Trang sau" },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ bài viết",
                emptyTable: "Không có bài viết nào đang hiển thị",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        $('#example').on('page.dt', function () {
            $('html, body').animate({
                scrollTop: $('#table-container').offset().top 
            }, 'slow');
        });
    });
</script>

<?php include 'inc/footer.php'; ?>
