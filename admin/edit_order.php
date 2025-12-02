<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/mon.php';
require_once '../classes/khuyenmai.php';
require_once '../classes/hopdong.php';

$monan = new mon();
$khuyenmai = new KhuyenMai();
$hopdong = new HopDong();

$fm = new Format();
$id = $fm->validation($_GET['id']);
$order = $hopdong->getOrderById($id)->fetch_assoc();

$mon_list = $monan->getmonbyall();
$khuyenmai_list = $khuyenmai->getActiveKhuyenMai();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $id_mon = $fm->validation($_POST['id_mon']);
    $soluong = $fm->validation($_POST['soluong']);
    $id_km = $fm->validation($_POST['id_km'] ?: null);
    $payment_method = $fm->validation($_POST['payment_method']);
    $hopdong->updateOrder($id, $id_mon, $soluong, $id_km, $payment_method);
    header("Location: admin_orders.php");
}
?>

<style>
    .form-container {
        max-width: 700px;
        margin: 30px auto;
        padding: 30px 40px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .form-container h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 25px;
    }

    .form-table {
        width: 100%;
    }

    .form-table tr {
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
    }

    .form-table td {
        padding: 10px 0;
    }

    .form-table label {
        font-weight: 600;
        margin-bottom: 5px;
        display: inline-block;
        color: #333;
    }

    .form-table input[type="number"],
    .form-table select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 15px;
        transition: border 0.3s ease;
    }

    .form-table input:focus,
    .form-table select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
    }

    .form-actions {
        text-align: center;
        margin-top: 20px;
    }

    .form-actions input[type="submit"] {
        background-color: #007bff;
        color: #fff;
        padding: 12px 26px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>Sửa đơn hàng</h2>
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <td><label>Chọn món</label></td>
                        <td>
                            <select name="id_mon" required>
                                <option value="">-- Chọn món --</option>
                                <?php while ($mon = $mon_list->fetch_assoc()) { ?>
                                    <option value="<?= $mon['id_mon']; ?>" <?= $mon['id_mon'] == $order['id_mon'] ? 'selected' : '' ?>>
                                        <?= $mon['name_mon']; ?> - <?= number_format($mon['gia_mon']); ?> VNĐ
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td><label>Số lượng</label></td>
                        <td><input type="number" name="soluong" min="1" value="<?= $order['soluong']; ?>" required /></td>
                    </tr>

                    <tr>
                        <td><label>Khuyến mãi</label></td>
                        <td>
                            <select name="id_km">
                                <option value="">Không áp dụng</option>
                                <?php while ($km = $khuyenmai_list->fetch_assoc()) { ?>
                                    <option value="<?= $km['id_km']; ?>" <?= $km['id_km'] == $order['id_km'] ? 'selected' : '' ?>>
                                        <?= $km['name_km']; ?> (-<?= number_format($km['discout']); ?> VNĐ)
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td><label>Phương thức thanh toán</label></td>
                        <td>
                            <select name="payment_method" required>
                                <option value="cash" <?= $order['payment_method'] == 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                                <option value="vietqr" <?= $order['payment_method'] == 'vietqr' ? 'selected' : '' ?>>vietQR</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Cập nhật">
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
