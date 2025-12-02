<?php
include 'inc/header.php';
Session::checkSession();
ob_start();

// ===== Helpers =====
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function val(array $a=null, ...$keys){
    $a = is_array($a) ? $a : [];
    foreach ($keys as $k){
        if (array_key_exists($k, $a) && $a[$k] !== null && $a[$k] !== '') return $a[$k];
    }
    return '';
}

// ===== Booking id từ GET hoặc session =====
$booking_id =
    isset($_REQUEST['booking_id']) ? (int)$_REQUEST['booking_id'] :
    (int)Session::get('booking_id');

if ($booking_id > 0) Session::set('booking_id', $booking_id);


// ===== Lấy & chuẩn hóa order_data =====
$order_data = Session::get('order_data');
if (!is_array($order_data)) $order_data = [];

// Fallback: nạp lại từ DB nếu mất order_data
if (empty($order_data) && $booking_id > 0) {
    include_once realpath(dirname(__FILE__)) . '/lib/database.php';
    $db = new Database();
    if (isset($db->link)) {
        $rs = $db->select("SELECT * FROM hopdong WHERE id = {$booking_id} LIMIT 1");
        if ($rs && $rs->num_rows > 0) {
            $hd = $rs->fetch_assoc();
            $order_data = [
                'booking_id'     => (int)($hd['id'] ?? 0),
                'time'           => $hd['tg']        ?? '',
                'date'           => $hd['dates']     ?? '',
                'khach'          => $hd['khach']     ?? '',
                'noidung'        => $hd['noidung']   ?? '',
                'payment_status' => $hd['payment_status'] ?? '',
                'payment_method' => $hd['payment_method'] ?? '',
            ];
            Session::set('order_data', $order_data);
        }
    }
}

// Map hiển thị
$time    = val($order_data, 'time','gio_bat_dau','tg');
$date    = val($order_data, 'date','ngay','dates');
$khach   = val($order_data, 'khach','so_nguoi');
$noidung = val($order_data, 'noidung','ghi_chu');

// ===== Chuẩn bị đối tượng/formatter =====
$fmt_money = function($n){ return number_format((float)$n, 0, ',', '.'); };
if (isset($fm) && is_object($fm) && method_exists($fm, 'formatMoney')) {
    $fmt_money = function($n) use ($fm){ return $fm->formatMoney($n); };
}
if (!isset($mon)) {
    @include_once __DIR__ . '/classes/Mon.php';
    if (class_exists('Mon')) { $mon = new Mon(); }
}

// ===== Lấy menu_chon & tính tổng =====
// ===== ƯU TIÊN DÙNG SNAPSHOT (nếu có) =====
    $menu_snapshot = Session::get('menu_snapshot');
    $menu_chon     = Session::get('menu_chon'); // dữ liệu thô (id_mon, soluong) nếu cần fallback

    if (!is_array($menu_snapshot)) $menu_snapshot = [];
    if (!is_array($menu_chon))     $menu_chon     = [];

    $total_amount = 0.0;

    if (!empty($menu_snapshot)) {
        // Đã có đầy đủ name_mon, gia_mon, soluong, thanhtien
        foreach ($menu_snapshot as $line) {
            $total_amount += (float)($line['thanhtien'] ?? 0);
        }
    } else {
        // Fallback: tính lại từ menu_chon + DB (như code cũ của bạn)
        if (!empty($menu_chon) && isset($mon) && method_exists($mon, 'getMonById')) {
            foreach ($menu_chon as $item) {
                $id_mon  = (int)($item['id_mon'] ?? 0);
                $soluong = (int)($item['soluong'] ?? 0);
                $giaFix  = isset($item['gia']) && $item['gia'] !== null ? (float)$item['gia'] : null;

                if ($id_mon <= 0 || $soluong <= 0) continue;

                $gia = 0.0;
                if ($giaFix === null) {
                    $rs = $mon->getMonById($id_mon);
                    if ($rs && $rs->num_rows > 0) {
                        $row = $rs->fetch_assoc();
                        $gia = (float)($row['gia_mon'] ?? 0);
                    }
                } else {
                    $gia = (float)$giaFix;
                }
                $total_amount += ($gia * $soluong);
            }
        }

        // Thêm: nếu vẫn 0, dùng menu_subtotal đã lưu
        if ($total_amount <= 0 && (float)Session::get('menu_subtotal') > 0) {
            $total_amount = (float)Session::get('menu_subtotal');
        }
    }


// ===== Đọc trạng thái thanh toán hiện tại =====
if (!isset($db)) {
    include_once realpath(dirname(__FILE__)) . '/lib/database.php';
    $db = new Database();
}
$current_status = 'pending';
$current_method = '';
if ($booking_id > 0) {
    $rs = $db->select("SELECT payment_status, payment_method FROM hopdong WHERE id = {$booking_id} LIMIT 1");
    if ($rs && $rs->num_rows > 0) {
        $row = $rs->fetch_assoc();
        $current_status = $row['payment_status'] ?? 'pending';
        $current_method = $row['payment_method'] ?? '';
    }
}

$error_msg = null;

