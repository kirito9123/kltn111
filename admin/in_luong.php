<?php
include '../lib/session.php';
Session::checkSession();
include '../classes/luong.php';

$luong = new Luong();
$roles = [0 => 'Admin', 1 => 'Kế toán', 2 => 'NV Quầy', 3 => 'NV Bếp', 4 => 'NV Chạy bàn'];

// XỬ LÝ LOGIC: IN LẺ HAY IN TỔNG?
$mode = ''; // 'single' hoặc 'list'
$data = null;
$title = '';

if (isset($_GET['id'])) {
    // In lẻ 1 người
    $mode = 'single';
    $data = $luong->layChiTietLuongByID($_GET['id']);
    if (!$data) die("Không tìm thấy dữ liệu lương ID: " . $_GET['id']);
    $title = "PHIẾU LƯƠNG THÁNG " . $data['thang'] . "/" . $data['nam'];
} elseif (isset($_GET['thang']) && isset($_GET['nam'])) {
    // In danh sách tổng hợp
    $mode = 'list';
    $thang = $_GET['thang'];
    $nam = $_GET['nam'];
    $data = $luong->layBangLuongTheoThang($thang, $nam);
    $title = "BẢNG THANH TOÁN LƯƠNG THÁNG $thang NĂM $nam";
} else {
    die("Tham số không hợp lệ!");
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            background: #ccc;
            padding: 20px;
        }

        .page {
            background: #fff;
            width: 21cm;
            min-height: 29.7cm;
            padding: 2cm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-style: italic;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* Table Style */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th {
            background: #eee;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }

        td {
            padding: 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        /* Signature */
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .footer div {
            width: 30%;
        }

        /* Print Button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0d6efd;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-family: sans-serif;
            border-radius: 5px;
            font-weight: bold;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .page {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

    <a href="javascript:window.print()" class="print-btn">🖨️ IN ẤN / LƯU PDF</a>

    <div class="page">
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 20px;">
            <div>
                <img src="images/livelogo.png" alt="Logo" style="height: 80px;">
            </div>
            <div>
                <strong>NHÀ HÀNG TRISKIET</strong><br>
                Địa chỉ: 12 Nguyễn Văn Bảo, Phường 1, Quận Gò Vấp, TP.HCM<br>
                Điện thoại: 0909.123.456
            </div>
        </div>
        <hr style="border-top: 2px double #000; margin-bottom: 20px;">

        <h1><?php echo $title; ?></h1>

        <?php if ($mode == 'list'): ?>
            <p class="subtitle">Đơn vị tính: VNĐ</p>
            <table>
                <thead>
                    <tr>
                        <th width="5%">STT</th>
                        <th width="20%">Họ và Tên</th>
                        <th width="10%">Chức vụ</th>
                        <th width="10%">Số Ca</th>
                        <th width="15%">Lương Cứng</th>
                        <th width="10%">Thưởng</th>
                        <th width="10%">Phạt</th>
                        <th width="20%">Thực Lãnh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    $tongTien = 0;
                    if ($data): while ($row = $data->fetch_assoc()):
                            $i++;
                            $luongCung = ($row['tong_ca'] * $row['muc_luong_ca']) + $row['phu_cap'];
                            $tongTien += $row['thuc_lanh'];
                    ?>
                            <tr>
                                <td class="text-center"><?php echo $i; ?></td>
                                <td><?php echo $row['hoten']; ?></td>
                                <td class="text-center"><?php echo isset($roles[$row['level']]) ? $roles[$row['level']] : $row['level']; ?></td>
                                <td class="text-center"><?php echo $row['tong_ca']; ?></td>
                                <td class="text-right"><?php echo number_format($luongCung); ?></td>
                                <td class="text-right"><?php echo number_format($row['tien_thuong']); ?></td>
                                <td class="text-right"><?php echo number_format($row['tien_phat']); ?></td>
                                <td class="text-right bold"><?php echo number_format($row['thuc_lanh']); ?></td>
                            </tr>
                    <?php endwhile;
                    endif; ?>

                    <tr style="background: #f9f9f9;">
                        <td colspan="7" class="text-center bold" style="padding: 12px;">TỔNG CỘNG CHI LƯƠNG THÁNG:</td>
                        <td class="text-right bold" style="font-size: 16px;"><?php echo number_format($tongTien); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>


        <?php if ($mode == 'single'): ?>
            <p class="subtitle" style="margin-bottom: 40px;">(Dành cho nhân viên)</p>

            <table style="border: none; margin-bottom: 10px;">
                <tr>
                    <td style="border: none; width: 150px; font-weight: bold;">Mã nhân viên:</td>
                    <td style="border: none;">#<?php echo $data['mans']; ?></td>
                    <td style="border: none; width: 150px; font-weight: bold;">Bộ phận:</td>
                    <td style="border: none;"><?php echo isset($roles[$data['level']]) ? $roles[$data['level']] : $data['level']; ?></td>
                </tr>
                <tr>
                    <td style="border: none; font-weight: bold;">Họ và tên:</td>
                    <td style="border: none; font-size: 16px;"><strong><?php echo $data['hoten']; ?></strong></td>
                    <td style="border: none; font-weight: bold;">Ngày thanh toán:</td>
                    <td style="border: none;"><?php echo $data['ngay_thanh_toan'] ? date('d/m/Y', strtotime($data['ngay_thanh_toan'])) : 'Chưa chi'; ?></td>
                </tr>
            </table>

            <h3 style="margin-top: 30px; border-bottom: 1px solid #000; padding-bottom: 5px;">CHI TIẾT THU NHẬP</h3>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Khoản mục</th>
                        <th style="text-align: left;">Diễn giải</th>
                        <th style="text-align: right;">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Lương theo ca</td>
                        <td><?php echo $data['tong_ca']; ?> ca x <?php echo number_format($data['muc_luong_ca']); ?></td>
                        <td class="text-right"><?php echo number_format($data['tong_ca'] * $data['muc_luong_ca']); ?></td>
                    </tr>
                    <tr>
                        <td>Phụ cấp</td>
                        <td>Cố định hàng tháng</td>
                        <td class="text-right"><?php echo number_format($data['phu_cap']); ?></td>
                    </tr>
                    <?php if ($data['tien_thuong'] > 0): ?>
                        <tr>
                            <td>Thưởng</td>
                            <td><?php echo $data['ly_do']; ?></td>
                            <td class="text-right">+ <?php echo number_format($data['tien_thuong']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($data['tien_phat'] > 0): ?>
                        <tr>
                            <td>Khấu trừ / Phạt</td>
                            <td><?php echo $data['ly_do']; ?></td>
                            <td class="text-right">- <?php echo number_format($data['tien_phat']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr style="background: #eee;">
                        <td colspan="2" class="bold" style="text-align: right; font-size: 16px;">THỰC LÃNH:</td>
                        <td class="text-right bold" style="font-size: 18px;"><?php echo number_format($data['thuc_lanh']); ?> VNĐ</td>
                    </tr>
                </tbody>
            </table>
            <p><i>Bằng chữ: .....................................................................................................................................</i></p>
        <?php endif; ?>


        <div class="footer">
            <div>
                <strong>Người Lập Biểu</strong><br>
                (Ký, họ tên)<br><br><br><br>
                <?php echo Session::get('adminName'); ?>
            </div>

            <?php if ($mode == 'single'): ?>
                <div>
                    <strong>Người Nhận Tiền</strong><br>
                    (Ký, họ tên)<br><br><br><br>
                    <?php echo $data['hoten']; ?>
                </div>
            <?php endif; ?>

            <div>
                <strong>Giám Đốc Duyệt</strong><br>
                (Ký, đóng dấu)<br><br><br><br>
            </div>
        </div>
    </div>

</body>

</html>