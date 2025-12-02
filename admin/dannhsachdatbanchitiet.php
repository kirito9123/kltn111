<?php
include 'inc/header.php';
include 'inc/sidebar.php';

// Dùng cả 2 class: cart (để JOIN chi tiết & update tinhtrang theo sesis)
// và HopDong (để lấy header theo id, có trường so_tien, v.v.)
require_once '../classes/cart.php';
require_once '../classes/hopdong.php';
require_once '../classes/user.php';

$cart    = new cart();
$hopdong = new HopDong();
$user    = new user();

// ✅ Nhận id, không còn 'id_mon'
if (empty($_GET['id'])) {
    echo "<script>window.location='danhsachdatban.php'</script>";
    exit;
}
$id = (int)$_GET['id'];

// Lấy 1 bản ghi hopdong theo id để có sesis & so_tien
$order_row = null;
if ($rs = $hopdong->getOrderById($id)) {
    $order_row = $rs->fetch_assoc();
}
if (!$order_row) {
    echo "<div style='padding:16px;color:#b00020;'>Không tìm thấy hợp đồng!</div>";
    include 'inc/footer.php'; exit;
}
$sesis   = $order_row['sesis'];
$so_tien = (float)($order_row['so_tien'] ?? 0);

// Cập nhật tình trạng (theo sesis như code cũ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $tinhtrang = $_POST['tinhtrang'] ?? '';
    $cart->update_tinhtrang($sesis, $tinhtrang);
    // reload để thấy trạng thái mới
    echo "<script>window.location='dannhsachdatbanchitiet.php?id={$id}'</script>";
    exit;
}

// Lấy header + chi tiết theo sesis (dùng JOIN đã viết trong cart->show_thongtinid)
$hopdong_info = null;     // header (lấy từ dòng đầu)
$list_monan   = [];       // chi tiết món
$tongtien     = 0;

if ($rs = $cart->show_thongtinid($sesis)) {
    // Lấy dòng đầu làm header
    $hopdong_info = $rs->fetch_assoc();
    // Push dòng đầu vào list
    if ($hopdong_info) {
        $gia       = (float)($hopdong_info['gia'] ?? 0);
        $soluong   = (int)($hopdong_info['soluong'] ?? 0);
        $thanhtien = (float)($hopdong_info['thanhtien'] ?? ($gia * $soluong));
        $tongtien += $thanhtien;
        $list_monan[] = $hopdong_info;
    }
    // Duyệt tiếp các dòng còn lại
    while ($row = $rs->fetch_assoc()) {
        $gia       = (float)($row['gia'] ?? 0);
        $soluong   = (int)($row['soluong'] ?? 0);
        $thanhtien = (float)($row['thanhtien'] ?? ($gia * $soluong));
        $tongtien += $thanhtien;
        $list_monan[] = $row;
    }
}

// Lấy tên khách (an toàn)
$ten_khach = 'Không xác định';
if (!empty($order_row['id_user'])) {
    $u = $user->get_user_by_id($order_row['id_user']);
    if ($u && isset($u['ten'])) $ten_khach = $u['ten'];
}
?>

<style>
    .contract-form { max-width: 800px; margin: 0 auto; }
    .contract-form label { font-weight: bold; color: #333; }
    .contract-form input[type="text"], .contract-form select {
        width: 100%; padding: 8px; margin: 5px 0 15px;
        border: 1px solid #ccc; border-radius: 8px; box-shadow: inset 0 1px 3px rgba(0,0,0,.1);
    }
    .contract-form input[readonly] { background-color: #f9f9f9; }
    .contract-form table tr td { padding: 8px 10px; }
    .contract-form .btn-submit {
        padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.3s;
    }
    .contract-form .btn-submit:hover { background-color: #0056b3; }
    .scroll-mon-list {
        max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;
        padding: 10px; margin-bottom: 20px; background-color: #fafafa;
    }
    .monan-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
    .monan-item input { width: auto !important; flex: 1; }
</style>

<div style="margin-bottom: 15px; text-align: left;">
    <a href="danhsachdatban.php" class="btn-action btn-back" style="background-color: #6c757d; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none;">
        ← Quay lại trang quản trị
    </a>
</div>

<div class="grid_10">
    <div class="box round first grid">
        <h2 style="text-align:center">Chi tiết hợp đồng</h2>
        <div class="block contract-form">
            <?php if ($hopdong_info): ?>
                <form action="" method="post">
                    <table class="form">
                        <tr>
                            <td><label>Mã hợp đồng</label></td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($sesis) ?>" /></td>
                        </tr>
                        <tr>
                            <td><label>Tên khách hàng</label></td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($ten_khach) ?>" /></td>
                        </tr>
                        <tr>
                            <td><label>Giờ</label></td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($hopdong_info['tg'] ?? '') ?>" /></td>
                        </tr>
                        <tr>
                            <td><label>Ngày</label></td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($hopdong_info['dates'] ?? '') ?>" /></td>
                        </tr>
                        <tr>
                            <td><label>Nội dung</label></td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($hopdong_info['noidung'] ?? '') ?>" /></td>
                        </tr>
                        <tr>
                            <td><label>Số lượng khách</label></td>
                            <td><input type="text" readonly value="<?= (int)($hopdong_info['so_user'] ?? 0) ?>" /></td>
                        </tr>

                        <!-- Danh sách món -->
                        <tr>
                            <td colspan="2">
                                <div class="scroll-mon-list">
                                    <?php if (!empty($list_monan)): ?>
                                        <?php foreach ($list_monan as $monan): ?>
                                            <div class="monan-item">
                                                <input type="text" readonly value="<?= htmlspecialchars($monan['name_mon']) ?>" />
                                                <input type="text" readonly value="Số lượng: <?= (int)$monan['soluong'] ?>" />
                                                <input type="text" readonly value="Đơn giá: <?= number_format((float)($monan['gia'] ?? 0), 0, ',', '.') ?> đ" />
                                                <input type="text" readonly value="Thành tiền: <?= number_format((float)($monan['thanhtien'] ?? 0), 0, ',', '.') ?> đ" />
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <em>Không có món nào trong hợp đồng.</em>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Tiền cọc hoặc Tổng tiền -->
                        <tr>
                            <td><label>Tiền cọc</label></td>
                            <td>
                                <input type="text" readonly value="<?= number_format($so_tien > 0 ? $so_tien : $tongtien, 0, ',', '.') . ' đ' ?>" />
                                <?php if ($so_tien <= 0): ?>
                                    <div style="color:#888;font-size:12px;margin-top:4px;">(* Không có so_tien, đang hiển thị Tổng tiền đơn)</div>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td><label>Trạng thái duyệt</label></td>
                            <td>
                                <?php $tinhtrang = isset($hopdong_info['tinhtrang']) ? (int)$hopdong_info['tinhtrang'] : 0; ?>
                                <select name="tinhtrang">
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="1" <?= $tinhtrang === 1 ? 'selected' : '' ?>>Đã duyệt</option>
                                    <option value="0" <?= $tinhtrang === 0 ? 'selected' : '' ?>>Chưa duyệt</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td></td>
                            <td>
                                <button type="submit" name="submit" class="btn-submit">Cập nhật</button>
                            </td>
                        </tr>
                    </table>
                </form>
            <?php else: ?>
                <div style="color: red;">Không tìm thấy thông tin hợp đồng!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
