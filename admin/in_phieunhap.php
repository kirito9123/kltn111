<?php
include_once __DIR__ . '/../lib/session.php';
Session::checkSession();
include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';
include_once __DIR__ . '/../classes/nguyenvatlieu.php';

if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.close();</script>"; 
    exit;
}

$id = $_GET['id'];
$nl = new nguyenvatlieu();

// Lấy thông tin chung (Header)
$header = $nl->get_phieu_header($id);
if ($header) {
    $phieu = $header->fetch_assoc();
} else {
    echo "Không tìm thấy phiếu."; exit;
}

// Lấy chi tiết món (Body)
$details = $nl->get_chi_tiet_phieu($id);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Nhập - <?php echo $phieu['ma_phieu']; ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 13pt; margin: 0; padding: 20px; color: #000; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        
        /* HEADER THÔNG TIN QUÁN */
        .brand-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .brand-info { width: 60%; }
        .brand-name { font-size: 22px; font-weight: bold; text-transform: uppercase; margin: 0 0 5px 0; }
        .brand-detail { margin: 2px 0; font-size: 14px; }
        
        .bill-title { width: 40%; text-align: right; }
        .bill-name { font-size: 26px; font-weight: bold; text-transform: uppercase; margin: 0; padding-top: 10px; }
        .print-date { font-style: italic; font-size: 13px; margin-top: 5px; }

        /* THÔNG TIN PHIẾU */
        .info-section { margin-bottom: 20px; line-height: 1.6; }
        .row-info { display: flex; justify-content: space-between; }
        
        /* BẢNG DỮ LIỆU */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: middle; }
        th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* CHỮ KÝ */
        .footer { margin-top: 40px; display: flex; justify-content: space-between; text-align: center; }
        .footer > div { width: 30%; }
        .signature-space { height: 80px; }

        /* NÚT BẤM (KHÔNG IN) */
        @media print {
            @page { margin: 1cm; }
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
        }
        
        .btn-action {
            padding: 8px 15px; cursor: pointer; border: none; border-radius: 4px; 
            font-weight: bold; margin-right: 10px; color: white;
        }
    </style>
</head>
<body onload="window.print()">

<div class="container">
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" class="btn-action" style="background:#2980b9;">🖨️ In Ngay</button>
        <button onclick="window.close()" class="btn-action" style="background:#c0392b;">❌ Đóng</button>
    </div>

    <div class="brand-header">
        <div class="brand-info">
            <h1 class="brand-name">NHÀ HÀNG TRISKIET</h1>
            <p class="brand-detail">📍 Đ/c: 12 Nguyễn Văn Bảo, P.4, Q. Gò Vấp, TP.HCM</p>
            <p class="brand-detail">☎️ SĐT: 0869 387 601</p>
            <p class="brand-detail">📧 Email: tknhahangtriskiet@gmail.com</p>
        </div>
        <div class="bill-title">
            <h2 class="bill-name">PHIẾU NHẬP KHO</h2>
            <div class="print-date">Số: <?php echo $phieu['ma_phieu']; ?></div>
            <div class="print-date">Ngày in: <?php echo date('d/m/Y H:i'); ?></div>
        </div>
    </div>

    <div class="info-section">
        <div class="row-info">
            <span><strong>Người nhập hàng:</strong> <?php echo $phieu['nhan_vien']; ?></span>
            <span><strong>Thời gian nhập:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['ngay_nhap'])); ?></span>
        </div>
        <div style="margin-top: 5px;">
            <strong>Ghi chú / Nhà cung cấp:</strong> <?php echo empty($phieu['ghi_chu']) ? '................................................' : $phieu['ghi_chu']; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 35%;">Tên Hàng Hóa / Nguyên Liệu</th>
                <th style="width: 10%;">ĐVT</th>
                <th style="width: 15%;">Số Lượng</th>
                <th style="width: 15%;">Đơn Giá</th>
                <th style="width: 20%;">Thành Tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 0;
            if ($details) {
                while ($row = $details->fetch_assoc()) {
                    $i++;
            ?>
            <tr>
                <td class="text-center"><?php echo $i; ?></td>
                <td><?php echo htmlspecialchars($row['ten_nl']); ?></td>
                <td class="text-center"><?php echo $row['don_vi']; ?></td>
                
                <td class="text-center"><?php echo floatval($row['so_luong_nhap']); ?></td>
                
                <td class="text-right"><?php echo number_format($row['gia_nhap'], 0, ',', '.'); ?></td>
                <td class="text-right bold"><?php echo number_format($row['thanh_tien'], 0, ',', '.'); ?></td>
            </tr>
            <?php 
                }
            }
            ?>
            <tr>
                <td colspan="5" class="text-right bold" style="font-size: 16px;">TỔNG CỘNG THANH TOÁN:</td>
                <td class="text-right bold" style="font-size: 16px;"><?php echo number_format($phieu['tong_tien'], 0, ',', '.'); ?> ₫</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 20px;">
        <i>Số tiền bằng chữ: ...........................................................................................................................................</i>
    </div>

    <div class="footer">
        <div>
            <strong>Người Lập Phiếu</strong><br>
            <i>(Ký, họ tên)</i>
            <div class="signature-space"></div>
            <div><?php echo $phieu['nhan_vien']; ?></div>
        </div>
        <div>
            <strong>Thủ Kho / Nhận Hàng</strong><br>
            <i>(Ký, họ tên)</i>
            <div class="signature-space"></div>
        </div>
        <div>
            <strong>Giám Đốc / Kế Toán</strong><br>
            <i>(Ký, họ tên)</i>
            <div class="signature-space"></div>
        </div>
    </div>
</div>

</body>
</html>