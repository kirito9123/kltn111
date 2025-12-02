<?php
// =================== LƯU HÓA ĐƠN (KHỚP VỚI CẤU TRÚC CỦA BẠN) ===================

ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function respond(array $data, int $code = 200): void {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function save_invoice(): void {
    $filepath = realpath(dirname(__FILE__));
    include_once($filepath . '/lib/session.php');
    include_once($filepath . '/lib/database.php');
    Session::init();

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

    // ======= LẤY INPUT =======
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : (int)(Session::get('booking_id') ?? 0);
    if ($booking_id <= 0) {
        respond(['success' => false, 'message' => 'Thiếu booking_id!'], 400);
    }

    $items = $_POST['items'] ?? null;
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        if (json_last_error() === JSON_ERROR_NONE) $items = $decoded;
    }
    if (!is_array($items) || empty($items)) {
        $items = Session::get('menu_chon');
    }
    if (!is_array($items) || empty($items)) {
        respond(['success' => false, 'message' => 'Không có danh sách món để lưu!'], 400);
    }

    $replace = isset($_POST['replace']) ? (int)$_POST['replace'] : 1;

    // ======= LẤY GIÁ MÓN NẾU THIẾU =======
    $mon = null;
    @include_once __DIR__ . '/classes/Mon.php';
    if (class_exists('Mon')) $mon = new Mon();

    $getGia = function(int $id_mon) use ($mon): float {
        if ($mon && method_exists($mon, 'getMonById')) {
            $rs = $mon->getMonById($id_mon);
            if ($rs && $rs->num_rows > 0) {
                $row = $rs->fetch_assoc();
                return (float)($row['gia_mon'] ?? 0);
            }
        }
        return 0.0;
    };

    // ======= BẮT ĐẦU LƯU DỮ LIỆU =======
    $mysqli = $db->link;
    $mysqli->begin_transaction();

    try {
        // Xóa chi tiết cũ (nếu cần)
        if ($replace === 1) {
            $stmt = $mysqli->prepare("DELETE FROM hopdong_chitiet WHERE hopdong_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
        }

        // Thêm chi tiết mới
        $sql = "INSERT INTO hopdong_chitiet (hopdong_id, monan_id, soluong, gia, thanhtien)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        $total = 0.0;
        $count = 0;

        foreach ($items as $it) {
            $id_mon  = (int)($it['id_mon'] ?? 0);
            $soluong = (int)($it['soluong'] ?? 0);
            if ($id_mon <= 0 || $soluong <= 0) continue;

            $gia = isset($it['gia']) && $it['gia'] !== '' ? (float)$it['gia'] : $getGia($id_mon);
            $thanhtien = $gia * $soluong;

            $stmt->bind_param("iiidd", $booking_id, $id_mon, $soluong, $gia, $thanhtien);
            $stmt->execute();

            $total += $thanhtien;
            $count++;
        }
        $stmt->close();

        if ($count === 0) {
            $mysqli->rollback();
            respond(['success' => false, 'message' => 'Không có món hợp lệ để lưu!'], 400);
        }

        // Cập nhật tổng tiền vào hopdong
        $stmt2 = $mysqli->prepare("UPDATE hopdong 
                                   SET so_tien=?, thanhtien=?, payment_status='pending', updated_at=NOW()
                                   WHERE id=? LIMIT 1");
        $stmt2->bind_param("ddi", $total, $total, $booking_id);
        $stmt2->execute();
        $stmt2->close();

        $mysqli->commit();

        // Lưu backup session cho bước thanh toán
        Session::set('menu_chon_backup', $items);
        Session::set('order_booking_id', $booking_id);
        Session::set('menu_subtotal', $total);

        respond([
            'success' => true,
            'message' => 'Hóa đơn đã được lưu thành công!',
            'booking_id' => $booking_id,
            'total' => $total,
            'count' => $count,
            'redirect_url' => "xacnhan_thanhtoan.php?booking_id={$booking_id}"
        ]);

    } catch (Throwable $e) {
        $mysqli->rollback();
        error_log('[SAVE_INVOICE][ERR] ' . $e->getMessage());
        respond(['success' => false, 'message' => 'Lỗi khi lưu hóa đơn: ' . $e->getMessage()], 500);
    }
}

// Nếu là endpoint độc lập thì mở dòng sau:
save_invoice();
