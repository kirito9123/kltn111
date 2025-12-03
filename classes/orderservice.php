<?php
// classes/OrderService.php
$filepath = realpath(dirname(__FILE__));
require_once $filepath . '/../lib/database.php';

class orderservice {
    /** @var Database */
    private $db;

    public function __construct($db = null){
        $this->db = $db ?: new Database();
        if (isset($this->db->link) && $this->db->link instanceof mysqli) {
            @$this->db->link->set_charset('utf8mb4');
        }
    }

    /* ---------------------------------------------------------
     * 1) Đọc & tính tiền từ form order.php
     *    - $post: dữ liệu POST
     *    - Trả về: ['items'=>[...], 'total'=>float]
     *    items: id_mon, qty, price, amount
     * --------------------------------------------------------- */
    public function parseOrderFromPost(array $post): array {
        $chon  = $post['chonmon'] ?? [];          // checkbox: chonmon[id_mon] = "on"
        $slMap = $post['soluong'] ?? [];          // number:   soluong[id_mon] = n

        $items = [];
        $total = 0;

        foreach ($chon as $id_mon => $on) {
            $id  = (int)$id_mon;
            $qty = max(1, (int)($slMap[$id] ?? 1));

            // Server-authoritative price
            $row = $this->db->select("SELECT gia_mon FROM monan WHERE id_mon = {$id} AND xoa = 0 LIMIT 1");
            if (!$row || !$row->num_rows) { continue; }
            $r = $row->fetch_assoc();
            $price = (float)$r['gia_mon'];

            $amount = $price * $qty;
            $items[] = ['id_mon'=>$id, 'qty'=>$qty, 'price'=>$price, 'amount'=>$amount];
            $total  += $amount;
        }
        return ['items'=>$items, 'total'=>$total];
    }

