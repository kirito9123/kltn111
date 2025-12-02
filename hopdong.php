<?php
include 'inc/header.php';
Session::checkSession();

// ====== GHIM BOOKING_ID ======
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (int)Session::get('booking_id');
if ($booking_id > 0) Session::set('booking_id', $booking_id);

// ====== LẤY SESSION ======
$order_data = Session::get('order_data');
$menu_chon  = Session::get('menu_chon');

// Ép kiểu an toàn
if (!is_array($order_data)) $order_data = [];
if (!is_array($menu_chon))  $menu_chon  = [];

// ====== FALLBACK: nạp lại từ DB hopdong nếu mất order_data ======
if (empty($order_data)) {
    include_once realpath(dirname(__FILE__)) . '/lib/database.php';
    $db = new Database();
    if (isset($db->link)) {
        $booking_id_int = (int)$booking_id;
        $rs = $db->select("SELECT * FROM hopdong WHERE id = {$booking_id_int} LIMIT 1");
        if ($rs && $rs->num_rows > 0) {
            $hd = $rs->fetch_assoc();
            // Chuẩn hóa key về từ điển mềm dùng chung
            $order_data = [
                'booking_id'     => (int)($hd['id'] ?? 0),
                'ghi_chu'        => $hd['noidung']   ?? '',
                'so_nguoi'       => $hd['khach']     ?? '',
                'loaiphong_ten'  => $hd['loaiphong'] ?? '',
                'phong_ten'      => $hd['phong']     ?? '',
                'so_ban_string'  => $hd['so_ban']    ?? '',
                'gio_bat_dau'    => $hd['tg']        ?? '',
                'ngay'           => $hd['dates']     ?? '',
                'so_dien_thoai'  => $hd['so_user']   ?? '',
                'ho_ten'         => $hd['tenKH']     ?? '',
            ];
            Session::set('order_data', $order_data);
        }
    }
}


// ====== HELPERS AN TOÀN ======
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Lấy giá trị đầu tiên tồn tại/không rỗng trong danh sách key
function val(array $a, ...$keys){
    foreach ($keys as $k){
        if (array_key_exists($k, $a) && $a[$k] !== null && $a[$k] !== '') return $a[$k];
    }
    return '';
}

// ====== MAP DỮ LIỆU RA BIẾN HIỂN THỊ (không chọc trực tiếp vào $order_data['...']) ======
$kh_name   = Session::get('name') ?: val($order_data, 'ho_ten','tenKH','kh_ten');
$kh_sdt    = Session::get('sdt')  ?: val($order_data, 'so_dien_thoai','so_user','kh_sdt');

$noi_dung  = val($order_data, 'noidung','ghi_chu','noi_dung');
$so_khach  = val($order_data, 'khach','so_nguoi','so_khach');
$khu_vuc   = val($order_data, 'vitri_id','loaiphong_ten','loaiphong','khu_vuc');

// so_ban: ưu tiên chuỗi; nếu là mảng thì join
$so_ban = val($order_data, 'so_ban','so_ban_string');
if ($so_ban === '' && isset($order_data['so_ban_ten']) && is_array($order_data['so_ban_ten'])) {
    $so_ban = implode(', ', $order_data['so_ban_ten']);
}

$gio_tiec  = val($order_data, 'time','gio_bat_dau','tg');
$ngay_tiec = val($order_data, 'date','ngay','dates');

$date      = getdate();
$subtotal  = 0;

// ====== ĐẢM BẢO $mon và formatter tiền tệ tồn tại ======
// Cố gắng tận dụng $fm->formatMoney nếu có; nếu không thì fallback.
$fmt_money = function($n){
    return number_format((float)$n, 0, ',', '.');
};
if (isset($fm) && is_object($fm) && method_exists($fm, 'formatMoney')) {
    $fmt_money = function($n) use ($fm){ return $fm->formatMoney($n); };
}

