<?php
// classes/phong.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');

class phong
{
    private $db;
    public function __construct()
    {
        $this->db = new Database();
    }

    /* ================= LOẠI PHÒNG / LOẠI BÀN ================= */

    // Lấy tất cả loại phòng
    public function show_loaiphong()
    {
        $sql = "SELECT maloaiphong, tenloaiphong
                FROM loaiphong
                ORDER BY tenloaiphong ASC";
        return $this->db->select($sql);
    }

    // Lấy tất cả loại bàn
    public function show_loaiban()
    {
        $sql = "SELECT id_loaiban, tenloaiban
                FROM loaiban
                ORDER BY tenloaiban ASC";
        return $this->db->select($sql);
    }

    /* ================= PHÒNG ================= */

    // Tất cả phòng + tên loại phòng
    public function show_phong_all()
    {
        $sql = "SELECT p.id_phong, p.tenphong, p.maloaiphong, 
                       lp.tenloaiphong,
                       /* cột hình ảnh có thể không tồn tại ở DB của bạn, nên IFNULL để tránh lỗi */
                       IFNULL(p.hinhanh, '') AS hinhanh
                FROM phong p
                LEFT JOIN loaiphong lp ON p.maloaiphong = lp.maloaiphong
                ORDER BY p.maloaiphong ASC, p.id_phong ASC";
        return $this->db->select($sql);
    }

    // Phòng theo loại phòng
    public function show_phong_by_loai($maloaiphong)
    {
        $id = (int)$maloaiphong;
        $sql = "SELECT p.id_phong, p.tenphong, p.maloaiphong, 
                       IFNULL(p.hinhanh, '') AS hinhanh
                FROM phong p
                WHERE p.maloaiphong = {$id}
                ORDER BY p.id_phong ASC";
        return $this->db->select($sql);
    }

    // Lấy 1 phòng
    public function show_phong_by_id($id_phong)
    {
        $id = (int)$id_phong;
        $sql = "SELECT p.id_phong, p.tenphong, p.maloaiphong, 
                       IFNULL(p.hinhanh, '') AS hinhanh,
                       lp.tenloaiphong
                FROM phong p
                LEFT JOIN loaiphong lp ON lp.maloaiphong = p.maloaiphong
                WHERE p.id_phong = {$id} LIMIT 1";
        return $this->db->select($sql);
    }

    // (Tuỳ chọn) Đếm số bàn trong phòng
    public function count_ban_in_phong($id_phong)
    {
        $id = (int)$id_phong;
        $sql = "SELECT COUNT(*) AS cnt FROM ban WHERE id_phong = {$id}";
        $rs = $this->db->select($sql);
        if ($rs) {
            $r = $rs->fetch_assoc();
            return (int)$r['cnt'];
        }
        return 0;
    }

    /* ================= BÀN ================= */

    // Danh sách bàn theo phòng + loại bàn (kèm trạng thái)
    // Quy ước: 0: trống, 1: đã đặt, 2: giữ chỗ
    public function show_ban_by_phong_loaiban($id_phong, $id_loaiban)
    {
        $pid = (int)$id_phong;
        $lid = (int)$id_loaiban;
        $sql = "SELECT id_ban, tenban, id_phong, id_loaiban, 
                       IFNULL(trangthai, 0) AS trangthai
                FROM ban
                WHERE id_phong = {$pid} AND id_loaiban = {$lid}
                ORDER BY id_ban ASC";
        return $this->db->select($sql);
    }

    /* ================= KHÔNG DÙNG (XOÁ LỖI CŨ) ================= */
    // Hàm cũ dùng sai kết nối + sai bảng → bỏ/giữ lại theo ý bạn, mặc định trả mảng rỗng
    public function getTenPhong()
    {
        return [];
    }
}
