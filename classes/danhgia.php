<?php
// classes/danhgia.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');

class DanhGia {
    /** @var Database */
    private $db;
    private $uploadDir = 'images/danhgia'; // thư mục chứa ảnh đánh giá

    public function __construct() {
        $this->db = new Database();
        if (isset($this->db->link) && $this->db->link instanceof mysqli) {
            @$this->db->link->set_charset('utf8mb4');
        }
    }

    /** điều kiện mặc định cho bản ghi chưa bị xóa mềm */
    private function _notDeletedCondition(string $alias = ''): string {
        $col = $alias ? ($alias . '.xoa') : 'xoa';
        return "($col IS NULL OR $col = 0)";
    }

    /** Lấy hợp đồng thuộc về user (để hiển thị & xác thực quyền) */
    public function getContractForUser(int $id_hopdong, int $id_khachhang) {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;

        $sql = "
            SELECT h.id, h.noidung, h.dates, h.payment_status,
                   kh.id AS id_kh, kh.ten AS tenKH, kh.sodienthoai
            FROM hopdong h
            JOIN khach_hang kh ON kh.id = h.id_user
            WHERE h.id = {$id_hopdong} AND kh.id = {$id_khachhang}
            LIMIT 1
        ";
        $rs = $this->db->select($sql);
        return ($rs && $rs->num_rows > 0) ? $rs->fetch_assoc() : null;
    }

