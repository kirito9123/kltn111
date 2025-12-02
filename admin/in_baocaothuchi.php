<?php
include '../lib/session.php';
Session::checkSession();
include '../classes/thongke.php';

$tk = new ThongKe();
$thang = isset($_GET['thang']) ? $_GET['thang'] : date('m');
$nam = isset($_GET['nam']) ? $_GET['nam'] : date('Y');

$tongThu = $tk->getTongDoanhThu($thang, $nam);
$chiLuong = $tk->getTongChiLuong($thang, $nam);
$chiNhapHang = $tk->getTongChiPhiKhac($thang, $nam);
$tongChi = $chiLuong + $chiNhapHang;
$loiNhuan = $tongThu - $tongChi;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Doanh Thu Tháng <?php echo "$thang/$nam"; ?></title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            padding: 40px;
            background: #ccc;
        }

        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 2cm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-style: italic;
            margin-bottom: 30px;
        }

        .overview-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 2px solid #000;
        }

        .overview-table td {
            padding: 10px;
            border: 1px solid #000;
            font-size: 16px;
        }

        .label {
            font-weight: bold;
            width: 60%;
        }

        .value {
            text-align: right;
            width: 40%;
        }

        .section-title {
            border-bottom: 1px solid #000;
            margin-top: 30px;
            font-weight: bold;
            font-size: 18px;
            padding-bottom: 5px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        .detail-table th {
            background: #eee;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: blue;
            color: white;
            text-decoration: none;
            font-family: sans-serif;
            border-radius: 5px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .page {
                box-shadow: none;
                margin: 0;
                width: auto;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <a href="javascript:window.print()" class="print-btn">IN BÁO CÁO</a>

    <div class="page">
        <h1>BÁO CÁO DOANH THU & THU CHI</h1>
        <p class="subtitle">Tháng <?php echo $thang; ?> Năm <?php echo $nam; ?></p>
        <p><strong>Người lập:</strong> <?php echo Session::get('adminname'); ?> | <strong>Ngày xuất:</strong> <?php echo date('d/m/Y H:i'); ?></p>

        <div class="section-title">I. TỔNG HỢP TÀI CHÍNH</div>
        <table class="overview-table">
            <tr>
                <td class="label">1. Tổng Doanh Thu (Từ Hợp đồng)</td>
                <td class="value" style="color: green;">+ <?php echo number_format($tongThu); ?> VNĐ</td>
            </tr>
            <tr>
                <td class="label">2. Tổng Chi Lương Nhân Viên</td>
                <td class="value">- <?php echo number_format($chiLuong); ?> VNĐ</td>
            </tr>
            <tr>
                <td class="label">3. Chi Phí Khác (Nhập hàng, Điện, Nước...)</td>
                <td class="value">- <?php echo number_format($chiNhapHang); ?> VNĐ</td>
            </tr>
            <tr style="background: #f0f0f0;">
                <td class="label" style="font-size: 18px;">LỢI NHUẬN THUẦN</td>
                <td class="value" style="font-size: 18px; font-weight: bold; color: <?php echo ($loiNhuan >= 0) ? 'black' : 'red'; ?>;">
                    <?php echo number_format($loiNhuan); ?> VNĐ
                </td>
            </tr>
        </table>

        <div class="section-title">II. CHI TIẾT CÁC KHOẢN CHI KHÁC</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Nội dung chi</th>
                    <th>Ghi chú</th>
                    <th style="text-align: right;">Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dsChi = $tk->getListChiPhi($thang, $nam);
                if ($dsChi): while ($d = $dsChi->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($d['ngay_chi'])); ?></td>
                            <td><?php echo $d['ten_chiphi']; ?></td>
                            <td><?php echo $d['ghi_chu']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($d['so_tien']); ?></td>
                        </tr>
                <?php endwhile;
                endif; ?>
            </tbody>
        </table>

        <p style="margin-top: 10px; font-style: italic; font-size: 12px;">* Số liệu lương nhân viên vui lòng xem tại "Bảng lương chi tiết".</p>

        <div class="footer">
            <div>
                <strong>Người Lập Bảng</strong><br><br><br><br>
                (Ký, họ tên)
            </div>
            <div>
                <strong>Giám Đốc Duyệt</strong><br><br><br><br>
                (Ký, đóng dấu)
            </div>
        </div>
    </div>
</body>

</html>