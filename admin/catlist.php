<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/loaimon.php'; ?>

<?php
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$loai = new loaimon();
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delloai = $loai->del_loai($id);
}
?>

<!-- ✅ Thêm thư viện DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- ✅ CSS giống productlist.php -->
<style>
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

    td, th {
        text-align: center !important;
        vertical-align: middle !important;
    }

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
</style>

<!-- ✅ Giao diện chính -->
<div class="grid_10">
    <div class="box round first grid">
        <h2 style="text-align:center; margin-bottom:20px;">Danh sách loại món</h2>
        <div class="block" id="table-container">
            <?php if (isset($delloai)) echo "<p style='color:green; font-weight:bold;'>$delloai</p>"; ?>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên loại</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $show_loai = $loai->show_loai();
                    if ($show_loai) {
                        $i = 0;
                        while ($result = $show_loai->fetch_assoc()) {
                            $i++;
                    ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo $result['name_loai']; ?></td>
                                <td>
                                    <a class="btn-action btn-edit" href="catedit.php?id_loai=<?php echo $result['id_loai']; ?>">Sửa</a>
                                    <a class="btn-action btn-delete" onclick="return confirm('Bạn có muốn xóa không?')" href="?delid=<?php echo $result['id_loai']; ?>">Xóa</a>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- ✅ Nút xem loại món đã bị ẩn -->
            <?php if (Session::get('adminlogin') && Session::get('adminlevel') == 0): ?>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="catlist_hidden.php"
                    class="btn-action btn-edit"
                    style="font-size: 13px; padding: 6px 10px; background-color: #6c757d;">
                        → Xem loại món đã bị ẩn
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ✅ Cấu hình DataTables -->
<script type="text/javascript">
    $(document).ready(function () {
        $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm loại món...",
                paginate: {
                    previous: "Trang trước",
                    next: "Trang sau"
                },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ loại",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>
