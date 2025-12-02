<?php
// classes/ban.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');

class BanService {
    /** @var Database */
    private $db;

    public function __construct($db = null) {
        // Cho phép truyền sẵn Database từ ngoài vào để tái sử dụng connection
        $this->db = $db ?: new Database();
        if (isset($this->db->link) && $this->db->link instanceof mysqli) {
            @($this->db->link->set_charset('utf8mb4'));
        }
    }

    /* ===========================
     * Helpers trạng thái
     * =========================== */
    public static function statusClass(int $st): string {
        if ($st === 0) return 'free';
        if ($st === 2) return 'hold';
        if ($st === 1) return 'busy';
        return 'unknown';
    }
    public static function statusText(string $cls): string {
        switch ($cls) {
            case 'free': return 'Trống';
            case 'busy': return 'Đã đặt';
            case 'hold': return 'Giữ chỗ';
            default: return 'Không rõ';
        }
    }

    /* ===========================
     * Đọc danh sách bàn theo phòng & loại bàn
     * =========================== */
    public function getBanByPhongLoaiBan(int $id_phong, int $id_loaiban) {
        $id_phong   = (int)$id_phong;
        $id_loaiban = (int)$id_loaiban;
        $sql = "
            SELECT b.id_ban, b.tenban, b.trangthai
            FROM ban b
            WHERE b.id_phong = {$id_phong} AND b.id_loaiban = {$id_loaiban}
            ORDER BY b.tenban ASC
        ";
        return $this->db->select($sql);
    }

