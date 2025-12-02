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

if (isset($_GET['restoreid'])) {
    $id = $_GET['restoreid'];
    $restore_user = $kh->restore_user($id);
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
        padding: 8px 18px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
        margin-bottom: 15px;
        display: inline-block;
    }

    .btn-back:hover {
        background-color: #5a6268;
    }

    td, th {
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
        <h2>Khách hàng đã bị ẩn</h2>
        
        <div class="block" id="table-container">
            <?php if (isset($restore_user)) echo "<p style='color:green; font-weight:bold;'>$restore_user</p>"; ?>

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
                    $userList = $kh->show_deleted_users();
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
                                    <a class="btn-action btn-restore" onclick="return confirm('Khôi phục khách hàng này?')" href="?restoreid=<?= $row['id'] ?>">Khôi phục</a>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="8">Không có khách hàng nào bị ẩn.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div style="text-align: right; margin-bottom: 15px;">
                <a href="customerlist.php" class="btn-action btn-back" style="margin-right: 10px;">← Quay lại danh sách</a>
            </div>

        </div>
    </div>
</div>

<!-- Cấu hình DataTables -->
<script type="text/javascript">
    $(document).ready(function () {
        $('#example').DataTable({
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

        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>
