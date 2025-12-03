<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/luong.php';

$luong = new Luong();
$message = "";

// --- XỬ LÝ POST ---

// 1. Cập nhật cấu hình lương (Tab 1)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_rate'])) {
    $mans = $_POST['mans'];
    $rate = str_replace(',', '', $_POST['luong_ca']);
    $allowance = str_replace(',', '', $_POST['phu_cap']);
    if ($luong->capNhatMucLuong($mans, $rate, $allowance)) {
        $message = "✅ Cập nhật mức lương & trợ cấp thành công!";
    }
}

// 2. Chốt lương (Tab 2)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['chot_luong_btn'])) {
    $thang = $_POST['thang'];
    $nam = $_POST['nam'];
    $message = $luong->chotBangLuong($thang, $nam, $_POST);
}

// 3. Xác nhận thanh toán (Tab 3)
if (isset($_GET['pay_id'])) {
    $payID = $_GET['pay_id'];
    if ($luong->xacNhanThanhToan($payID)) {
        $message = "✅ Đã xác nhận thanh toán lương thành công!";
    }
}

// --- LẤY DỮ LIỆU ---
$dsNhanVien = $luong->layDanhSachMucLuong();
$selected_month = isset($_GET['thang']) ? (int)$_GET['thang'] : (int)date('m');
$selected_year = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');

$historyData = $luong->layLichSuLuong($selected_month, $selected_year);
$hasHistory = ($historyData && $historyData->num_rows > 0);
$isLocked = $hasHistory;

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'setting';
if (isset($_POST['chot_luong_btn'])) $currentTab = 'payment';
if (isset($_GET['pay_id'])) $currentTab = 'payment';

$roles = [0 => 'Admin', 1 => 'Kế toán', 2 => 'NV Quầy', 3 => 'NV Bếp', 4 => 'NV Chạy bàn'];
?>