    /* ===========================
     * Cập nhật trạng thái hàng loạt (book/free)
     * =========================== */
    public function bulkUpdateStatus(array $ids, string $action): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return ['ok' => false, 'msg' => 'Bạn chưa chọn bàn nào.'];
        }
        $new = ($action === 'book') ? 1 : (($action === 'free') ? 0 : 2);
        $idlist = implode(',', $ids);

        $ok = $this->db->update("UPDATE ban SET trangthai = {$new} WHERE id_ban IN ({$idlist})");
        if ($ok === false) {
            return ['ok' => false, 'msg' => 'Có lỗi khi cập nhật trạng thái bàn.'];
        }
        $text = ($new===2 ? 'đã giữ chỗ' : ($new===1 ? 'đã đánh dấu đặt' : 'đã trả bàn'));
        return ['ok' => true, 'msg' => "Cập nhật ".count($ids)." bàn {$text}."];
    }

    /* ===========================
     * Giữ chỗ + tạo hợp đồng (transaction)
     * - $ids: danh sách id_ban
     * - $form: dữ liệu từ form giữ chỗ (hold_name/phone/email/date/time/note, payment_method)
     * - $context: thông tin filter để ghi vào noidung (ten_loaiphong/ten_phong)
     * =========================== */
    public function holdAndCreateContract(array $ids, array $form, array $context): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) return ['ok'=>false, 'msg'=>'Bạn chưa chọn bàn nào.'];

        $mysqli = $this->db->link;
        if (!($mysqli instanceof mysqli)) return ['ok'=>false, 'msg'=>'Không kết nối được CSDL.'];

        // Chuẩn bị chuỗi & biến
        $hold_name  = trim($form['hold_name']  ?? '');
        $hold_phone = trim($form['hold_phone'] ?? '');
        $hold_email = trim($form['hold_email'] ?? '');
        $hold_date  = trim($form['hold_date']  ?? '');
        $hold_time  = trim($form['hold_time']  ?? '');
        $hold_note  = trim($form['hold_note']  ?? '');
        $pay_method = (isset($form['payment_method']) && $form['payment_method']==='qr') ? 'qr' : 'cash';

        if (!$hold_name || !$hold_phone || !$hold_date || !$hold_time) {
            return ['ok'=>false, 'msg'=>'Thiếu thông tin: Họ tên, SĐT, Ngày đến, Giờ đến.'];
        }

        $ten_loaiphong = $context['ten_loaiphong'] ?? '';
        $ten_phong     = $context['ten_phong']     ?? '';

        $idlist = implode(',', $ids);
        $mysqli->begin_transaction();
        try {
            // Khóa bản ghi bàn
            $lock = $mysqli->query("SELECT id_ban, tenban, trangthai FROM ban WHERE id_ban IN ({$idlist}) FOR UPDATE");
            if (!$lock) throw new Exception('Không thể khóa dữ liệu bàn.');

            $tenBanArr = []; $unavailable = [];
            while ($r = $lock->fetch_assoc()) {
                if ((int)$r['trangthai'] !== 0) $unavailable[] = $r['tenban'];
                $tenBanArr[] = $r['tenban'];
            }
            if ($unavailable) {
                throw new Exception('Các bàn không còn trống: '.implode(', ', $unavailable));
            }

            // Escape
            $tenKH   = $mysqli->real_escape_string($hold_name);
            $sdtKH   = $mysqli->real_escape_string($hold_phone);
            $emailKH = $mysqli->real_escape_string($hold_email);
            $noteKH  = $mysqli->real_escape_string($hold_note);
            $dates   = $mysqli->real_escape_string($hold_date);
            $tg      = $mysqli->real_escape_string($hold_time);

            $tables_str = implode(',', $ids);
            $tenban_str = implode(', ', $tenBanArr);

            $noidung     = "Giữ chỗ ngày {$hold_date} lúc {$hold_time} - Loại phòng: {$ten_loaiphong} - Phòng: {$ten_phong} - Bàn: {$tenban_str}";
            $noidung_sql = $mysqli->real_escape_string($noidung);
            $lp_ten_sql  = $mysqli->real_escape_string($ten_loaiphong);
            $p_ten_sql   = $mysqli->real_escape_string($ten_phong);
            $payment_sql = ($pay_method === 'qr') ? 'qr' : 'cash';

            // Tuỳ business: tiền cọc/thanhtien
            $id_user        = 0;
            $so_tien        = 0;
            $thanhtien      = 30000;
            $payment_status = ($pay_method === 'cash') ? 'completed' : 'pending';

            $ins = $mysqli->query("
                INSERT INTO hopdong (
                    id_user, tenKH, dates, tg, noidung, so_user,
                    tinhtrang, payment_status, payment_method,
                    so_ban, loaiphong, phong, so_tien, thanhtien, created_at
                ) VALUES (
                    {$id_user}, '{$tenKH}', '{$dates}', '{$tg}', '{$noidung_sql}', '{$sdtKH}',
                    0, '{$payment_status}', '{$payment_sql}',
                    '{$tables_str}', '{$lp_ten_sql}', '{$p_ten_sql}', {$so_tien}, {$thanhtien}, NOW()
                )
            ");
            if (!$ins) throw new Exception('Không thể lưu đơn giữ chỗ: '.$mysqli->error);
            $booking_id = (int)$mysqli->insert_id;

            // Cập nhật trạng thái bàn -> hold (2)
            $upd = $mysqli->query("UPDATE ban SET trangthai = 2 WHERE id_ban IN ({$idlist})");
            if (!$upd) throw new Exception('Không thể cập nhật trạng thái bàn: '.$mysqli->error);

            $mysqli->commit();

            // Trả về chỉ dẫn để controller quyết định redirect
            if ($pay_method === 'qr') {
                return [
                    'ok' => true,
                    'msg' => 'Đã giữ chỗ, chuyển sang VNPAY.',
                    'booking_id' => $booking_id,
                    'redirect' => "vnpay_cre.php?mode=datban&booking_id={$booking_id}"
                ];
            } else {
                return [
                    'ok' => true,
                    'msg' => 'Đã giữ chỗ thành công (tiền mặt).',
                    'booking_id' => $booking_id
                ];
            }
        } catch (Throwable $e) {
            $mysqli->rollback();
            return ['ok'=>false, 'msg'=>'Lỗi giữ chỗ: '.$e->getMessage()];
        }
    }

    /* ===========================
     * Bộ xử lý tổng cho POST (để trang gọi 1 hàm duy nhất)
     * $post      : dữ liệu POST
     * $qkeep     : mảng filter để ghép query string khi redirect
     * $contextCb : callback cung cấp ten_loaiphong / ten_phong khi cần
     * =========================== */
    public function handlePost(array $post, array $qkeep, callable $contextCb): array {
        $ids  = isset($post['tables']) ? (array)$post['tables'] : [];
        $act  = $post['bulk_action'] ?? 'hold';

        $qs   = http_build_query([
            'maloaiphong' => (int)($qkeep['maloaiphong'] ?? 0),
            'id_phong'    => (int)($qkeep['id_phong'] ?? 0),
            'id_loaiban'  => (int)($qkeep['id_loaiban'] ?? 0),
        ]);

        if ($act === 'hold' && isset($post['hold_name'])) {
            // Lấy context hiển thị từ callback truyền vào
            $context = $contextCb();
            $res = $this->holdAndCreateContract($ids, $post, $context);
            if (!$res['ok']) {
                return ['redirect'=>"danhsachdatban.php?{$qs}&msg=".urlencode($res['msg'])];
            }
            if (!empty($res['redirect'])) {
                return ['redirect'=>$res['redirect']]; // sang VNPAY
            }
            // tiền mặt: quay về danh sách
            return ['redirect'=>"danhsachdatban.php?{$qs}&msg=".urlencode($res['msg'])];
        }

        // Nhánh cũ: book/free
        // ========= NHÁNH MỚI: ORDER =========
        if ($action === 'order') {
            $ids = array_filter(array_map('intval', $post['tables'] ?? []));
            $ids = array_values(array_unique($ids));

            if (empty($ids)) {
                return ['redirect' => 'danhsachdatban.php?msg=ChuaChonBan'];
            }
            return ['redirect' => 'order.php?ban_ids='.implode(',', $ids)];
        }


        $res = $this->bulkUpdateStatus($ids, $act);
        $msg = $res['msg'] ?? '';
        return ['redirect'=>"danhsachdatban.php?{$qs}&msg=".urlencode($msg)];
    }
}