    /** Kiểm tra user đã có review còn hiệu lực (chưa xóa mềm) cho HĐ này chưa */
    public function hasReviewed(int $id_hopdong, int $id_khachhang): bool {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;
        $cond = $this->_notDeletedCondition('dg');
        $rs = $this->db->select("
            SELECT id_danhgia FROM danhgia dg
            WHERE dg.id_hopdong = {$id_hopdong}
              AND dg.id_khachhang = {$id_khachhang}
              AND $cond
            LIMIT 1
        ");
        return ($rs && $rs->num_rows > 0);
    }

    /**
     * Tạo đánh giá (form dùng name="rating", "comment", "photos[]")
     * Chỉ lưu tên file ảnh đầu tiên (VD: rv_1_20_abcd.jpg)
     * Mặc định xoa = 0 (chưa xóa mềm)
     */
    public function createReview(int $id_hopdong, int $id_khachhang, int $rating, string $comment, ?array $photos = null): array {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;
        $rating       = (int)$rating;

        if ($rating < 1 || $rating > 5) {
            return ['ok'=>false, 'msg'=>'Vui lòng chọn số sao từ 1–5.'];
        }
        if ($this->hasReviewed($id_hopdong, $id_khachhang)) {
            return ['ok'=>false, 'msg'=>'Bạn đã gửi đánh giá cho hợp đồng này rồi.'];
        }

        // Upload nhiều ảnh nhưng chỉ lưu tên ảnh đầu tiên
        $fileName = '';
        if ($photos && !empty($photos['name'])) {
            $fileName = $this->uploadFirstImageAndReturnName($photos, $id_khachhang, $id_hopdong);
            if ($fileName === false) {
                return ['ok'=>false, 'msg'=>'Ảnh không hợp lệ hoặc tải lên thất bại.'];
            }
        }

        $binh_luan = mysqli_real_escape_string($this->db->link, $comment);
        $hinhanh   = mysqli_real_escape_string($this->db->link, (string)$fileName);

        $sql = "
            INSERT INTO danhgia (id_hopdong, id_khachhang, binh_luan, hinhanh, so_sao, xoa)
            VALUES ({$id_hopdong}, {$id_khachhang}, '{$binh_luan}', '{$hinhanh}', {$rating}, 0)
        ";
        $ok = $this->db->insert($sql);
        return $ok
            ? ['ok'=>true, 'msg'=>'Cảm ơn bạn! Đánh giá đã được ghi nhận.']
            : ['ok'=>false, 'msg'=>'Không thể lưu đánh giá, vui lòng thử lại.'];
    }

    /** Danh sách đánh giá theo user (chỉ lấy bản ghi chưa xóa mềm) */
    public function listByUser(int $id_khachhang) {
        $id_khachhang = (int)$id_khachhang;
        $cond = $this->_notDeletedCondition('dg');
        $sql = "
            SELECT dg.*, h.noidung, h.dates
            FROM danhgia dg
            JOIN hopdong h ON h.id = dg.id_hopdong
            WHERE dg.id_khachhang = {$id_khachhang}
              AND $cond
            ORDER BY dg.id_danhgia DESC
        ";
        return $this->db->select($sql);
    }

    /** Danh sách đánh giá theo hợp đồng (chỉ lấy bản ghi chưa xóa mềm) */
    public function listByContract(int $id_hopdong) {
        $id_hopdong = (int)$id_hopdong;
        $cond = $this->_notDeletedCondition('dg');
        $sql = "
            SELECT dg.*, kh.ten AS tenKH, kh.sodienthoai
            FROM danhgia dg
            JOIN khach_hang kh ON kh.id = dg.id_khachhang
            WHERE dg.id_hopdong = {$id_hopdong}
              AND $cond
            ORDER BY dg.id_danhgia DESC
        ";
        return $this->db->select($sql);
    }

    /** Lấy review của user cho HĐ (chỉ bản ghi chưa xóa mềm) */
    public function getReview(int $id_hopdong, int $id_khachhang) {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;
        $cond = $this->_notDeletedCondition('dg');
        $sql = "
            SELECT 
                dg.so_sao   AS rating,
                dg.binh_luan AS comment,
                dg.hinhanh  AS photos
            FROM danhgia dg
            WHERE dg.id_hopdong = {$id_hopdong}
              AND dg.id_khachhang = {$id_khachhang}
              AND $cond
            LIMIT 1
        ";
        $rs = $this->db->select($sql);
        return ($rs && $rs->num_rows > 0) ? $rs->fetch_assoc() : null;
    }

    /** Cập nhật review (chỉ khi review đang còn hiệu lực, không bị xóa mềm) */
    public function updateReview(int $id_hopdong, int $id_khachhang, int $rating, string $comment, ?array $photos = null): array {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;
        $rating       = (int)$rating;

        if ($rating < 1 || $rating > 5) {
            return ['ok'=>false, 'msg'=>'Vui lòng chọn số sao từ 1–5.'];
        }

        // Lấy đánh giá cũ (chỉ bản ghi chưa xóa mềm)
        $old = $this->getReview($id_hopdong, $id_khachhang);
        if (!$old) {
            return ['ok'=>false, 'msg'=>'Không tìm thấy đánh giá để cập nhật (có thể đã xóa).'];
        }

        $binh_luan = mysqli_real_escape_string($this->db->link, $comment);

        // 1) Nếu KHÔNG có ảnh mới -> KHÔNG cập nhật cột hinhanh (giữ nguyên DB)
        if (!$this->hasNewPhoto($photos)) {
            $sql = "
                UPDATE danhgia
                SET so_sao = {$rating}, binh_luan = '{$binh_luan}'
                WHERE id_hopdong = {$id_hopdong}
                  AND id_khachhang = {$id_khachhang}
                  AND " . $this->_notDeletedCondition() . "
                LIMIT 1
            ";
            $ok = $this->db->update($sql);
            return $ok ? ['ok'=>true, 'msg'=>'Cập nhật đánh giá thành công.']
                       : ['ok'=>false, 'msg'=>'Không thể cập nhật đánh giá.'];
        }

        // 2) Có ảnh mới -> upload và cập nhật hinhanh
        $fileNameNew = $this->uploadFirstImageAndReturnName($photos, $id_khachhang, $id_hopdong);
        if ($fileNameNew === false) {
            return ['ok'=>false, 'msg'=>'Ảnh không hợp lệ hoặc tải lên thất bại.'];
        }
        // Nếu upload không thành công (trả ''), ta vẫn giữ ảnh cũ (KHÔNG set rỗng)
        if ($fileNameNew === '') {
            $sql = "
                UPDATE danhgia
                SET so_sao = {$rating}, binh_luan = '{$binh_luan}'
                WHERE id_hopdong = {$id_hopdong}
                  AND id_khachhang = {$id_khachhang}
                  AND " . $this->_notDeletedCondition() . "
                LIMIT 1
            ";
            $ok = $this->db->update($sql);
            return $ok ? ['ok'=>true, 'msg'=>'Cập nhật đánh giá thành công.']
                       : ['ok'=>false, 'msg'=>'Không thể cập nhật đánh giá.'];
        }

        $hinhanh = mysqli_real_escape_string($this->db->link, $fileNameNew);
        $sql = "
            UPDATE danhgia
            SET so_sao = {$rating}, binh_luan = '{$binh_luan}', hinhanh = '{$hinhanh}'
            WHERE id_hopdong = {$id_hopdong}
              AND id_khachhang = {$id_khachhang}
              AND " . $this->_notDeletedCondition() . "
            LIMIT 1
        ";
        $ok = $this->db->update($sql);
        return $ok ? ['ok'=>true, 'msg'=>'Cập nhật đánh giá thành công.']
                   : ['ok'=>false, 'msg'=>'Không thể cập nhật đánh giá.'];
    }

    /** Xóa mềm review: đặt xoa=1 (chỉ tác động nếu đang chưa xóa) */
    public function softDeleteReview(int $id_hopdong, int $id_khachhang): array {
        $id_hopdong   = (int)$id_hopdong;
        $id_khachhang = (int)$id_khachhang;

        $sql = "
            UPDATE danhgia
            SET xoa = 1
            WHERE id_hopdong = {$id_hopdong}
              AND id_khachhang = {$id_khachhang}
              AND " . $this->_notDeletedCondition() . "
            LIMIT 1
        ";
        $ok = $this->db->update($sql);
        return $ok ? ['ok'=>true, 'msg'=>'Đã xóa đánh giá (mềm).']
                   : ['ok'=>false, 'msg'=>'Không thể xóa (có thể đã xóa trước đó).'];
    }

    // ================= Helpers =================

    /** Upload ảnh đầu tiên, trả về tên file (vd: rv_1_10_abc123.jpg) hoặc '' nếu không có ảnh, false nếu lỗi */
    private function uploadFirstImageAndReturnName(array $photos, int $id_khachhang, int $id_hopdong) {
        // Normalize mảng photos[]
        $names = is_array($photos['name'] ?? null) ? $photos['name'] : [$photos['name'] ?? ''];
        $tmps  = is_array($photos['tmp_name'] ?? null) ? $photos['tmp_name'] : [$photos['tmp_name'] ?? ''];
        $errs  = is_array($photos['error'] ?? null) ? $photos['error'] : [$photos['error'] ?? UPLOAD_ERR_NO_FILE];
        $sizes = is_array($photos['size'] ?? null) ? $photos['size'] : [$photos['size'] ?? 0];

        // Thư mục /images/danhgia
        $rootDir = realpath(__DIR__ . '/..'); // ổn định hơn so với $GLOBALS['filepath']
        $absDir  = $rootDir . DIRECTORY_SEPARATOR . $this->uploadDir;
        if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }

        $allowMime = ['image/jpeg','image/png','image/webp','image/gif'];

        for ($i = 0; $i < count($names); $i++) {
            if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (!is_uploaded_file($tmps[$i] ?? '')) continue;
            if (($sizes[$i] ?? 0) > 5 * 1024 * 1024) continue; // >5MB bỏ qua

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmps[$i]);
            finfo_close($finfo);
            if (!in_array($mime, $allowMime, true)) continue;

            $ext = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
            if (!$ext) {
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'gif'
                };
            }

