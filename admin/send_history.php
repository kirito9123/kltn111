<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/cart.php';

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Lấy lịch sử email
$query = "SELECT pe.id, pe.subject, pe.message, pe.recipients, pe.sent_at, a.Name_admin
          FROM promotion_emails pe
          INNER JOIN tb_admin a ON pe.admin_id = a.id_admin
          ORDER BY pe.sent_at DESC";
$result = $db->select($query);
?>

<!-- DataTables + jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    .table-img {
        width: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .btn-action {
        display: inline-block;
        padding: 6px 12px;
        margin: 2px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
        transition: 0.3s;
    }

    td, th {
        text-align: center;
        vertical-align: middle;
    }

    .dataTables_filter input {
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 14px;
        outline: none;
    }

    .dataTables_filter input:focus {
        border-color: #007bff;
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

    .no-data {
        color: #dc3545;
        font-weight: bold;
        padding: 20px;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Lịch sử gửi Mail đã gửi</h2>
        <div class="block" id="table-container">
            <?php if (!$result || $result->num_rows === 0) { ?>
                <p class="no-data">Chưa có email khuyến mãi nào được gửi.</p>
            <?php } else { ?>
                <table id="example" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Nội dung</th>
                            <th>Người nhận</th>
                            <th>Thời gian gửi</th>
                            <th>Tên Admin gửi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                                <td>
                                    <?php
                                    $recipients = explode(',', $row['recipients']);
                                    $emails = array_filter($recipients, fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL));
                                    echo implode('<br>', array_map('htmlspecialchars', $emails));
                                    ?>
                                </td>
                                <td><?= $row['sent_at'] ?></td>
                                <td><?= htmlspecialchars($row['Name_admin']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<!-- DataTables script -->
<script>
    $(document).ready(function () {
        $('#example').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "",
                searchPlaceholder: "Tìm tiêu đề, nội dung...",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                },
                info: "Hiển thị _START_–_END_ trong _TOTAL_ bản ghi",
                emptyTable: "Chưa có dữ liệu",
                infoEmpty: "Không có dữ liệu hiển thị"
            }
        });
    });
</script>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>
