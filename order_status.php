<?php
include 'inc/header.php';
include 'inc/sidebar.php';
require_once 'lib/database.php';
require_once 'helpers/format.php';

$db = new Database();
$fm = new Format();

$orderId = $fm->validation($_GET['order_id']);
$status = $fm->validation($_GET['status']);
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Trạng thái thanh toán</h2>
        <div class="block">
            <p>Đơn hàng ID: <?php echo $orderId; ?></p>
            <p>Trạng thái: <?php echo $status == 'completed' ? 'Thành công' : 'Thất bại'; ?></p>
            <a href="index.php">Quay lại trang chủ</a>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>