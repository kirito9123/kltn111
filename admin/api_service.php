<?php
// Bật bộ đệm đầu ra để chặn lỗi PHP/khoảng trắng thừa
ob_start(); 

require_once '../classes/NhanVienPhucVu.php'; 
require_once '../helpers/format.php';

$phucvu = new NhanVienPhucVu();
$fm = new Format();

// ==== XỬ LÝ POST: XÁC NHẬN ĐÃ PHỤC VỤ ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_served') {
    $id = (int)($_POST['id'] ?? 0);
    
    ob_clean(); 
    $ok = ($id > 0) ? $phucvu->hoan_thanh_phuc_vu($id) : false; 
    echo $ok ? 'success' : 'error';
    exit;
}

// ==== HÀM PHỤ TRỢ: XỬ LÝ DANH SÁCH ĐƠN HÀNG ====
// Hàm này giúp ta không phải viết lại code xử lý cho cả 2 danh sách (Chờ & Lịch sử)
function processOrderList($rs, $phucvu) {
    $data = [];
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $id = $row['id'];
            
            // 1. Lấy chi tiết món ăn
            $items = [];
            $rsItems = $phucvu->get_chi_tiet_mon_cho_phuc_vu($id); 
            if($rsItems){
                while($r = $rsItems->fetch_assoc()){
                    $items[] = [
                        'mon' => htmlspecialchars($r['name_mon']),
                        'sl'  => $r['soluong']
                    ];
                }
            }

            // 2. Xử lý Tên Bàn từ cột 'noidung' (Regex)
            $tenban = trim($row['tenKH']);
            $tenphong = $row['phong'] ?? 'Sảnh chung';
            $noidung = $row['noidung'] ?? '';
            
            $ban_duoc_trich = '';
            if (!empty($noidung) && preg_match('/Bàn: ([A-Z0-9-]+)/', $noidung, $matches)) {
                 $ban_duoc_trich = trim($matches[1]);
            }

            if (!empty($ban_duoc_trich)) {
                $tenban = $ban_duoc_trich;
            } else if ($tenban == '') {
                $tenban = 'Khách lẻ';
            }
            
            $data[] = [
                'id'        => $id,
                'tenban'    => $tenban, 
                'phong'     => $tenphong, 
                'tg'        => $row['tg'], 
                'dates'     => $row['dates'], 
                'updated_at'=> $row['updated_at'] ?? '', // Thêm giờ hoàn thành
                'ghichu'    => $row['ghichu'] ?? '',
                'items'     => $items
            ];
        }
    }
    return $data;
}

// ==== XỬ LÝ GET: LẤY DỮ LIỆU ====

// 1. Lấy danh sách CHỜ GIAO
$rsWaiting = $phucvu->get_don_can_phuc_vu(); 
$waitingOrders = processOrderList($rsWaiting, $phucvu);

// 2. Lấy danh sách LỊCH SỬ (Đã giao hôm nay)
$rsHistory = $phucvu->get_don_da_phuc_vu();
$historyOrders = processOrderList($rsHistory, $phucvu);

// Xóa bộ đệm và trả về JSON
ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'count'   => count($waitingOrders), // Số lượng đơn chờ (để hiện badge đỏ)
    'orders'  => $waitingOrders,        // Danh sách đơn chờ
    'history' => $historyOrders         // Danh sách lịch sử
]);
exit;