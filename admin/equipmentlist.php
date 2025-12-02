<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/phong.php'; ?> 
<?php include '../classes/trangthietbi.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$fm = new Format();
$thietbi = new trangthietbi();

function display_tinhtrang($code) {
    switch ($code) {
        case 1: return '<span style="color: green; font-weight: bold;">Hoạt động</span>';
        case 2: return '<span style="color: orange; font-weight: bold;">Hư hỏng</span>';
        case 3: return '<span style="color: blue; font-weight: bold;">Cũ</span>';
        default: return 'Không rõ';
    }
}

$delMsg = '';
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $thietbi->del_thietbi($id); 
}
?>

<div class="grid_10">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        /* === 1. FIX LỖI RỚT DÒNG DO DATATABLES (CÁI BẠN ĐANG TÌM) === */
        .dataTables_wrapper {
            clear: none !important; /* Chặn đứng lệnh xuống dòng của thư viện */
        }

        /* === 2. FIX LAYOUT 2 CỘT CHUẨN === */
        .container_12 {
            display: block !important; 
            width: 100% !important;
            overflow: hidden !important; 
        }

        .grid_2 {
            float: left !important;
            width: 230px !important;
            margin: 0 !important;
        }

        .grid_10 {
            float: left !important;
            /* Tính toán: 100% trừ đi Sidebar 230px */
            width: calc(100% - 230px) !important; 
            margin: 0 !important;
            padding: 20px !important;
            box-sizing: border-box !important;
            background: #f4f6f9;
        }

        /* Ẩn các thẻ gây lỗi layout cũ */
        .grid_10 .clear { display: none; }
        .clear { clear: none !important; } /* Cực quan trọng */

        /* === 3. GIAO DIỆN BẢNG === */
        .table-img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
        .btn-action {
            display: inline-block; padding: 6px 12px; margin: 2px;
            border-radius: 5px; font-size: 14px; text-align: center;
            text-decoration: none; cursor: pointer; transition: background-color 0.3s;
        }
        .btn-edit { background-color: #2ecc71; color: white; }
        .btn-delete { background-color: #e74c3c; color: white; }
        
        #example_filter { 
            display: flex; align-items: center; justify-content: flex-end; margin-bottom: 10px;
        }
        #filter-phong, #filter-tinhtrang, #filter-loaiphong {
            width: 160px; margin-left: 12px; border-radius: 6px;
            padding: 8px; border: 1px solid #ccc;
            background-color: #f9f9f9; font-size: 14px;
        }
        .btn-hidden-list {
            font-size: 13px; padding: 8px 15px; 
            background-color: #6c757d; color: white;
            border-radius: 6px; text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-hidden-list:hover { background-color: #5a6268; }
    </style>

    <div class="box round first grid">
        <h2>Danh Sách Trang Thiết Bị</h2>
        <?php if ($delMsg) echo $delMsg; ?>

        <div class="block">
            <div id="table-container">
                <table class="data display" id="example">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên Thiết Bị</th>
                            <th>Phòng</th>
                            <th>Trạng Thái</th> 
                            <th>Ghi Chú</th>
                            <th>Hình Ảnh</th>
                            <th style="display:none;">Loại Phòng</th> 
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $list_thietbi = $thietbi->show_thietbi(); 
                        if ($list_thietbi) {
                            $i = 0;
                            while ($result = $list_thietbi->fetch_assoc()) {
                                $i++;
                        ?>
                                <tr class="odd gradeX">
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $result['tenthietbi']; ?></td>
                                    <td><?php echo $result['tenphong']; ?></td> 
                                    <td><?php echo display_tinhtrang($result['tinhtrang_trangthietbi']); ?></td> 
                                    <td><?php echo $fm->textShorten($result['ghichu'], 50); ?></td>
                                    <td>
                                        <?php if (!empty($result['hinhanh_thietbi'])): ?>
                                            <img class="table-img" src="../images/equipment/<?php echo $result['hinhanh_thietbi']; ?>" alt="Hình ảnh thiết bị">
                                        <?php else: ?>
                                            (Không ảnh)
                                        <?php endif; ?>
                                    </td>
                                    <td style="display:none;"><?php echo $result['tenloaiphong']; ?></td> 
                                    <td>
                                        <a href="equipmentedit.php?id=<?php echo $result['id_thietbi']; ?>" class="btn-action btn-edit">Sửa</a> | 
                                        <a onclick="return confirm('Bạn có chắc muốn ẨN/XÓA thiết bị này? Nó sẽ được chuyển vào danh sách ẩn.')" 
                                            href="?delid=<?php echo $result['id_thietbi']; ?>" class="btn-action btn-delete">Xóa</a>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php 
            $adminLogin = Session::get('adminlogin');
            $adminLevel = Session::get('adminlevel');
            if (isset($adminLogin) && $adminLogin && $adminLevel == 0): 
            ?>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="equipmentlist_hidden.php" class="btn-hidden-list">Danh sách ẩn</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        const table = $('#example').DataTable({
            pageLength: 10, lengthChange: false,
            columnDefs: [{ targets: [6], visible: false }],
            language: {
                search: "", searchPlaceholder: "Tìm thiết bị...",
                paginate: { previous: "Trang trước", next: "Trang sau" },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ thiết bị",
                emptyTable: "Không có dữ liệu"
            }
        });

        // BỘ LỌC
        const loaiphongSelect = $('<select id="filter-loaiphong"></select>').append('<option value="">Tất Cả Loại Phòng</option>');
        table.column(6, { search: 'applied' }).data().unique().sort().each(function (d) { 
            if (d) loaiphongSelect.append('<option value="' + d + '">' + d + '</option>'); 
        });
        
        const phongSelect = $('<select id="filter-phong"></select>').append('<option value="">Tất Cả Phòng</option>');
        table.column(2).data().unique().sort().each(function (d) {
             phongSelect.append('<option value="' + d + '">' + d + '</option>');
        });

        const tinhtrangSelect = $('<select id="filter-tinhtrang"></select>').append('<option value="">Tất Cả Trạng thái</option>');
        ['Hoạt động', 'Hư hỏng', 'Cũ'].forEach(v => tinhtrangSelect.append('<option value="' + v + '">' + v + '</option>'));

        const filterContainer = $('#example_filter');
        filterContainer.append(loaiphongSelect, phongSelect, tinhtrangSelect);
        
        function applyAllFilters() {
            table.column(6).search($('#filter-loaiphong').val() || '', true, false); 
            table.column(2).search($('#filter-phong').val() || '', true, false); 
            const tt = $('#filter-tinhtrang').val();
            table.column(3).search(tt ? '(?=.*' + tt + ')' : '', true, false).draw();
        }

        $('#filter-loaiphong').on('change', function () {
            const loai = $(this).val();
            table.column(6).search(loai, true, false).column(2).search('', true, false).draw();
            $('#filter-phong').empty().append('<option value="">Tất Cả Phòng</option>');
            table.column(2, { search: 'applied' }).data().unique().sort().each(function (d) {
                $('#filter-phong').append('<option value="' + d + '">' + d + '</option>');
            });
            applyAllFilters();
        });
        
        $('#filter-phong, #filter-tinhtrang').on('change', function () { applyAllFilters(); });
    });
</script>

<?php include 'inc/footer.php'; ?>