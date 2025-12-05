<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/user.php'; ?>
<?php include_once '../helpers/format.php'; ?>

<?php
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$fm = new Format();
$kh = new user();

if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $del_user = $kh->soft_delete_user($id);
}
?>

<!-- Thư viện DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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

    td,
    th {
        text-align: center !important;
        vertical-align: middle !important;
    }

    .nowrap {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách khách hàng</h2>
        <div class="block" id="table-container">
            <?php if (isset($del_user)) echo "<p style='color:green; font-weight:bold;'>$del_user</p>"; ?>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Giới tính</th>
                        <th>Số lần đặt</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $userList = $kh->show_all();
                    if ($userList) {
                        $i = 0;
                        while ($row = $userList->fetch_assoc()) {
                            $i++;
                    ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= htmlspecialchars($row['ten']) ?></td>
                                <td class="nowrap"><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['sodienthoai'] ?></td>
                                <td><?= $row['gioitinh'] == 1 ? 'Nam' : 'Nữ' ?></td>
                                <td><?= $row['solandat'] ?? '0' ?></td>
                                <td><?= $fm->textShorten($row['ghichu'] ?? '', 20) ?></td>
                                <td>
                                    <a class="btn-action btn-edit" href="customeredit.php?id=<?= $row['id'] ?>">Sửa</a>
                                    <a class="btn-action btn-delete" onclick="return confirm('Bạn muốn ẩn khách này?')" href="customerlist_hidden.php?id=<?= $row['id'] ?>">Xóa</a>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="8">Không có khách hàng nào.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <?php if (Session::get('adminlogin') && Session::get('adminlevel') == 0): ?>
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="customerlist_hidden.php" class="btn-action btn-delete" style="margin-right: 10px;">
                        Xem khách hàng bị ẩn
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Cấu hình DataTables -->
<script type="text/javascript">
    $(document).ready(function() {
        const table = $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm khách hàng...",
                paginate: {
                    previous: "Trang trước",
                    next: "Trang sau"
                },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ khách hàng",
                emptyTable: "Không có dữ liệu",
                infoEmpty: "Không có dữ liệu để hiển thị"
            }
        });

        table.on('page.dt', function() {
            $('html, body').animate({
                scrollTop: $('#table-container').offset().top - 20
            }, 400);
        });

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>