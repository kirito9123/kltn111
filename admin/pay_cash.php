<?php
@ini_set('display_errors','0');
@error_reporting(E_ERROR|E_PARSE);
while (ob_get_level() > 0) { @ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/database.php';
Session::init();

try {
    $hopdong_id  = isset($_POST['hopdong_id']) ? (int)$_POST['hopdong_id'] : 0;
    $amount_due  = (float)($_POST['amount_due'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);

    if ($hopdong_id <= 0 || $amount_due <= 0) throw new Exception('Thiếu tham số.');
    if ($amount_paid < $amount_due)           throw new Exception('Khách đưa chưa đủ tiền.');

    $db   = new Database();
    $conn = $db->link;
    if (!$conn) throw new Exception('Không kết nối được CSDL.');

    // ===== Helpers =====
    $parseBanIds = function(string $csv): array {
        if ($csv === '') return [];
        $parts = preg_split('/[,\s;]+/', $csv);
        $ids = [];
        foreach ($parts as $p) {
            $v = (int)trim($p);
            if ($v > 0) $ids[] = $v;
        }
        // unique & reindex
        return array_values(array_unique($ids));
    };

    // ===== Bắt đầu transaction
    if (method_exists($conn, 'begin_transaction')) { $conn->begin_transaction(); }

    // Lấy trạng thái hiện tại + danh sách bàn (so_ban)
    $stmt = $conn->prepare("SELECT payment_status, COALESCE(so_ban,'') AS so_ban FROM hopdong WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $hopdong_id);
    $stmt->execute();
    $rs = $stmt->get_result();
    if (!$rs || !$rs->num_rows) throw new Exception('Không tìm thấy hợp đồng.');
    $row = $rs->fetch_assoc();
    $curStatus = strtolower(trim((string)($row['payment_status'] ?? '')));
    $soBanRaw  = (string)($row['so_ban'] ?? '');
    $stmt->close();

    $banIds = $parseBanIds($soBanRaw); // -> [id_ban,...]

    // Nếu hợp đồng chưa completed thì cập nhật completed
    if ($curStatus !== 'completed') {
        $stmt = $conn->prepare("
            UPDATE hopdong
               SET payment_status = 'completed',
                   payment_method = 'cash',
                   payment_type   = 'full',
                   so_tien        = ?,
                   updated_at     = NOW()
             WHERE id = ?
             LIMIT 1
        ");
        $stmt->bind_param("di", $amount_paid, $hopdong_id);
        $stmt->execute();
        if ($stmt->affected_rows < 0) throw new Exception('Không cập nhật được hợp đồng.');
        $stmt->close();
    }

    // ===== TRẢ BÀN (dựa trên hopdong.so_ban ↔ ban.id_ban)
    $banUpdatedRows = 0;
    $roomsTouched   = [];

    if (!empty($banIds)) {
        $idList = implode(',', array_map('intval', $banIds)); // an toàn vì đã cast int

        // Lấy danh sách phòng chứa các bàn này (để lát nữa xét trả phòng)
        $q = $conn->query("SELECT DISTINCT id_phong FROM ban WHERE id_ban IN ($idList)");
        if ($q) { while ($r = $q->fetch_assoc()) { $roomsTouched[] = (int)$r['id_phong']; } }

        // Trả bàn: set trangthai=0, clear hopdong_id (varchar NOT NULL)
        $conn->query("UPDATE ban SET trangthai = 0, hopdong_id = '' WHERE id_ban IN ($idList)");
        $banUpdatedRows = $conn->affected_rows;

        // ===== TRẢ PHÒNG: nếu phòng không còn bàn nào bận thì set phong.trangthai=0
        $roomsTouched = array_values(array_unique($roomsTouched));
        foreach ($roomsTouched as $pid) {
            $cntRes = $conn->query("SELECT COUNT(*) AS busy FROM ban WHERE id_phong = ".(int)$pid." AND trangthai <> 0");
            $busy = 0;
            if ($cntRes) { $busy = (int)($cntRes->fetch_assoc()['busy'] ?? 0); }
            if ($busy === 0) {
                $conn->query("UPDATE phong SET trangthai = 0 WHERE id_phong = ".(int)$pid." LIMIT 1");
            }
        }
    }
    // Nếu so_ban trống -> không làm gì thêm, nhưng không coi là lỗi

    // ===== Log thanh toán nếu có bảng payment_logs (tùy chọn)
    $hasLogs = false;
    $chk = $conn->query("SELECT 1 FROM information_schema.TABLES
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_logs' LIMIT 1");
    if ($chk && $chk->num_rows) $hasLogs = true;

    if ($hasLogs) {
        $cashier = (int)(Session::get('adminId') ?? 0);
        $due     = $amount_due;
        $paid    = $amount_paid;
        $change  = max(0, $amount_paid - $amount_due);
        // không throw nếu insert thất bại
        @$conn->query(sprintf(
            "INSERT INTO payment_logs(hopdong_id,cashier_id,payment_method,payment_type,amount_due,amount_paid,change_amount,created_at)
             VALUES(%d,%d,'cash','full',%.2f,%.2f,%.2f,NOW())",
            $hopdong_id, $cashier, $due, $paid, $change
        ));
    }

    if (method_exists($conn, 'commit')) { $conn->commit(); }

    echo json_encode([
        'ok' => true,
        // Bật chẩn đoán khi cần:
        // 'diag' => ['so_ban_raw'=>$soBanRaw, 'ban_ids'=>$banIds, 'ban_updated_rows'=>$banUpdatedRows, 'rooms_touched'=>$roomsTouched]
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) { $conn->rollback(); }
    echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
}
