<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/loaimon.php'; ?>
<?php include '../classes/mon.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
$fm = new Format();
$monan = new mon();

if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delmon = $monan->del_mon($id);
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

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: bold;
        color: white;
    }

    .status-active {
        background-color: #28a745;
    }

    .status-inactive {
        background-color: #6c757d;
    }

    td, th {
        text-align: center !important;
        vertical-align: middle !important;
    }

	/* Làm đẹp ô tìm kiếm */
.dataTables_filter {
    float: right;
    margin-bottom: 15px;
}

.dataTables_filter label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.dataTables_filter input {
    padding: 8px 12px;
    margin-left: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
    transition: border-color 0.3s;
}

.dataTables_filter input:focus {
    border-color: #007bff;
}

/* Làm đẹp phần info: "Hiển thị... trong tổng số" */
.dataTables_info {
    font-size: 14px;
    margin-top: 10px;
    font-weight: 500;
    color: #555;
}

/* Làm đẹp phần phân trang */
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
    transition: border-color 0.3s ease;
    height: 38px;
}

#filter-loai:focus {
    border-color: #007bff;
    box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
    background-color: #fff;
}

/* Làm đẹp ô tìm kiếm giống dropdown danh mục */
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


</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách món ăn</h2>
        <div class="block" id="table-container">
            <?php if (isset($delmon)) echo "<p style='color:green; font-weight:bold;'>$delmon</p>"; ?>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên món</th>
                        <th>Loại</th>
                        <th>Giá</th>
                        <th>Ghi chú</th>
                        <th>Hình ảnh</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $listmon = $monan->show_mon();
                    if ($listmon) {
                        $i = 0;
                        while ($result = $listmon->fetch_assoc()) {
                            $i++;
                    ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo $result['name_mon']; ?></td>
                                <td><?php echo $result['name_loai']; ?></td>
                                <td><?php echo number_format($result['gia_mon']) . ' đ'; ?></td>
                                <td><?php echo $fm->textShorten($result['ghichu_mon'], 20); ?></td>
                                <td>
                                    <img class="table-img" src="../images/food/<?php echo $result['images']; ?>" alt="Ảnh món ăn">
                                </td>
                                <td>
                                    <?php
                                    echo $result['tinhtrang'] == 1
                                        ? "<span class='status-badge status-active'>Phục vụ</span>"
                                        : "<span class='status-badge status-inactive'>Ngưng phục vụ</span>";
                                    ?>
                                </td>
                                <td>
                                    <a class="btn-action btn-edit" href="productedit.php?id_mon=<?php echo $result['id_mon']; ?>">Sửa</a>
                                    <a class="btn-action btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa món này?')" href="?delid=<?php echo $result['id_mon']; ?>">Xóa</a>
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
                    <a href="productlist_hidden.php"
                    class="btn-action btn-edit"
                    style="font-size: 13px; padding: 6px 10px; background-color: #6c757d;">
                        → Xem món ăn đã bị ẩn
                    </a>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Cấu hình DataTables -->
<script type="text/javascript">
  $(document).ready(function () {
    const table = $('#example').DataTable({
      pageLength: 10,
      lengthChange: false,
      stateSave: true,
      stateDuration: 0,
      stateLoadParams: function (settings, data) {
        data.length = 10;
      },
      language: {
        search: "",
        searchPlaceholder: "Tìm món ăn...",
        paginate: { previous: "Trang trước", next: "Trang sau" },
        info: "Hiển thị _START_–_END_ trong _TOTAL_ món ăn",
        emptyTable: "Không có dữ liệu",
        infoEmpty: "Không có dữ liệu để hiển thị"
      },

      stateSaveParams: function (settings, data) {
        data.customLoai = $('#filter-loai').val() || '';
      }
    });

    table.page.len(10).draw(false);
    table.state.save();

    const loaiSelect = $('<select id="filter-loai" class="form-control" style="width: 160px; margin-left: 12px; border-radius: 6px;"></select>')
      .append('<option value="">Tất cả</option>');

    table.column(2).data().unique().sort().each(function (d) {
      loaiSelect.append('<option value="' + d + '">' + d + '</option>');
    });

    $('#example_filter').append(loaiSelect);

    const saved = table.state.loaded();
    if (saved && saved.customLoai) {
      $('#filter-loai').val(saved.customLoai);
      table.column(2).search(saved.customLoai).draw(false);
    }

    $('#filter-loai').on('change', function () {
      table.column(2).search(this.value).draw(false);
      table.state.save();
    });

    table.on('page.dt', function () {
      $('html, body').animate({ scrollTop: $('#table-container').offset().top - 20 }, 400);
    });

    setSidebarHeight();
  });
</script>






<?php include 'inc/footer.php'; ?>
