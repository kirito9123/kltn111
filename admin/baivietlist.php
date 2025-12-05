<?php
// FILE: admin/baivietlist.php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/baiviet.php';
include_once '../helpers/format.php';

$fm = new Format();
$baiviet = new baiviet();

// Xử lý ẩn
$delMsg = '';
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delResult = $baiviet->del_baiviet($id);
    if ($delResult) $delMsg = $delResult;
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />

<style>
    .table-wrapper {
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .img-thumb {
        width: 60px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }

    .badge-cat {
        background: #17a2b8;
        color: #fff;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        white-space: nowrap;
    }

    .btn-action {
        padding: 4px 8px;
        border-radius: 4px;
        color: white;
        text-decoration: none;
        font-size: 12px;
        margin-right: 2px;
    }

    .btn-edit {
        background-color: #007bff;
    }

    .btn-del {
        background-color: #dc3545;
    }
</style>

<div class="grid_10">
    <div class="box round first grid" id="table-container">
        <h2>Danh Sách Bài Viết</h2>
        <?php echo $delMsg; ?>

        <div class="block">
            <div class="table-wrapper">
                <table class="data display" id="example">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="25%">Tiêu Đề</th>
                            <th width="15%">Thể Loại</th>
                            <th width="10%">Hình Ảnh</th>
                            <th width="25%">Tóm Tắt</th>
                            <th width="10%">Ngày Tạo</th>
                            <th width="10%">Xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $baivietlist = $baiviet->show_baiviet(0);
                        if ($baivietlist) {
                            $i = 0;
                            while ($result = $baivietlist->fetch_assoc()) {
                                $i++;
                                // Lấy tên thể loại
                                $catName = $baiviet->get_category_name($result['theloai']);
                        ?>
                                <tr class="odd gradeX">
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $fm->textShorten($result['ten_baiviet'], 40); ?></td>
                                    <td><span class="badge-cat"><?php echo $catName; ?></span></td>
                                    <td>
                                        <?php if ($result['anh_chinh']): ?>
                                            <img src="../images/baiviet/<?php echo $result['anh_chinh']; ?>" class="img-thumb">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $fm->textShorten($result['noidung_tongquan'], 50); ?></td>
                                    <td><?php echo date('d/m/y', strtotime($result['ngay_tao'])); ?></td>
                                    <td>
                                        <a href="baivietedit.php?baivietid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-edit">Sửa</a>
                                        <a onclick="return confirm('Bạn có chắc muốn ẨN bài viết này?')" href="?delid=<?php echo $result['id_baiviet']; ?>" class="btn-action btn-del">Ẩn</a>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <a href="baivietlist_hidden.php" style="color:#666; font-size:13px;">Xem thùng rác (Bài đã ẩn)</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#example').DataTable({
            pageLength: 10,
            language: {
                search: "Tìm kiếm:",
                paginate: {
                    next: "Sau",
                    previous: "Trước"
                },
                info: "Hiện _START_ đến _END_ của _TOTAL_ bài"
            }
        });
    });
</script>

<?php include 'inc/footer.php'; ?>