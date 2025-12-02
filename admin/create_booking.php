<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/cart.php';
require_once '../classes/khuyenmai.php';
require_once '../lib/database.php';
require_once '../helpers/format.php';

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$fm = new Format();
$ct = new Cart();
$km = new khuyenmai();
$id_admin = $_SESSION['idadmin'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time = $fm->validation($_POST['timebook']);
    $date = $fm->validation($_POST['datebook']);
    $khach = $fm->validation($_POST['khach']);
    $noidung = $fm->validation($_POST['noidung']);
    $payment_method = $fm->validation($_POST['payment_method']);
    $id_user = $fm->validation($_POST['id_user']);
    $id_km = !empty($_POST['id_km']) ? intval($_POST['id_km']) : null;

    $error = null;
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $date) || !strtotime($date)) {
        $error = "Ngày không hợp lệ.";
    } elseif (!preg_match("/^[0-9]{2}:[0-9]{2}$/", $time)) {
        $error = "Thời gian không hợp lệ.";
    } elseif (!in_array($khach, ['2', '4', '10-15', '15-20', '20-50'])) {
        $error = "Số lượng khách không hợp lệ.";
    } elseif (!in_array($noidung, ['Birthday', 'Meeting', 'Wedding', 'Other'])) {
        $error = "Nội dung không hợp lệ.";
    } elseif (!in_array($payment_method, ['cash', 'qr'])) {
        $error = "Phương thức thanh toán không hợp lệ.";
    } elseif (!is_numeric($id_user)) {
        $error = "ID khách hàng không hợp lệ.";
    }

    // BẮT ĐẦU SỬA
    $phantram = 0;
    if ($id_km) {
        $query = "SELECT discout FROM khuyenmai WHERE id_km = ? AND time_star <= NOW() AND time_end >= NOW()";
        $stmt = $db->link->prepare($query);
        $stmt->bind_param("i", $id_km);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $phantram = floatval($result->fetch_assoc()['discout']) / 100; // vd: 0.1 cho 10%
        }
        $stmt->close();
    }

    $selected_mons = [];
    $total_amount = 0;
    $total_with_discount = 0;

    if (isset($_POST['mons']) && is_array($_POST['mons'])) {
        foreach ($_POST['mons'] as $mon_id => $soluong) {
            if ($soluong > 0) {
                $query = "SELECT * FROM monan WHERE id_mon = ?";
                $stmt = $db->link->prepare($query);
                $stmt->bind_param("s", $mon_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $mon = $result->fetch_assoc();
                    $gia = $mon['gia_mon'];
                    $gia_da_giam = ($phantram > 0) ? round($gia * (1 - $phantram), 0) : $gia;
                    $thanhtien = $gia_da_giam * $soluong;
                    $total_amount += $gia * $soluong;           // tổng tiền gốc
                    $total_with_discount += $thanhtien;         // tổng tiền sau giảm
                    $selected_mons[] = [
                        'id_mon' => $mon['id_mon'],
                        'name_mon' => $mon['name_mon'],
                        'soluong' => $soluong,
                        'gia_mon' => $gia,           // vẫn truyền giá gốc, hàm insert sẽ xử lý giảm giá khi lưu
                        'images' => $mon['images']
                    ];
                }
                $stmt->close();
            }
        }
    }
    $discount = $total_amount - $total_with_discount;

    if (!$error) {
        if (!empty($selected_mons)) {
            $db->link->begin_transaction();
            try {
                $result = $ct->insert_order_admin(
                    $id_user, $time, $date, $khach, $noidung,
                    $selected_mons, $payment_method, $id_km, $phantram // truyền phần trăm giảm (0.1 cho 10%)
                );
                if ($result) {
                    $_SESSION['booking_info'] = [
                        'id_user' => $id_user,
                        'time' => $time,
                        'date' => $date,
                        'khach' => $khach,
                        'noidung' => $noidung,
                        'payment_method' => $payment_method,
                        'selected_mons' => $selected_mons,
                        'total_amount' => $total_amount,
                        'discount' => $discount,
                        'total_with_discount' => $total_with_discount,
                        'id_km' => $id_km
                    ];

                    if ($payment_method === 'cash') {
                        $sid = session_id();    
                        $update = "UPDATE hopdong SET payment_status = 'completed', payment_method = 'cash' WHERE sesis = ? AND payment_status = 'pending'";
                        $stmt = $db->link->prepare($update);
                        $stmt->bind_param("s", $sid);
                        $stmt->execute();
                        $stmt->close();
                        $db->link->commit();
                        unset($_SESSION['booking_info']);
                        header('Location: success_admin.php');
                        exit();
                    } else {
                        $db->link->commit();
                        header('Location: vietqr_cre_admin.php');
                        exit();
                    }
                } else {
                    throw new Exception("Lỗi khi tạo đặt bàn.");
                }
            } catch (Exception $e) {
                $db->link->rollback();
                $error = "Lỗi: " . $e->getMessage();
            }
        } else {
            $error = "Vui lòng chọn ít nhất một món ăn.";
        }
    }
}

