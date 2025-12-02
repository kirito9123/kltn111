<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/mon.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$monan = new mon();

if (isset($_GET['restoreid'])) {
    $id = $_GET['restoreid'];
    $monan->restore_mon($id);
}
?>

<!-- ✅ Thêm thư viện DataTables -->
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

    .btn-action {
        display: inline-block;
        padding: 6px 12px;
        margin: 2px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        text-align: center;
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

    td, th {
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* ... giữ nguyên tất cả phần CSS từ productlist.php ... */

    .dataTables_filter {
        float: right;
        margin-bottom: 15px;
    }

    .dataTables_filter label {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .dataTables_filter input[type="search"] {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fff;
        color: #333;
        height: 38px;
        outline: none;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .dataTables_filter input[type="search"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
    }

    .dataTables_info {
        font-size: 14px;
        margin-top: 10px;
        font-weight: 500;
        color: #555;
    }

    .dataTables_paginate {
        margin-top: 15px;
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

    #filter-loai {
        padding: 7px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fff;
        color: #333;
        margin-left: 12px;
        outline: none;
        height: 38px;
        transition: border-color 0.3s ease;
    }

    #filter-loai:focus {
        border-color: #007bff;
        box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách món ăn đã bị ẩn</h2>
        <div class="block" id="table-container">

            <!-- Nút quay lại -->
            <div style="margin-bottom: 15px; text-align: left;">
                <a href="productlist.php" class="btn-action btn-back">← Quay lại danh sách món ăn</a>
            </div>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên món</th>
                        <th>Loại</th>
                        <th>Giá</th>
                        <th>Ghi chú</th>
                        <th>Hình ảnh</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $listmon = $monan->show_mon_deleted();
                    if ($listmon && $listmon->num_rows > 0) {
                        $i = 0;
                        while ($result = $listmon->fetch_assoc()) {
                            $i++;
                    ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $result['name_mon'] ?></td>
                            <td><?= $result['name_loai'] ?></td>
                            <td><?= number_format($result['gia_mon']) ?> đ</td>
                            <td><?= $fm->textShorten($result['ghichu_mon'], 20) ?></td>
                            <td>
                                <img class="table-img" src="../images/food/<?= $result['images'] ?>" alt="Ảnh món ăn">
                            </td>
                            <td>
                                <a class="btn-action btn-restore" href="?restoreid=<?= $result['id_mon'] ?>" onclick="return confirm('Khôi phục món ăn này?')">Khôi phục</a>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="7">Không có món nào bị ẩn.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ✅ Cấu hình DataTables -->
<script type="text/javascript">
    $(document).ready(function () {
        const table = $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm món ăn...",
                paginate: {
                    previous: "Trang trước",
                    next: "Trang sau"
                },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ món bị ẩn",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        // Bộ lọc theo loại
        const loaiSelect = $('<select id="filter-loai" class="form-control" style="width: 160px;"></select>')
            .append('<option value="">Tất cả</option>');
        table.column(2).data().unique().sort().each(function (d) {
            loaiSelect.append('<option value="' + d + '">' + d + '</option>');
        });
        $('#example_filter').append(loaiSelect);
        $('#filter-loai').on('change', function () {
            table.column(2).search(this.value).draw();
        });

        table.on('page.dt', function () {
            $('html, body').animate({
                scrollTop: $('#table-container').offset().top - 20
            }, 400);
        });

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>
