<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/loaimon.php'; ?>
<?php include '../classes/khuyenmai.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$km = new khuyenmai();

if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delkm = $km->del_km($id);
}
?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    .table-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
        display: block;
        margin: auto;
    }

    td, th {
        text-align: center !important;
        vertical-align: middle !important;
    }

    .btn-action {
        display: inline-block;
        padding: 6px 12px;
        margin: 2px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-edit {
        background-color: #007bff;
        color: white;
    }

    .btn-edit:hover {
        background-color: #0056b3;
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background-color: #a71d2a;
    }

    .dataTables_filter input[type="search"] {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        height: 38px;
    }

    .dataTables_info {
        font-size: 14px;
        margin-top: 10px;
        color: #555;
    }

    .dataTables_paginate .paginate_button {
        padding: 6px 12px;
        margin: 0 2px;
        border-radius: 6px;
        background-color: #f1f1f1;
        border: 1px solid #ddd;
        font-size: 14px;
        color: #007bff !important;
        cursor: pointer;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #007bff;
        color: white !important;
        border-color: #007bff;
    }

    .dataTables_paginate .paginate_button:hover {
        background-color: #0056b3;
        color: white !important;
        border-color: #0056b3;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách khuyến mãi</h2>
        <div class="block">
            <?php if (isset($delkm)) echo "<p style='color:green; font-weight:bold;'>$delkm</p>"; ?>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Giảm giá</th>
                        <th>Bắt đầu</th>
                        <th>Kết thúc</th>
                        <th>Ghi chú</th>
                        <th>Hình ảnh</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $listkm = $km->show_km();
                    if ($listkm) {
                        $i = 0;
                        while ($result = $listkm->fetch_assoc()) {
                            $i++;
                    ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo $result['name_km']; ?></td>
                                <td><?php echo $result['discout']; ?>%</td>
                                <td><?php echo $result['time_star']; ?></td>
                                <td><?php echo $result['time_end']; ?></td>
                                <td><?php echo $fm->textShorten($result['ghichu'], 20); ?></td>
                                <td><img class="table-img" src="../images/food/<?php echo $result['images']; ?>" alt="Ảnh"></td>
                                <td>
                                    <a class="btn-action btn-edit" href="kmedit.php?id_km=<?php echo $result['id_km']; ?>">Sửa</a>
                                    <a class="btn-action btn-delete" onclick="return confirm('Bạn muốn xóa khuyến mãi này?')" href="?delid=<?php echo $result['id_km']; ?>">Xóa</a>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <?php if (Session::get('adminlogin') && Session::get('adminlevel') == 0): ?>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="kmlist_hidden.php" class="btn-action btn-edit" style="background-color: #6c757d;">
                        → Xem khuyến mãi đã bị ẩn
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm khuyến mãi...",
                paginate: {
                    previous: "Trang trước",
                    next: "Trang sau"
                },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ khuyến mãi",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>
