<?php
include '../lib/session.php';
Session::checkSession();
include_once '../classes/nhanvienquay.php';

if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "Không tìm thấy hóa đơn.";
    exit;
}

$id = (int)$_GET['id'];
$nv = new nhanvienquay();

// Lấy thông tin đơn
$info_rs = $nv->get_thong_tin_don_hang($id);
$info = ($info_rs) ? $info_rs->fetch_assoc() : null;

// Lấy chi tiết món
$list_mon = $nv->get_chi_tiet_mon_an($id);

// Lấy tên nhân viên từ Session
$staff_name = Session::get('adminname');

if (!$info) {
    echo "Đơn hàng không tồn tại.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?php echo $id; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            margin: 0;
            padding: 20px;
            background: #555;
        }

        /* Khung hóa đơn mô phỏng giấy in nhiệt */
        .invoice-box {
            max-width: 380px;
            /* Khổ 80mm */
            margin: auto;
            padding: 15px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        h2 {
            margin: 5px 0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        p {
            margin: 2px 0;
            text-align: center;
            font-size: 12px;
        }

        .dashed-line {
            border-top: 1px dashed #333;
            margin: 10px 0;
        }

        .title-bill {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }

        /* Bảng thông tin chung */
        .info-table {
            width: 100%;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .info-label {
            white-space: nowrap;
            padding-right: 5px;
            font-weight: bold;
        }

        .info-val {
            text-align: right;
        }

        /* Bảng món ăn */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        .item-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            text-transform: uppercase;
            font-size: 11px;
        }

        .item-table td {
            padding: 5px 0;
            border-bottom: 1px dashed #ccc;
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

        /* Phần tổng tiền */
        .total-section {
            margin-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 13px;
        }

        .final-total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-style: italic;
            font-size: 11px;
        }

        /* Thanh công cụ (Nút bấm) */
        .toolbar {
            text-align: center;
            margin-bottom: 15px;
            position: sticky;
            top: 0;
            background: #555;
            padding: 10px;
            z-index: 100;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-family: sans-serif;
        }

        .btn-print {
            background: #ff9800;
            color: white;
        }

        .btn-back {
            background: #fff;
            color: #333;
            margin-right: 10px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Chế độ in: Ẩn nút, nền trắng */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .invoice-box {
                box-shadow: none;
                max-width: 100%;
                width: 100%;
                padding: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <a href="booking_list.php" class="btn btn-back">⬅ Quay lại</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ In Hóa Đơn</button>
    </div>

    <div class="invoice-box">
        <h2>TRK RESTAURANT</h2>
        <p>123 Đường ABC, Quận XYZ, TP.HCM</p>
        <p>Hotline: 0909 123 456</p>

        <div class="dashed-line"></div>
        <div class="title-bill">Phiếu Thanh Toán</div>

        <table class="info-table">
            <tr>
                <td class="info-label">Số phiếu:</td>
                <td class="info-val">#<?php echo $id; ?></td>
            </tr>
            <tr>
                <td class="info-label">Ngày tạo:</td>
                <td class="info-val"><?php echo $info['dates'] . ' ' . $info['tg']; ?></td>
            </tr>
            <tr>
                <td class="info-label">Ngày in:</td>
                <td class="info-val"><?php echo date('d/m/Y H:i'); ?></td>
            </tr>
            <tr>
                <td class="info-label">Thu ngân:</td>
                <td class="info-val"><?php echo $staff_name; ?></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="dashed-line" style="margin: 5px 0; border-color:#eee;"></div>
                </td>
            </tr>
            <tr>
                <td class="info-label">Khách hàng:</td>
                <td class="info-val"><?php echo htmlspecialchars($info['tenKH']); ?></td>
            </tr>
            <tr>
                <td class="info-label">Vị trí:</td>
                <td class="info-val">
                    <?php
                    echo "Bàn " . $info['so_ban'];
                    if (!empty($info['phong'])) echo " (" . $info['phong'] . ")";
                    ?>
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Tên món</th>
                    <th class="text-center" style="width: 15%;">SL</th>
                    <th class="text-right" style="width: 20%;">Đ.Giá</th>
                    <th class="text-right" style="width: 25%;">T.Tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                if ($list_mon) {
                    while ($row = $list_mon->fetch_assoc()) {
                        $tt = $row['thanhtien'];
                        $total += $tt;
                        echo "<tr>
                                <td>" . htmlspecialchars($row['name_mon']) . "</td>
                                <td class='text-center'>{$row['soluong']}</td>
                                <td class='text-right'>" . number_format($row['gia']) . "</td>
                                <td class='text-right'>" . number_format($tt) . "</td>
                              </tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span>Tổng tiền hàng:</span>
                <span><?php echo number_format($total, 0, ',', '.'); ?></span>
            </div>
            <div class="total-row final-total">
                <span>THÀNH TIỀN:</span>
                <span><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</span>
            </div>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ!</p>
            <p>Pass Wifi: 88888888</p>
            <p>--- Hẹn gặp lại ---</p>
        </div>
    </div>

</body>

</html>