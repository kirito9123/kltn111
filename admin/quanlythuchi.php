<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/thongke.php';

$tk = new ThongKe();
$msg = "";

// Xử lý thêm chi phí nhập hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_cost'])) {
    $msg = $tk->themChiPhi($_POST);
}
// Xử lý xóa chi phí
if (isset($_GET['del_cost'])) {
    $tk->xoaChiPhi($_GET['del_cost']);
    echo "<script>window.location = 'quanlythuchi.php';</script>";
}

// Lấy thời gian
$thang = isset($_GET['thang']) ? $_GET['thang'] : date('m');
$nam = isset($_GET['nam']) ? $_GET['nam'] : date('Y');

// Tính toán số liệu
$tongThuSystem = $tk->getTongDoanhThu($thang, $nam); // Doanh thu trên hệ thống
$chenhLech = $tk->getChenhLech($thang, $nam); // Tổng chênh lệch tiền mặt
$tongThuThuc = $tongThuSystem + $chenhLech; // Doanh thu thực tế

$chiLuong = $tk->getTongLuong($thang, $nam); // Lương thực tế (đã chốt)
$chiNhapHang = $tk->getTongChiPhiKhac($thang, $nam); // Chi phí nhập hàng
$tongChi = $chiLuong + $chiNhapHang;

$loiNhuan = $tongThuThuc - $tongChi;

$listThu = $tk->getListDoanhThu($thang, $nam);
$listChi = $tk->getListChiPhi($thang, $nam);
?>

<style>
    .stat-box {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        flex: 1;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center;
        border-left: 5px solid #ccc;
    }

    .card h3 {
        font-size: 16px;
        color: #777;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .card .num {
        font-size: 24px;
        font-weight: bold;
    }

    .card.green {
        border-color: #27ae60;
    }

    .card.green .num {
        color: #27ae60;
    }

    .card.red {
        border-color: #c0392b;
    }

    .card.red .num {
        color: #c0392b;
    }

    .card.blue {
        border-color: #2980b9;
    }

    .card.blue .num {
        color: #2980b9;
    }

    .panels {
        display: flex;
        gap: 20px;
    }

    .panel {
        flex: 1;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .panel h3 {
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
        color: #333;
    }

    table.mini-table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }

    table.mini-table th,
    table.mini-table td {
        border-bottom: 1px solid #f1f1f1;
        padding: 8px;
        text-align: left;
    }

    table.mini-table th {
        font-weight: 600;
        color: #555;
    }

    .input-group input,
    .input-group textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .btn-submit {
        width: 100%;
        padding: 10px;
        background: #e67e22;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-submit:hover {
        background: #d35400;
    }

    .btn-print {
        float: right;
        background: #34495e;
        color: #fff;
        padding: 8px 15px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-print:hover {
        background: #2c3e50;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>📊 Báo Cáo Doanh Thu & Thu Chi</h2>
            <a href="in_baocaothuchi.php?thang=<?php echo $thang; ?>&nam=<?php echo $nam; ?>" target="_blank" class="btn-print">🖨️ Xuất PDF Báo Cáo</a>
        </div>

        <form method="GET" style="background:#eee; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; gap:15px; align-items:center;">
            <strong>Xem tháng:</strong>
            <select name="thang" style="padding:5px;"><?php for ($i = 1; $i <= 12; $i++) echo "<option value='$i' " . ($i == $thang ? 'selected' : '') . ">Tháng $i</option>"; ?></select>
            <select name="nam" style="padding:5px;">
                <option value="2025">2025</option>
                <option value="2026">2026</option>
            </select>
            <button type="submit" style="padding:5px 15px; cursor:pointer;">Xem</button>
        </form>

        <?php if ($msg) echo $msg; ?>

        <div class="stat-box">
            <div class="card green">
                <h3>Tổng Doanh Thu Thực</h3>
                <div class="num"><?php echo number_format($tongThuThuc); ?> ₫</div>
                <small>(Hệ thống: <?php echo number_format($tongThuSystem); ?> + Lệch: <?php echo number_format($chenhLech); ?>)</small>
            </div>
            <div class="card red">
                <h3>Tổng Chi Phí</h3>
                <div class="num"><?php echo number_format($tongChi); ?> ₫</div>
                <small>(Lương: <?php echo number_format($chiLuong); ?> + Nhập: <?php echo number_format($chiNhapHang); ?>)</small>
            </div>
            <div class="card blue">
                <h3>Lợi Nhuận Thực</h3>
                <div class="num"><?php echo number_format($loiNhuan); ?> ₫</div>
                <small><?php echo ($loiNhuan >= 0) ? "Có lãi" : "Đang lỗ"; ?></small>
            </div>
        </div>

        <div class="panels">
            <div class="panel" style="flex: 2;">
                <h3>Chi tiết nguồn thu (Hợp đồng đã TT)</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Khách hàng</th>
                                <th>Dịch vụ/Bàn</th>
                                <th>Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listThu): while ($row = $listThu->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m', strtotime($row['dates'])); ?></td>
                                        <td><?php echo $row['tenKH']; ?></td>
                                        <td><?php echo $row['loaiphong']; ?></td>
                                        <td style="font-weight:bold; color:#27ae60;"><?php echo number_format($row['thanhtien']); ?></td>
                                    </tr>
                            <?php endwhile;
                            else: echo "<tr><td colspan='4'>Không có doanh thu.</td></tr>";
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="flex: 1;">
                <h3>➖ Thêm Chi Phí Phát Sinh</h3>
                <form method="POST">
                    <div class="input-group">
                        <label>Tên khoản chi (Nhập hàng, Điện...)</label>
                        <input type="text" name="ten_chiphi" required placeholder="VD: Nhập 50kg Gạo">
                    </div>
                    <div class="input-group">
                        <label>Số tiền (VNĐ)</label>
                        <input type="text" name="so_tien" class="money" required placeholder="0">
                    </div>
                    <div class="input-group">
                        <label>Ngày chi</label>
                        <input type="date" name="ngay_chi" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="input-group">
                        <textarea name="ghi_chu" placeholder="Ghi chú thêm..." rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_cost" class="btn-submit">Lưu Khoản Chi</button>
                </form>

                <h3 style="margin-top:20px; font-size:14px;">Lịch sử chi nhập hàng tháng này</h3>
                <ul style="list-style:none; padding:0; max-height:200px; overflow-y:auto;">
                    <?php if ($listChi): while ($row = $listChi->fetch_assoc()): ?>
                            <li style="border-bottom:1px dashed #ddd; padding:5px 0; font-size:12px; display:flex; justify-content:space-between;">
                                <span>
                                    <b><?php echo date('d/m', strtotime($row['ngay_chi'])); ?>:</b>
                                    <?php echo $row['ten_chiphi']; ?>
                                </span>
                                <span>
                                    <b style="color:#c0392b;">-<?php echo number_format($row['so_tien']); ?></b>
                                    <a href="?del_cost=<?php echo $row['id_chiphi']; ?>" onclick="return confirm('Xóa?')" style="color:red; text-decoration:none;">x</a>
                                </span>
                            </li>
                    <?php endwhile;
                    endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
    // Format input tiền
    document.querySelector('.money').addEventListener('keyup', function(e) {
        let val = this.value.replace(/,/g, '');
        if (!isNaN(val) && val !== '') this.value = parseInt(val).toLocaleString('en-US');
    });
</script>
<?php include 'inc/footer.php'; ?>