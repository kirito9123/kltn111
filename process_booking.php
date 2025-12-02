<?php
// ======= CHẶN RÁC & LOG LỖI =======
ob_start(); // nuốt mọi output rò rỉ (BOM/echo từ file include)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ======= TIỆN ÍCH TRẢ JSON =======
function respond(array $data, int $code = 200): void {
    while (ob_get_level() > 0) ob_end_clean(); // xóa sạch mọi output trước đó
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ======= KHỞI TẠO =======
    $filepath = realpath(dirname(__FILE__));
    include_once($filepath . '/lib/session.php');
    include_once($filepath . '/lib/database.php');
    Session::init();

    // ======= KIỂM TRA PHIÊN & METHOD =======
    if (!Session::get('userlogin')) {
        respond(['success' => false, 'message' => 'Bạn chưa đăng nhập!'], 401);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'Phương thức không hợp lệ!'], 405);
    }

    $db = new Database();
    if (!isset($db->link)) {
        respond(['success' => false, 'message' => 'Không kết nối được CSDL!'], 500);
    }

    // ======= LẤY & VALIDATE INPUT =======
    $id_user      = (int)(Session::get('id') ?? 0);
    $tenKH        = trim($_POST['customer_name']  ?? '');
    $dates        = trim($_POST['booking_date']   ?? '');
    $tg           = trim($_POST['booking_time']   ?? '');
    $loaiphong_id = (int)($_POST['loai_phong_id'] ?? 0);
    $phong_id     = (int)($_POST['phong_id']      ?? 0);
    $so_ban       = isset($_POST['tables']) && is_array($_POST['tables']) ? array_map('intval', $_POST['tables']) : [];

    if ($id_user <= 0 || $tenKH === '' || $dates === '' || $tg === '' || $loaiphong_id <= 0 || $phong_id <= 0 || empty($so_ban)) {
        respond(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!'], 400);
    }

    // Chuẩn hóa chuỗi
    $tenKH = mysqli_real_escape_string($db->link, $tenKH);
    $dates = mysqli_real_escape_string($db->link, $dates);
    $tg    = mysqli_real_escape_string($db->link, $tg);

    // ======= TRA CỨU THÔNG TIN PHỤ =======
    $loaiphong_ten = '';
    if ($rs = $db->select("SELECT tenloaiphong FROM loaiphong WHERE maloaiphong = {$loaiphong_id} LIMIT 1")) {
        if ($rs->num_rows > 0) $loaiphong_ten = $rs->fetch_assoc()['tenloaiphong'];
    }

    $phong_ten = '';
    if ($rs = $db->select("SELECT tenphong FROM phong WHERE id_phong = {$phong_id} LIMIT 1")) {
        if ($rs->num_rows > 0) $phong_ten = $rs->fetch_assoc()['tenphong'];
    }

    $so_user = '';
    if ($rs = $db->select("SELECT sodienthoai FROM khach_hang WHERE id = {$id_user} LIMIT 1")) {
        if ($rs->num_rows > 0) $so_user = $rs->fetch_assoc()['sodienthoai'];
    }
    $so_user = mysqli_real_escape_string($db->link, $so_user);

    // ======= KIỂM TRA BÀN TRỐNG =======
    $unavailable = [];
    foreach ($so_ban as $ban_id) {
        $ban_id = (int)$ban_id;
        if ($rs = $db->select("SELECT tenban, trangthai FROM ban WHERE id_ban = {$ban_id} LIMIT 1")) {
            if ($rs->num_rows > 0) {
                $row = $rs->fetch_assoc();
                if ((int)$row['trangthai'] === 1) $unavailable[] = $row['tenban'];
            }
        }
    }
    if (!empty($unavailable)) {
        respond(['success' => false, 'message' => 'Các bàn đã được đặt: ' . implode(', ', $unavailable)], 409);
    }

    // ======= CHUẨN BỊ DỮ LIỆU LƯU =======
    $so_ban_string = implode(',', $so_ban);
    $tenBanArray   = [];
    foreach ($so_ban as $ban_id) {
        if ($rs = $db->select("SELECT tenban FROM ban WHERE id_ban = {$ban_id} LIMIT 1")) {
            if ($rs->num_rows > 0) $tenBanArray[] = $rs->fetch_assoc()['tenban'];
        }
    }
    $tenBanString = implode(', ', $tenBanArray);

    $noidung = "Đặt bàn ngày {$dates} lúc {$tg} - Loại phòng: {$loaiphong_ten} - Phòng: {$phong_ten} - Bàn: {$tenBanString}";
    $noidung = mysqli_real_escape_string($db->link, $noidung);

    // ======= GHI HỢP ĐỒNG (đặt cọc = 0, số tiền = 0 ở bước này) =======
    // Lưu ý: nếu cột NOT NULL (strict mode), cần truyền giá trị (đã set 0 bên dưới)
    $so_tien   = 0; // tổng tiền sẽ tính sau khi chọn món
    $thanhtien = 0; // nếu bạn dùng cột này, giữ 0 ở bước booking

    $sql = "
        INSERT INTO hopdong (
            id_user, tenKH, dates, tg, noidung, so_user,
            tinhtrang, payment_status, payment_method,
            so_ban, loaiphong, phong, so_tien, thanhtien, created_at
        )
        VALUES (
            {$id_user}, '{$tenKH}', '{$dates}', '{$tg}', '{$noidung}', '{$so_user}',
            0, 'pending', 'cash',
            '{$so_ban_string}', '{$loaiphong_ten}', '{$phong_ten}', {$so_tien}, {$thanhtien}, NOW()
        )
    ";
    $ok = $db->insert($sql);

    if (!$ok) {
        respond(['success' => false, 'message' => 'Không thể lưu đơn đặt bàn!'], 500);
    }

    $booking_id = (int)$db->link->insert_id;

    // ======= CẬP NHẬT TRẠNG THÁI BÀN =======
    foreach ($so_ban as $ban_id) {
        $ban_id = (int)$ban_id;
        $db->update("UPDATE ban SET trangthai = 1 WHERE id_ban = {$ban_id}");
    }

    // ======= GHIM PHIÊN LÀM VIỆC (QUAN TRỌNG) =======
    Session::set('booking_id', $booking_id);
    Session::set('order_data', [
        'booking_id'    => $booking_id,
        'ho_ten'        => $tenKH,
        'so_dien_thoai' => $so_user,
        'ngay'          => $dates,
        'gio_bat_dau'   => $tg,
        'so_nguoi'      => null,              // nếu có input số người, bạn map vào đây
        'loaiphong_id'  => $loaiphong_id,
        'loaiphong_ten' => $loaiphong_ten,
        'phong_id'      => $phong_id,
        'phong_ten'     => $phong_ten,
        'so_ban_ids'    => $so_ban,          // mảng id_ban
        'so_ban_ten'    => $tenBanArray,     // mảng tên bàn
        'so_ban_string' => $so_ban_string,   // chuỗi id_ban join
        'ghi_chu'       => $noidung
    ]);

    // ======= TRẢ THÀNH CÔNG =======
    respond([
        'success'      => true,
        'message'      => 'Đặt bàn thành công!',
        'booking_id'   => $booking_id,
        'redirect_url' => "hopdong_menu.php?booking_id={$booking_id}"
    ]);

} catch (Throwable $e) {
    error_log('[BOOKING][ERR] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
}

// KHÔNG đặt "?>"
