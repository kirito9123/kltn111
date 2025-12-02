<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/khuyenmai.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$km = new khuyenmai();

if (isset($_GET['restoreid'])) {
    $id = $_GET['restoreid'];
    $km->restore_km($id);
}
?>

<!-- DataTables -->
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

    .btn-restore {
        background-color: #28a745;
        color: white;
    }

    .btn-restore:hover {
        background-color: #1e7e34;
    }

    .btn-back {
        background-color: #6c757d;
        color: white;
    }

    .btn-back:hover {
        background-color: #5a6268;
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
        <h2>Danh sách khuyến mãi đã bị ẩn</h2>
        <div class="block">

            <!-- Nút quay lại -->
            <div style="margin-bottom: 15px; text-align: left;">
                <a href="kmlist.php" class="btn-action btn-back">← Quay lại danh sách khuyến mãi</a>
            </div>

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
                    $listkm = $km->show_km_deleted();
                    if ($listkm && $listkm->num_rows > 0) {
                        $i = 0;
                        while ($result = $listkm->fetch_assoc()) {
                            $i++;
                            echo "<tr>
                                <td>{$i}</td>
                                <td>{$result['name_km']}</td>
                                <td>{$result['discout']}%</td>
                                <td>{$result['time_star']}</td>
                                <td>{$result['time_end']}</td>
                                <td>{$fm->textShorten($result['ghichu'], 20)}</td>
                                <td><img class='table-img' src='../images/food/{$result['images']}' alt='Ảnh'></td>
                                <td><a class='btn-action btn-restore' href='?restoreid={$result['id_km']}' onclick='return confirm(\"Khôi phục khuyến mãi này?\")'>Khôi phục</a></td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>Không có khuyến mãi nào bị ẩn.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

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
                info: "Hiển thị _START_–_END_ trong _TOTAL_ khuyến mãi bị ẩn",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>