            $safeName = 'rv_' . $id_khachhang . '_' . $id_hopdong . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $absPath  = $absDir . DIRECTORY_SEPARATOR . $safeName;

            if (@move_uploaded_file($tmps[$i], $absPath)) {
                return $safeName; // chỉ lưu tên file
            }
        }
        return ''; // không có ảnh hợp lệ nào
    }

    private function deleteImageIfExists(?string $name) {
        if (!$name) return;
        $abs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . $this->uploadDir . DIRECTORY_SEPARATOR . $name;
        if (is_file($abs)) @unlink($abs);
    }

    /** Có ảnh mới hợp lệ được chọn không? */
    private function hasNewPhoto(?array $photos): bool {
        if (!is_array($photos) || empty($photos)) return false;

        $names = is_array($photos['name'] ?? null) ? $photos['name'] : [$photos['name'] ?? ''];
        $tmps  = is_array($photos['tmp_name'] ?? null) ? $photos['tmp_name'] : [$photos['tmp_name'] ?? ''];
        $errs  = is_array($photos['error'] ?? null) ? $photos['error'] : [$photos['error'] ?? UPLOAD_ERR_NO_FILE];

        for ($i = 0; $i < count($names); $i++) {
            if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($tmps[$i] ?? '')) {
                return true;
            }
        }
        return false;
    }

    public function restoreReview(int $id_danhgia): array {
        $id_danhgia = (int)$id_danhgia;
        $sql = "
            UPDATE danhgia
            SET xoa = 0
            WHERE id_danhgia = {$id_danhgia}
            LIMIT 1
        ";
        $ok = $this->db->update($sql);
        return $ok
            ? ['ok' => true,  'msg' => 'Đã khôi phục đánh giá thành công.']
            : ['ok' => false, 'msg' => 'Không thể khôi phục đánh giá.'];
    }
}
