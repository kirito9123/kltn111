<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../lib/database.php';
require_once '../helpers/format.php';
ob_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

// Kiểm tra thông tin đặt bàn từ session
if (!isset($_SESSION['booking_info'])) {
    header('Location: create_booking.php');
    exit();
}

$booking_info = $_SESSION['booking_info'];
$id_user = $booking_info['id_user'];
$time = $booking_info['time'];
$date = $booking_info['date'];
$khach = $booking_info['khach'];
$noidung = $booking_info['noidung'];
$payment_method = $booking_info['payment_method'];
$selected_mons = $booking_info['selected_mons'];
$total_amount = $booking_info['total_amount'];

// Lấy thông tin khách hàng
$db = new Database();
$user_query = "SELECT ten FROM khach_hang WHERE id = '$id_user'";
$user_result = $db->select($user_query);
$customer_name = $user_result ? $user_result->fetch_assoc()['ten'] : 'Khách hàng';

// Nếu xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['booking_info']); // Xóa thông tin tạm
    header('Location: danhsachdatban.php');
    exit();
}
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Xác nhận thanh toán</h2>
        <div class="block">
            <h4>Thông tin đặt bàn</h4>
            <p><strong>Khách hàng:</strong> <?php echo $customer_name; ?></p>
            <p><strong>Ngày:</strong> <?php echo $date; ?></p>
            <p><strong>Thời gian:</strong> <?php echo $time; ?></p>
            <p><strong>Số lượng khách:</strong> <?php echo $khach; ?></p>
            <p><strong>Nội dung:</strong> <?php echo $noidung; ?></p>

            <h4>Danh sách món ăn</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tên món</th>
                        <th>Số lượng</th>
                        <th>Giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selected_mons as $mon) { ?>
                        <tr>
                            <td><?php echo $mon['name_mon']; ?></td>
                            <td><?php echo $mon['soluong']; ?></td>
                            <td><?php echo $mon['gia_mon']; ?> VNĐ</td>
                            <td><?php echo $mon['thanhtien']; ?> VNĐ</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <h4>Tổng tiền: <?php echo $total_amount; ?> VNĐ</h4>
            <h4>Phương thức thanh toán: <?php echo $payment_method == 'cash' ? 'Tiền mặt' : 'QR'; ?></h4>

            <?php if ($payment_method == 'qr') { ?>
                <h4>Mã QR thanh toán</h4>
                <p>Quét mã QR bên dưới để thanh toán:</p>
                <!-- Tạo mã QR tĩnh (có thể thay bằng API VietQR hoặc dịch vụ khác) -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("Thanh toan don hang $total_amount VND cho $customer_name"); ?>" alt="QR Code">
            <?php } else { ?>
                <p>Vui lòng chuẩn bị số tiền mặt: <?php echo $total_amount; ?> VNĐ</p>
            <?php } ?>

            <form action="" method="post">
                <button type="submit" class="btn btn-success mt-3">Xác nhận thanh toán</button>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>