// Thử tự khởi tạo $mon nếu chưa có
if (!isset($mon)) {
    // Tùy dự án của bạn, tên file/class có thể là Mon.php/mon.php
    @include_once __DIR__ . '/classes/Mon.php';
    if (class_exists('Mon')) { $mon = new Mon(); }
}
?>
<style>
    h1,h2,h3,h4,h5,h6{ text-align:center; font-family:'Segoe UI',sans-serif; margin:10px 0; font-weight:600 }
    h1{font-size:28px} h2{font-size:24px} h3{font-size:20px} h4,h5,h6{font-size:17px}
    label{font-weight:500;color:#333}
    .text{display:block;margin:8px auto;text-align:justify;max-width:900px;color:#444;font-size:15px;line-height:1.6}
    .text1{color:#d9534f;font-weight:600}
    table{width:90%;margin:20px auto;border-radius:10px;overflow:hidden;box-shadow:0 0 10px rgba(0,0,0,.05)}
    .table th{background:#d19c65;color:#fff;font-weight:bold;text-align:center}
    .table td{vertical-align:middle;text-align:center;font-size:15px}
    .btn-primary{background:#d19c65;border:none;border-radius:30px;font-size:16px;font-weight:600;padding:12px 30px;transition:.3s}
    .btn-primary:hover{background:#b87e4b}
    hr{width:80%;margin:30px auto;border-top:2px solid #ccc}
    .contract-section{max-width:950px;margin:0 auto;padding:20px;background:#fff;border-radius:10px}
    @media (max-width:768px){h1,h2,h3{font-size:20px} table{width:100%} .btn-primary{width:90%}}
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Đặt bàn</h1>
                <p class="breadcrumbs">
                    <span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
                    <span>Đặt chỗ <i class="ion-ios-arrow-forward"></i></span>
                </p>
            </div>
        </div>
    </div>
</section>

<h1><label>Nhà Hàng TRisKiet</label></h1>
<h2><label>HỢP ĐỒNG NHẬN TIỆC</label></h2>
<h5>Ngày <?php echo (int)$date['mday'] ?> Tháng <?php echo (int)$date['mon'] ?> Năm <?php echo (int)$date['year'] ?></h5>

<h4>
    <label>Đại diện nhà hàng:</label> Nguyễn Minh Trí
    <label>Chức vụ:</label> Quản Lý
    <label>ĐT:</label> 0869387701
</h4>

<h4>
    <label>Người đặt tiệc:</label> <label class="text1"><?php echo h($kh_name) ?></label>
    <label>SĐT:</label> <label class="text1"><?php echo h($kh_sdt) ?></label>
</h4>

<h4>
    <label>Nội dung tiệc:</label> <label class="text1"><?php echo h($noi_dung) ?></label>
    <label>Số lượng khách:</label> <label class="text1"><?php echo h($so_khach) ?></label>
</h4>

<h4>
    <label>Khu vực tiệc:</label> <label class="text1"><?php echo h($khu_vuc) ?></label>
    <label>Số bàn:</label> <label class="text1"><?php echo h($so_ban) ?></label>
</h4>

<h4>
    <label>Thời gian tiệc:</label> <label class="text1"><?php echo h($gio_tiec) ?> Ngày: <?php echo h($ngay_tiec) ?></label>
</h4>

<hr>
<h2><label>THỰC ĐƠN TIỆC</label></h2>

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12 ftco-animate">
                <div class="cart-list">
                    <table class="table">
                        <thead class="thead-primary">
                            <tr class="text-center">
                                <th>Món ăn</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $menu_snapshot = []; // <-- thêm biến này trước vòng lặp

                                if (!empty($menu_chon) && is_array($menu_chon) && isset($mon) && method_exists($mon, 'getMonById')) {
                                    foreach ($menu_chon as $item) {
                                        $id_mon  = (int)($item['id_mon'] ?? 0);
                                        $soluong = (int)($item['soluong'] ?? 0);
                                        if ($id_mon <= 0 || $soluong <= 0) continue;

                                        $monan_result = $mon->getMonById($id_mon);
                                        if ($monan_result && $monan_result->num_rows > 0) {
                                            $monan     = $monan_result->fetch_assoc();
                                            $gia       = (float)($monan['gia_mon'] ?? 0);
                                            $ten       = (string)($monan['name_mon'] ?? 'Món');
                                            $thanhtien = $gia * $soluong;
                                            $subtotal += $thanhtien;

                                            // ===> THÊM: nhét vào snapshot
                                            $menu_snapshot[] = [
                                                'id_mon'     => $id_mon,
                                                'name_mon'   => $ten,
                                                'gia_mon'    => $gia,
                                                'soluong'    => $soluong,
                                                'thanhtien'  => $thanhtien,
                                            ];
                                            ?>
                                            <tr class="text-center">
                                                <td class="product-name"><h3><?php echo h($ten) ?></h3></td>
                                                <td class="price"><?php echo h($fmt_money($gia)) . " VNĐ"; ?></td>
                                                <td class="product-name"><h3><?php echo (int)$soluong ?></h3></td>
                                                <td class="total"><?php echo h($fmt_money($thanhtien)) . " VNĐ"; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                }

                                // LƯU tổng + cọc + snapshot để trang thanh toán đọc lại
                                $deposit = $subtotal * 0.2;
                                Session::set('menu_subtotal', $subtotal);
                                Session::set('order_data_deposit', $deposit);
                                Session::set('menu_snapshot', $menu_snapshot); // <=== QUAN TRỌNG
                                ?>
                        </tbody>
                    </table>
                    <h4><label>Tổng: <?php echo h($fmt_money($subtotal)) ?> VNĐ</label></h4>
                    <h4><label>Đặt cọc (20%): <?php echo h($fmt_money($deposit)) ?> VNĐ</label></h4>
                </div>
            </div>
        </div>
    </div>
</section>

<h2><label>(ĐƠN GIÁ CHƯA BAO GỒM 10% VAT)</label></h2>
<h4><label>Các loại khác:</label></h4>
<h4>Background: ……………Kích thước: ……………Nội dung: ……………………………..</h4>
<h4>Karaoke: …………………………Dàn nhạc: ………………………………………………</h4>
<h4>Sân khấu: …………………………Phí trang trí: …………………………………………...</h4>
<h4>Phí phòng lạnh: ………………………………………trên tổng bill thanh toán.</h4>
<h4>Khuyến mãi: ………………………………………………………………………………...</h4>
<h4><label>(ĐƠN GIÁ CHƯA BAO GỒM 10% VAT)</label></h4>

<h3><label>Hai bên cùng đồng ý:</label></h3>
<label class="text">1. Trong thời gian đặt cọc, nếu bên Quý Khách đơn phương hủy tiệc vì bất cứ lý do gì hoặc không đến đúng thời gian và ngày đặt tiệc thì coi như Quý Khách đã hủy tiệc. Bên nhà hàng sẽ không hoàn trả số tiền đã đặt cọc.</label>
<label class="text">2. Trong thời gian đặt cọc, nếu có thay đổi về món ăn và thức uống, Quý Khách phải liên hệ nhà hàng trước 48h để nhà hàng kịp thời chuẩn bị.</label>
<hr>

<!-- XÁC NHẬN -> LƯU HỢP ĐỒNG -->
<form method="post" action="thanhtoan.php" class="text-center" style="margin-bottom:30px;">
    <input type="hidden" name="booking_id" value="<?php echo (int)$booking_id; ?>">
    <button type="submit" class="btn btn-primary py-3 px-5">Xác Nhận</button>
</form>



<?php include 'inc/footer.php'; ?>