<style>
    * {
        box-sizing: border-box;
    }

    .main-container {
        padding: 25px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        font-family: 'Segoe UI', sans-serif;
    }

    h2.page-title {
        font-size: 24px;
        margin-bottom: 25px;
        color: #2c3e50;
        border-bottom: 2px solid #f0f2f5;
        padding-bottom: 15px;
        font-weight: 700;
    }

    /* --- THÔNG BÁO NỔI BẬT --- */
    .alert-success {
        padding: 15px;
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 16px;
        font-weight: 600;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        animation: slideDown 0.5s ease-out;
    }

    /* --- TABS --- */
    .tabs {
        display: flex;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 25px;
    }

    .tab {
        padding: 12px 25px;
        cursor: pointer;
        background: transparent;
        border-bottom: 2px solid transparent;
        margin-right: 5px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.2s;
    }

    .tab:hover {
        color: #0d6efd;
        background-color: #f8f9fa;
        border-radius: 5px 5px 0 0;
    }

    .tab.active {
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background-color: #f8fbff;
        border-radius: 5px 5px 0 0;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }

    .tab-content.active {
        display: block;
    }

    /* --- INPUTS --- */
    .money-input {
        width: 100%;
        height: 38px;
        padding: 6px 12px;
        text-align: right;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-family: 'Consolas', monospace;
        font-weight: 600;
    }

    .money-input:focus {
        border-color: #86b7fe;
        outline: none;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
    }

    .input-bonus {
        border-color: #a3e4d7;
        color: #198754;
        background-color: #f0fff4;
    }

    .input-fine {
        border-color: #f5c2c7;
        color: #dc3545;
        background-color: #fff5f5;
    }

    .input-reason {
        width: 100%;
        height: 38px;
        padding: 6px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 13px;
    }

    /* --- BUTTONS --- */
    .btn-action {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-blue {
        background-color: #0d6efd;
    }

    .btn-blue:hover {
        background-color: #0b5ed7;
    }

    .btn-green {
        background-color: #198754;
    }

    .btn-green:hover {
        background-color: #157347;
    }

    .btn-red {
        background-color: #dc3545;
    }

    .btn-red:hover {
        background-color: #bb2d3b;
    }

    .btn-gray {
        background-color: #6c757d;
    }

    .btn-gray:hover {
        background-color: #5c636a;
    }

    .btn-dark {
        background-color: #343a40;
    }

    .btn-dark:hover {
        background-color: #23272b;
    }

    /* --- TABLE --- */
    table.custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }

    table.custom-table th {
        background-color: #f8f9fa;
        color: #495057;
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
        text-align: center;
        font-weight: 700;
        white-space: nowrap;
    }

    table.custom-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
        color: #212529;
    }

    table.custom-table tr:last-child td {
        border-bottom: none;
    }

    table.custom-table tr:hover {
        background-color: #f8fbff;
    }

    /* --- FOOTER ACTION BAR --- */
    .action-bar {
        background: #fff;
        padding: 15px 25px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
        margin-top: 20px;
        position: sticky;
        bottom: 20px;
        z-index: 999;
        box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.05);
    }

    .total-preview {
        font-size: 16px;
        color: #495057;
    }

    .total-preview span {
        font-size: 24px;
        color: #dc3545;
        font-weight: 800;
        margin-left: 5px;
        font-family: 'Consolas', monospace;
    }

    /* --- BADGES --- */
    .badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-paid {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .badge-unpaid {
        background-color: #f8d7da;
        color: #842029;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="main-container">
            <h2 class="page-title">💰 QUẢN LÝ LƯƠNG & THU NHẬP</h2>

            <?php if ($message): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <div class="tab <?php echo $currentTab == 'setting' ? 'active' : ''; ?>" onclick="switchTab('setting')">1. Cài Đặt Mức Lương</div>
                <div class="tab <?php echo $currentTab == 'calculate' ? 'active' : ''; ?>" onclick="switchTab('calculate')">2. Tính & Chốt Lương</div>
                <div class="tab <?php echo $currentTab == 'payment' ? 'active' : ''; ?>" onclick="switchTab('payment')">3. Thanh Toán & Lịch Sử</div>
            </div>

            <div id="setting" class="tab-content <?php echo $currentTab == 'setting' ? 'active' : ''; ?>">
                <div style="margin-bottom:20px; color:#495057; background:#e9ecef; padding:12px 20px; border-radius:6px; border-left: 4px solid #0d6efd;">
                    ℹ️ <i>Cài đặt lương cơ bản cho 1 ca làm việc (5 tiếng) và các khoản phụ cấp cố định.</i>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="25%" style="text-align:left;">Họ Tên Nhân Viên</th>
                            <th width="15%">Chức Vụ</th>
                            <th width="20%">Lương/Ca (VNĐ)</th>
                            <th width="20%">Trợ Cấp (VNĐ)</th>
                            <th width="15%">Lưu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dsNhanVien && $dsNhanVien->num_rows > 0): mysqli_data_seek($dsNhanVien, 0);
                            while ($row = $dsNhanVien->fetch_assoc()):
                                $level = $row['level'];
                                $ten_chuc_vu = isset($roles[$level]) ? $roles[$level] : "Level $level";
                                $luong_ca = isset($row['luong_ca']) ? $row['luong_ca'] : 0;
                                $phu_cap = isset($row['phu_cap']) ? $row['phu_cap'] : 0;
                        ?>
                                <tr>
                                    <form method="POST" action="quanlyluong.php?tab=setting">
                                        <td style="text-align:center; color:#888; font-weight:bold;"><?php echo $row['mans']; ?><input type="hidden" name="mans" value="<?php echo $row['mans']; ?>"></td>
                                        <td style="font-weight:600; color:#2c3e50; font-size:15px;"><?php echo htmlspecialchars($row['hoten']); ?></td>
                                        <td style="text-align:center;"><span style="background:#e9ecef; padding:5px 10px; border-radius:15px; font-size:12px; color:#495057; font-weight:600;"><?php echo $ten_chuc_vu; ?></span></td>
                                        <td><input type="text" name="luong_ca" class="money-input" value="<?php echo number_format((float)$luong_ca); ?>" placeholder="0"></td>
                                        <td><input type="text" name="phu_cap" class="money-input" value="<?php echo number_format((float)$phu_cap); ?>" placeholder="0"></td>
                                        <td style="text-align:center;"><button type="submit" name="update_rate" class="btn-action btn-blue">💾 Lưu</button></td>
                                    </form>
                                </tr>
                        <?php endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="calculate" class="tab-content <?php echo $currentTab == 'calculate' ? 'active' : ''; ?>">
                <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:25px; border: 1px solid #dee2e6;">
                    <form method="GET" action="quanlyluong.php" style="display:flex; gap:15px; align-items:center; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="calculate">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <strong style="color:#495057;">📅 Chọn kỳ lương:</strong>
                            <select name="thang" style="padding:8px 12px; border-radius:4px; border:1px solid #ced4da; background:white;">
                                <?php for ($i = 1; $i <= 12; $i++) echo "<option value='$i' " . ($i == $selected_month ? 'selected' : '') . ">Tháng $i</option>"; ?>
                            </select>
                            <select name="nam" style="padding:8px 12px; border-radius:4px; border:1px solid #ced4da; background:white;">
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-action btn-blue" style="padding: 8px 20px;">🔍 Xem Bảng Tính</button>
                    </form>
                </div>

                <?php if ($isLocked): ?>
                    <div style="text-align:center; padding:50px; background:#f0fff4; border:2px dashed #198754; border-radius:10px; margin-bottom: 30px;">
                        <h3 style="color:#198754; margin-top:0; font-size: 22px;">✅ ĐÃ CHỐT SỔ THÁNG <?php echo "$selected_month/$selected_year"; ?></h3>
                        <p style="color:#6c757d; font-size: 15px;">Dữ liệu đã được lưu trữ. Chuyển sang tab Thanh Toán để in phiếu và chi tiền.</p>
                        <br>
                        <button onclick="switchTab('payment')" class="btn-action btn-green" style="font-size:16px; padding:12px 30px;">👉 Chuyển sang Tab Thanh Toán</button>
                    </div>
                <?php else:
                    $previewData = $luong->tinhLuongThang($selected_month, $selected_year);
                ?>
                    <?php if (!empty($previewData)): ?>
                        <form method="POST" action="quanlyluong.php">
                            <input type="hidden" name="thang" value="<?php echo $selected_month; ?>">
                            <input type="hidden" name="nam" value="<?php echo $selected_year; ?>">

                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="18%" style="text-align:left;">Nhân Viên</th>
                                        <th width="7%">Số Ca</th>
                                        <th width="15%" style="text-align:right;">Lương Cứng</th>
                                        <th width="10%">Thưởng</th>
                                        <th width="10%">Phạt</th>
                                        <th width="15%">Lý Do</th>
                                        <th width="15%" style="text-align:right;">Thực Lãnh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tongDuKien = 0;
                                    foreach ($previewData as $row):
                                        $tongDuKien += $row['luong_cung'];
                                        $rowID = $row['mans'];
                                    ?>
                                        <tr>
                                            <td style="text-align:center; color:#adb5bd; font-weight:600;"><?php echo $rowID; ?></td>
                                            <td>
                                                <div style="font-weight:700; color:#2c3e50; font-size: 14px;"><?php echo $row['hoten']; ?></div>
                                                <div style="font-size:11px; color:#6c757d; margin-top: 3px;">Level <?php echo $row['level']; ?></div>
                                            </td>
                                            <td style="text-align:center;">
                                                <span style="background:#e9ecef; color:#495057; padding:5px 12px; border-radius:50px; font-weight:700; font-size: 13px; display:inline-block; min-width:35px;"><?php echo $row['so_ca']; ?></span>
                                            </td>
                                            <td style="text-align:right; font-family:'Consolas', monospace; font-size:14px; font-weight: 600; color: #495057;">
                                                <?php echo number_format($row['luong_cung']); ?> <span style="font-size:10px; color:#adb5bd;">VNĐ</span>
                                            </td>
                                            <td>
                                                <input type="text" name="thuong[<?php echo $rowID; ?>]" class="money-input input-bonus" placeholder="0" oninput="calcRow(<?php echo $rowID; ?>, <?php echo $row['luong_cung']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" name="phat[<?php echo $rowID; ?>]" class="money-input input-fine" placeholder="0" oninput="calcRow(<?php echo $rowID; ?>, <?php echo $row['luong_cung']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" name="ly_do[<?php echo $rowID; ?>]" class="input-reason" placeholder="Lý do...">
                                            </td>
                                            <td style="text-align:right; font-family:'Consolas', monospace; font-size:15px; font-weight: 800; color: #d63384;">
                                                <span id="final_<?php echo $rowID; ?>" class="total-display"><?php echo number_format($row['luong_cung']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="action-bar">
                                <div class="total-preview">
                                    Tổng dự chi tháng <?php echo $selected_month; ?>:
                                    <span id="grand-total"><?php echo number_format($tongDuKien); ?></span> <small>VNĐ</small>
                                </div>
                                <button type="submit" name="chot_luong_btn" class="btn-action btn-green" style="padding: 12px 30px; font-size: 15px; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);" onclick="return confirm('Bạn có chắc chắn muốn CHỐT sổ lương tháng này không?');">
                                    💾 CHỐT & LƯU BẢNG LƯƠNG
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="text-align:center; padding:60px; color:#adb5bd;">
                            <div style="font-size: 50px; margin-bottom: 15px;">📭</div>
                            <p>Chưa có dữ liệu ca làm việc <b>"Đã hoàn thành"</b> nào.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div id="payment" class="tab-content <?php echo $currentTab == 'payment' ? 'active' : ''; ?>">
                <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:25px; border: 1px solid #dee2e6;">
                    <form method="GET" action="quanlyluong.php" style="display:flex; gap:15px; align-items:center; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="payment">
                        <strong style="color:#495057;">📂 Xem lịch sử tháng:</strong>
                        <select name="thang" style="padding:8px 12px; border-radius:4px; border:1px solid #ced4da; background:white;"><?php for ($i = 1; $i <= 12; $i++) echo "<option value='$i' " . ($i == $selected_month ? 'selected' : '') . ">Tháng $i</option>"; ?></select>
                        <select name="nam" style="padding:8px 12px; border-radius:4px; border:1px solid #ced4da; background:white;">
                            <option value="2025">2025</option>
                        </select>
                        <button type="submit" class="btn-action btn-blue">Xem</button>
                    </form>

                    <?php if ($hasHistory): ?>
                        <a href="in_luong.php?thang=<?php echo $selected_month; ?>&nam=<?php echo $selected_year; ?>"
                            target="_blank"
                            class="btn-action btn-gray"
                            style="padding: 10px 20px;">
                            🖨️ In Bảng Tổng Hợp Tháng <?php echo "$selected_month/$selected_year"; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($hasHistory): ?>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%" style="text-align:left;">Họ Tên</th>
                                <th width="12%">Chi tiết cơ bản</th>
                                <th width="12%">Thưởng / Phạt</th>
                                <th width="15%">Lý Do</th>
                                <th width="15%" style="text-align:right;">Thực Lãnh</th>
                                <th width="10%">Trạng Thái</th>
                                <th width="15%" style="text-align:center;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tongDaChi = 0;
                            mysqli_data_seek($historyData, 0);
                            while ($row = $historyData->fetch_assoc()):
                                $luongCung = ($row['tong_ca'] * $row['muc_luong_ca']) + $row['phu_cap'];
                                if ($row['trang_thai'] == 1) $tongDaChi += $row['thuc_lanh'];
                            ?>
                                <tr>
                                    <td style="text-align:center; color:#888; font-weight:bold;"><?php echo $row['mans']; ?></td>
                                    <td style="font-weight:700; color:#2c3e50;"><?php echo $row['hoten']; ?></td>
                                    <td style="font-size:13px;">
                                        <div style="margin-bottom:2px;">Ca làm: <b><?php echo $row['tong_ca']; ?></b></div>
                                        <div style="color:#6c757d;">Lương cứng: <?php echo number_format($luongCung); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['tien_thuong'] > 0) echo "<div style='color:#198754; font-weight:600;'>+ " . number_format($row['tien_thuong']) . "</div>"; ?>
                                        <?php if ($row['tien_phat'] > 0) echo "<div style='color:#dc3545; font-weight:600;'>- " . number_format($row['tien_phat']) . "</div>"; ?>
                                        <?php if ($row['tien_thuong'] == 0 && $row['tien_phat'] == 0) echo "<span style='color:#adb5bd;'>-</span>"; ?>
                                    </td>
                                    <td style="font-style:italic; color:#6c757d; font-size:12px; max-width:150px; white-space:normal;"><?php echo $row['ly_do']; ?></td>
                                    <td style="text-align:right; font-size:15px; font-weight:800; color:#d63384; font-family:'Consolas', monospace;">
                                        <?php echo number_format($row['thuc_lanh']); ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($row['trang_thai'] == 1): ?>
                                            <span class="badge badge-paid">Đã chi</span>
                                            <div style="font-size:10px; color:#198754; margin-top:4px;"><?php echo date('d/m H:i', strtotime($row['ngay_thanh_toan'])); ?></div>
                                        <?php else: ?>
                                            <span class="badge badge-unpaid">Chưa chi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <?php if ($row['trang_thai'] == 0): ?>
                                                <a href="quanlyluong.php?tab=payment&pay_id=<?php echo $row['id_bangluong']; ?>&thang=<?php echo $selected_month; ?>&nam=<?php echo $selected_year; ?>"
                                                    class="btn-action btn-blue" onclick="return confirm('Xác nhận đã trả tiền mặt/chuyển khoản cho nhân viên này?');" title="Xác nhận đã trả lương">💰 Chi Tiền</a>
                                            <?php else: ?>
                                                <button class="btn-action btn-gray" style="cursor: default; opacity: 0.6;" disabled>✔ Xong</button>
                                            <?php endif; ?>

                                            <a href="in_luong.php?id=<?php echo $row['id_bangluong']; ?>" class="btn-action btn-gray" style="background:#6c757d;" target="_blank" title="In phiếu lương cá nhân">🖨️ Phiếu</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot style="background:#f8f9fa;">
                            <tr>
                                <td colspan="5" style="text-align:right; font-weight:bold; padding:15px; color: #495057;">TỔNG ĐÃ CHI TIỀN:</td>
                                <td colspan="3" style="font-weight:800; color:#198754; font-size:18px; text-align: left; padding-left: 15px;"><?php echo number_format($tongDaChi); ?> VNĐ</td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <div style="text-align:center; padding:60px; color:#adb5bd;">
                        <div style="font-size: 50px; margin-bottom: 15px;">📂</div>
                        <p style="font-size: 16px;">Chưa có lịch sử lương của tháng <?php echo "$selected_month/$selected_year"; ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(x => x.style.display = "none");
        document.querySelectorAll('.tab').forEach(x => x.classList.remove("active"));
        document.getElementById(tabName).style.display = "block";

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        url.searchParams.set('thang', '<?php echo $selected_month; ?>');
        url.searchParams.set('nam', '<?php echo $selected_year; ?>');
        window.history.pushState({}, '', url);

        const buttons = document.querySelectorAll('.tab');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes(tabName)) {
                btn.classList.add('active');
            }
        });
    }

    function calcRow(id, luongCung) {
        let thuongInput = document.querySelector(`input[name="thuong[${id}]"]`);
        let phatInput = document.querySelector(`input[name="phat[${id}]"]`);

        let thuong = parseFloat(thuongInput.value.replace(/,/g, '')) || 0;
        let phat = parseFloat(phatInput.value.replace(/,/g, '')) || 0;

        let total = luongCung + thuong - phat;
        if (total < 0) total = 0;

        document.getElementById(`final_${id}`).innerText = total.toLocaleString('en-US');
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.total-display').forEach(td => {
            total += parseFloat(td.innerText.replace(/,/g, '')) || 0;
        });
        document.getElementById('grand-total').innerText = total.toLocaleString('en-US');
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('money-input')) {
            let rawValue = e.target.value.replace(/,/g, '');
            if (!isNaN(rawValue) && rawValue !== '') {
                e.target.value = parseInt(rawValue).toLocaleString('en-US');
            }
        }
    });
</script>

<?php include 'inc/footer.php'; ?>