// ===== Submit chọn phương thức =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $selected_method = $_POST['payment_method'] ?? '';
    $payment_type    = $_POST['payment_type']    ?? 'full';

    if ($current_status === 'completed') {
        echo "<script>alert('Đơn đã thanh toán trước đó bằng {$current_method}. Nếu cần hỗ trợ, vui lòng liên hệ admin.'); window.location.href='index.php';</script>";
        ob_end_flush(); exit();
    }

    if ($selected_method === 'cash') {
        // Thanh toán tiền mặt: cập nhật trạng thái ngay
        try {
            $update = "UPDATE hopdong 
                       SET payment_status = 'completed', payment_method = 'cash', updated_at = NOW()
                       WHERE id = ? AND (payment_status IS NULL OR payment_status = '' OR payment_status = 'pending' OR payment_status='unpaid')";
            $stmt = $db->link->prepare($update);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();

            // ===== LƯU CHI TIẾT HÓA ĐƠN SAU KHI HOÀN TẤT THANH TOÁN =====
            include_once __DIR__ . '/classes/HopDong.php';
            $hopdong = new HopDong();

            // Chỉ lưu nếu chưa lưu trước đó (hàm saveChiTiet nên tự kiểm tra idempotent)
            $menu_snapshot = Session::get('menu_snapshot');
            if (is_array($menu_snapshot) && !empty($menu_snapshot)) {
                $ok = $hopdong->saveChiTiet($booking_id, $menu_snapshot); // trả true/false
            }

            // Dọn session liên quan đơn
            Session::set('order_payment_info', null);
            Session::set('order_data_deposit', null);
            Session::set('menu_subtotal', null);

            header('Location: success.php');
            ob_end_flush(); exit();
        } catch (Exception $e) {
            $error_msg = "Lỗi cập nhật thanh toán tiền mặt.";
        }

    } elseif ($selected_method === 'vnpay') {
        // Xác định số tiền phải thanh toán
        if ($payment_type === 'deposit') {
            $amount_to_pay = $total_amount * 0.2;
            $order_info    = "Thanh toán đặt cọc đơn đặt bàn #{$booking_id}";
        } else {
            $amount_to_pay = $total_amount;
            $order_info    = "Thanh toán toàn bộ đơn đặt bàn #{$booking_id}";
        }

        // Lưu vào session để vnpay_cre.php đọc
        Session::set('order_data_deposit', $amount_to_pay);  // giữ tương thích biến cũ
        Session::set('order_payment_info', $order_info);
        Session::set('order_amount', $amount_to_pay);
        Session::set('order_booking_id', $booking_id);
        Session::set('order_payment_type', $payment_type);

        header('Location: vnpay_cre.php');
        ob_end_flush(); exit();

    } else {
        $error_msg = "Phương thức thanh toán không hợp lệ.";
    }
}
?>

<!-- Header -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Xác nhận đặt bàn</h1>
                <p class="breadcrumbs">
                    <span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
                    <span>Xác nhận <i class="ion-ios-arrow-forward"></i></span>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Nội dung -->
<section class="ftco-section ftco-no-pt ftco-no-pb">
    <div class="container px-4">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-6 text-center mb-4">
                <img height="130" width="130" src="images/success.png" alt="Success">
                <h3 class="mt-3 text-success">
                    <?php
                    if ($current_status === 'completed') {
                        echo "Đơn đã thanh toán ({$current_method}).";
                    } else {
                        echo "Yêu cầu đặt bàn đã được gửi thành công!";
                    }
                    ?>
                </h3>
                <hr>
                <h4 class="mb-3">Thông tin đặt bàn:</h4>
                <ul class="list-unstyled text-left" style="max-width: 500px; margin: 0 auto; font-size: 16px;">
                    <li><strong>Ngày:</strong> <?php echo h($date); ?></li>
                    <li><strong>Thời gian:</strong> <?php echo h($time); ?></li>
                    <li><strong>Số lượng khách:</strong> <?php echo h($khach); ?></li>
                    <li><strong>Nội dung bữa tiệc:</strong> <?php echo h($noidung); ?></li>
                    <li><strong>Tổng tiền:</strong> <span class="text-danger"><?php echo h($fmt_money($total_amount)) . " VNĐ"; ?></span></li>
                </ul>
                <hr>
            </div>
        </div>

        <?php if ($current_status !== 'completed'): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="payment-form p-4 shadow-sm rounded bg-white">
                    <h4 class="text-center mb-4">Chọn phương thức thanh toán</h4>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger text-center"><?php echo h($error_msg); ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="form-check mb-3 custom-radio-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="vnpay" id="vnpay" required checked>
                            <label class="form-check-label" for="vnpay">
                                Thanh toán qua VNPay
                            </label>
                        </div>

                        <div class="form-group mb-3">
                            <label><strong>Chọn hình thức thanh toán:</strong></label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_type" id="full_payment" value="full" checked>
                                <label class="form-check-label" for="full_payment">Thanh toán toàn bộ</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_type" id="deposit_payment" value="deposit">
                                <label class="form-check-label" for="deposit_payment">Đặt cọc 20%</label>
                            </div>
                        </div>

                        <div class="text-center d-flex gap-2 justify-content-center">
                            <button type="submit" name="confirm_payment" class="btn btn-primary px-5 py-2">Xác nhận</button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2" style="border-radius:10px;">Về trang chủ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row justify-content-center">
            <a href="index.php" class="btn btn-primary px-5 py-2" style="border-radius:10px;">Về trang chủ</a>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- CSS -->
<style>
    .payment-form {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        border-radius: 12px;
        background: #fff;
    }
    .btn-primary {
        background-color: #d19c65;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        transition: 0.3s ease;
    }
    .btn-primary:hover { background-color: #b87e4b; }
    .text-success { color: #28a745 !important; }
    ul li { margin-bottom: 8px; }
</style>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>