    /* ---------------------------------------------------------
     * 2) Tạo hợp đồng + 3) Ghi chi tiết + cập nhật bàn (transaction)
     *
     * $order = [
     *   // có thể bỏ trống để dùng mặc định
     *   'tenKH' => '', 'so_user' => '',
     *   'dates' => 'YYYY-MM-DD', 'tg' => 'HH:MM',
     *   'note'  => '',
     *   'ban_ids' => [1,2,3],                    // bắt buộc
     *   'ban_labels' => ['P05-B03','P05-B04'],   // tùy chọn để in ra noidung
     *   'items' => [ ['id_mon'=>..,'qty'=>..,'price'=>..,'amount'=>..], ... ],
     *   'meta'  => ['loaiphong'=>'','phong'=>'','vitri_id'=>null]
     * ]
     * Trả: ['ok'=>bool, 'id'=>hopdong_id, 'msg'=>?]
     * --------------------------------------------------------- */
    public function createContractWithDetails(array $order): array {
        $mysqli = $this->db->link;
        if (!($mysqli instanceof mysqli)) {
            return ['ok'=>false, 'msg'=>'Không kết nối được CSDL.'];
        }

        // ==== Bàn & món bắt buộc ====
        $ban_ids = array_values(array_unique(array_filter(array_map('intval', $order['ban_ids'] ?? []))));
        $items   = $order['items'] ?? [];
        if (empty($ban_ids) || empty($items)) {
            return ['ok'=>false, 'msg'=>'Thiếu bàn hoặc chưa chọn món.'];
        }

        // ==== Tính tổng tiền (không phải thanh toán) ====
        $total = 0.0;
        foreach ($items as $it) { $total += (float)$it['amount']; }
        $total = round($total, 2);

        // ==== Mặc định theo yêu cầu ====
        $id_user = 0;
        $tenKH   = $mysqli->real_escape_string(trim($order['tenKH'] ?? 'Khách ăn tại quán'));
        $dates   = $mysqli->real_escape_string(trim($order['dates'] ?? date('Y-m-d'))); // 2025-10-18
        $tg      = $mysqli->real_escape_string(trim($order['tg']    ?? date('H:i')));   // 19:00
        $so_user = $mysqli->real_escape_string(trim($order['so_user'] ?? ''));
        $note    = trim($order['note'] ?? '');

        // payment_type mặc định
        $payment_type = $mysqli->real_escape_string($order['payment_type'] ?? 'tại quán');

        $status = (int)($order['status'] ?? 0);

        // Nhãn bàn hiển thị (vd P05-B03). Fallback: ID bàn
        $ban_labels = $order['ban_labels'] ?? null;
        $ban_text = (is_array($ban_labels) && !empty($ban_labels))
            ? implode(',', array_map('trim', $ban_labels))
            : implode(',', $ban_ids);

        // Lưu danh sách ID bàn để tra cứu
        $so_ban_ids = implode(',', $ban_ids);
        $so_ban_sql = $mysqli->real_escape_string($so_ban_ids);

        // ==== Lấy loaiphong & phong từ DB dựa trên các bàn đã chọn ====
        // Kết quả: chuỗi (có thể nhiều giá trị -> nối bằng ", ")
        $lp_label = '';
        $phong_label = '';
        {
            $idlist = implode(',', array_map('intval', $ban_ids));
            $q = "
                SELECT DISTINCT COALESCE(lp.tenloaiphong,'') AS tenloaiphong,
                                COALESCE(p.tenphong,'')      AS tenphong
                FROM ban b
                LEFT JOIN phong p       ON p.id_phong = b.id_phong
                LEFT JOIN loaiphong lp  ON lp.maloaiphong = p.maloaiphong
                WHERE b.id_ban IN ({$idlist})
            ";
            $res = $mysqli->query($q);
            if ($res) {
                $lp_arr = []; $p_arr = [];
                while ($row = $res->fetch_assoc()) {
                    $lp_arr[] = trim((string)$row['tenloaiphong']);
                    $p_arr[]  = trim((string)$row['tenphong']);
                }
                $lp_arr = array_values(array_filter(array_unique($lp_arr)));
                $p_arr  = array_values(array_filter(array_unique($p_arr)));
                $lp_label    = implode(', ', $lp_arr);
                $phong_label = implode(', ', $p_arr);
            }
        }
        // Bảo đảm không null (cột hopdong.loaiphong NOT NULL)
        $lp_sql    = $mysqli->real_escape_string($lp_label ?? '');
        $phong_sql = $mysqli->real_escape_string($phong_label ?? '');

        // Nội dung hiển thị
        $noidung = "Đặt bàn ngày {$dates} lúc {$tg}"
                . ($lp_sql    !== '' ? " - Loại phòng: {$lp_sql}" : '')
                . ($phong_sql !== '' ? " - Phòng: {$phong_sql}"   : '')
                . " - Bàn: {$ban_text}";
        if ($note !== '') { $noidung .= " - {$note}"; }
        $noidung_sql = $mysqli->real_escape_string($noidung);

        // Nếu bạn có meta.vitri_id thì lấy; nếu bảng hopdong KHÔNG có cột vitri_id, hãy bỏ ở phần INSERT bên dưới
        $vitri_id = (int)($order['meta']['vitri_id'] ?? 0);

        // ==== Transaction ====
        $mysqli->begin_transaction();
        try {
            // 0) KHÓA & KIỂM TRA BÀN
            $idlist = implode(',', array_map('intval', $ban_ids));
            $lockRs = $mysqli->query("SELECT id_ban, trangthai FROM ban WHERE id_ban IN ({$idlist}) FOR UPDATE");
            if (!$lockRs) { throw new Exception('Không thể khóa bàn: ' . $mysqli->error); }
            $busy = [];
            while ($row = $lockRs->fetch_assoc()) {
                if ((int)$row['trangthai'] === 1) $busy[] = (int)$row['id_ban'];
            }
            if (!empty($busy)) {
                throw new Exception('Bàn đã bận: ' . implode(',', $busy));
            }

            // 1) INSERT hopdong
            // Chèn THÊM 2 cột: loaiphong, phong  (vì bảng của bạn đang yêu cầu NOT NULL)
            $sql = "
                INSERT INTO hopdong (
                    id_user, tenKH, dates, tg, noidung, so_user,
                    tinhtrang, so_ban, created_at, updated_at,
                    so_tien, thanhtien, vitri_id, payment_type,
                    loaiphong, phong, status
                ) VALUES (
                    {$id_user},
                    '{$tenKH}', '{$dates}', '{$tg}', '{$noidung_sql}', '{$so_user}',
                    0, '{$so_ban_sql}', NOW(), NOW(),
                    0, {$total}, " . ($vitri_id ?: "NULL") . ", '{$payment_type}',
                    '{$lp_sql}', '{$phong_sql}', {$status}
                )
            ";
            if (!$mysqli->query($sql)) {
                throw new Exception('Không thể tạo hợp đồng: ' . $mysqli->error);
            }
            $hopdong_id = (int)$mysqli->insert_id;

            // 2) INSERT hopdong_chitiet
            $stmt = $mysqli->prepare("
                INSERT INTO hopdong_chitiet (hopdong_id, monan_id, soluong, gia, thanhtien)
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt) throw new Exception('Lỗi prepare chi tiết: ' . $mysqli->error);
            foreach ($items as $it) {
                $mon   = (int)$it['id_mon'];
                $qty   = max(1, (int)$it['qty']);
                $price = round((float)$it['price'], 2);
                $amt   = round((float)$it['amount'], 2);
                $stmt->bind_param('iiidd', $hopdong_id, $mon, $qty, $price, $amt);
                if (!$stmt->execute()) throw new Exception('Lỗi ghi chi tiết: ' . $stmt->error);
            }
            $stmt->close();

            // 3) UPDATE trạng thái bàn -> bận (1) (+ tuỳ chọn gắn hopdong_id lên bảng ban)
            // if (!$mysqli->query("UPDATE ban SET trangthai = 1, hopdong_id = {$hopdong_id} WHERE id_ban IN ({$idlist})")) {
            //     // nếu không có cột hopdong_id ở bảng ban, dùng câu này: UPDATE ban SET trangthai = 1 WHERE ...
            //     // throw new Exception('Không thể cập nhật trạng thái bàn: ' . $mysqli->error);
            // }

            $mysqli->commit();

            return [
                'ok'  => true,
                'id'  => $hopdong_id,
                'msg' => 'Đã import hợp đồng và chi tiết (chưa thanh toán)'
            ];

        } catch (Throwable $e) {
            $mysqli->rollback();
            return ['ok'=>false, 'msg'=>'Ghi đơn thất bại: ' . $e->getMessage()];
        }
    }

}