$mon_list = $db->select("SELECT * FROM monan");
$users = $db->select("SELECT * FROM khach_hang WHERE xoa = 0");
$km_list = $db->select("SELECT * FROM khuyenmai WHERE time_star <= NOW() AND time_end >= NOW() AND xoa = 0");

?>

<!-- ✅ CSS -->
<style>
    .form-wrapper {
        max-width: 1000px;
        margin: 30px auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        font-size: 16px;
    }
    .form-wrapper h2 { text-align: center; margin-bottom: 30px; color: #333; }
    .form-group label { font-weight: 600; margin-bottom: 6px; display: block; color: #444; }
    .form-control {
        width: 100%; padding: 10px 14px; border: 1px solid #ccc;
        border-radius: 8px; font-size: 15px; transition: border-color 0.3s;
    }
    .form-control:focus {
        border-color: #007bff; outline: none;
        box-shadow: 0 0 5px rgba(0,123,255,0.25);
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    .table-chon-mon {
        width: 100%; margin-top: 20px; border-collapse: collapse;
    }
    .table-chon-mon th, .table-chon-mon td {
        border: 1px solid #dee2e6; padding: 12px; text-align: center;
    }
    .table-chon-mon th { background-color: #f8f9fa; }
    .btn-submit {
        background-color: #007bff; color: white;
        padding: 12px 30px; border-radius: 8px; font-weight: bold;
        font-size: 16px; border: none; margin-top: 20px;
        cursor: pointer; display: block; width: 100%;
        transition: background-color 0.3s ease;
    }
    .btn-submit:hover { background-color: #0056b3; }
    .text-danger {
        color: red; font-weight: 600;
        text-align: center; margin-bottom: 15px;
    }
</style>

<!-- ✅ HTML -->
<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Tạo đặt bàn</h2>
            <?php if (isset($error)) echo "<p class='text-danger'>" . htmlspecialchars($error) . "</p>"; ?>
            <form action="" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Khách hàng</label>
                        <select name="id_user" class="form-control" required>
                            <option value="">Chọn khách hàng</option>
                            <?php while ($user = $users->fetch_assoc()) { ?>
                                <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['ten']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Ngày</label><input type="date" name="datebook" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group"><label>Giờ</label><input type="time" name="timebook" class="form-control" required></div>
                    <div class="form-group">
                        <label>Số lượng khách</label>
                        <select name="khach" class="form-control" required>
                            <option value="">Chọn</option>
                            <option value="2">2</option><option value="4">4</option>
                            <option value="10-15">10-15</option>
                            <option value="15-20">15-20</option>
                            <option value="20-50">20-50</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <select name="noidung" class="form-control" required>
                            <option value="">Chọn nội dung</option>
                            <option value="Birthday">Sinh nhật</option>
                            <option value="Meeting">Gặp mặt</option>
                            <option value="Wedding">Đám cưới</option>
                            <option value="Other">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mã khuyến mãi</label>
                        <select name="id_km" class="form-control">
                            <option value="">Không áp dụng</option>
                            <?php if ($km_list) {
                                while ($km = $km_list->fetch_assoc()) {
                                    echo '<option value="' . $km['id_km'] . '">' . htmlspecialchars($km['name_km']) . '</option>';
                                }

                            } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phương thức thanh toán</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Tiền mặt</option>
                            <option value="qr">QR</option>
                        </select>
                    </div>
                </div>

                <h4 style="margin-top:30px; font-weight:bold;">Chọn món ăn</h4>
                <table class="table-chon-mon">
                    <thead>
                        <tr><th>Tên món</th><th>Giá</th><th>Số lượng</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($mon = $mon_list->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($mon['name_mon']); ?></td>
                                <td><?= $fm->formatMoney($mon['gia_mon']); ?> đ</td>
                                <td><input type="number" name="mons[<?= $mon['id_mon']; ?>]" class="form-control" value="0" min="0"></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <button type="submit" class="btn-submit">Xác nhận đặt món</button>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>
