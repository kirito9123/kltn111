<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class nhanvienbep
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    // ============================================================
    // 1. LẤY DANH SÁCH ĐƠN HÀNG (Dùng cho màn hình chính)
    // ============================================================
    public function get_danh_sach_don($filter = 'cho_che_bien', $date = '')
    {
        $query = "SELECT * FROM hopdong WHERE 1=1 ";

        switch ($filter) {
            case 'cho_che_bien':
                $today = date('Y-m-d');
                $query .= " AND status = 0 AND dates = '$today' ";
                break;

            case 'hom_nay':
                $today = date('Y-m-d');
                $query .= " AND dates = '$today' ";
                break;

            case 'lich_su':
                if (!empty($date)) {
                    $date = mysqli_real_escape_string($this->db->link, $date);
                    $query .= " AND dates = '$date' ";
                }
                break;
                
            case 'tat_ca_chua_xong':
                $query .= " AND status = 0 ";
                break;
        }

        $query .= " ORDER BY dates ASC, tg ASC";

        return $this->db->select($query);
    }

    // ============================================================
    // 2. LẤY CHI TIẾT MÓN ĂN CỦA 1 ĐƠN (Kèm tên món)
    // ============================================================
    public function get_chi_tiet_don($id_hd)
    {
        $id_hd = mysqli_real_escape_string($this->db->link, $id_hd);
        
        // [SỬA LẠI] Thêm c.trangthai vào SELECT để biết món nào Bếp làm rồi
        $query = "
            SELECT 
                c.id AS id_chitiet,
                c.soluong,
                c.thanhtien,
                c.trangthai, 
                m.name_mon,
                m.images
            FROM hopdong_chitiet c
            JOIN monan m ON c.monan_id = m.id_mon
            WHERE c.hopdong_id = '$id_hd'
        ";
        
        return $this->db->select($query);
    }

    // ============================================================
    // 3. CẬP NHẬT TRẠNG THÁI ĐƠN (HOÀN THÀNH)
    // ============================================================
    public function hoan_thanh_don($id_hd)
    {
        $id_hd = mysqli_real_escape_string($this->db->link, $id_hd);
        
        // [LOGIC MỚI] Không kiểm tra status = 1 và return ngay nữa
        // Vì có thể đơn đã xong nhưng khách gọi thêm món mới (status lại về 0)
        
        // 1. Thực hiện trừ nguyên liệu kho (Chỉ trừ món chưa làm - trangthai=0)
        $this->tru_nguyen_lieu_theo_don($id_hd);

        // 2. [MỚI] Update các món đang chờ (0) thành đã xong (1)
        // Chỉ update những món chưa làm thôi, món làm rồi giữ nguyên
        $update_items = "UPDATE hopdong_chitiet SET trangthai = 1 WHERE hopdong_id = '$id_hd' AND trangthai = 0";
        $this->db->update($update_items);

        // 3. Cập nhật trạng thái Completed cho cả đơn
        $query = "UPDATE hopdong SET status = 1 WHERE id = '$id_hd'";
        return $this->db->update($query);
    }

    // --- HÀM PHỤ: TÍNH TOÁN VÀ TRỪ KHO ---
    private function tru_nguyen_lieu_theo_don($id_hd) {
        // [SỬA LẠI] Thêm điều kiện AND trangthai = 0 
        // Để không trừ lại kho những món cũ đã trừ trước đó
        $query_mon = "SELECT monan_id, soluong FROM hopdong_chitiet WHERE hopdong_id = '$id_hd' AND trangthai = 0";
        $ds_mon = $this->db->select($query_mon);

        if ($ds_mon) {
            while ($mon = $ds_mon->fetch_assoc()) {
                $id_mon = $mon['monan_id'];
                $sl_mon_khach_goi = $mon['soluong']; 

                // Lấy công thức (Giữ nguyên logic cũ)
                $query_ct = "
                    SELECT 
                        ct.id_nl,
                        ct.so_luong as sl_dinh_muc, 
                        dvt_ct.he_so as he_so_ct,   
                        dvt_kho.he_so as he_so_kho  
                    FROM congthuc_mon ct
                    JOIN don_vi_tinh dvt_ct ON ct.id_dvt = dvt_ct.id_dvt
                    JOIN nguyen_lieu nl ON ct.id_nl = nl.id_nl
                    JOIN don_vi_tinh dvt_kho ON nl.id_dvt = dvt_kho.id_dvt
                    WHERE ct.id_mon = '$id_mon'
                ";
                
                $cong_thuc = $this->db->select($query_ct);

                if ($cong_thuc) {
                    while ($nl = $cong_thuc->fetch_assoc()) {
                        $id_nguyen_lieu = $nl['id_nl'];
                        
                        $tong_can_dung = $nl['sl_dinh_muc'] * $sl_mon_khach_goi;
                        
                        $ty_le_quy_doi = $nl['he_so_ct'] / $nl['he_so_kho'];
                        
                        $sl_tru_kho = $tong_can_dung * $ty_le_quy_doi;

                        $sql_tru = "UPDATE nguyen_lieu 
                                    SET so_luong_ton = so_luong_ton - $sl_tru_kho 
                                    WHERE id_nl = '$id_nguyen_lieu'";
                        $this->db->update($sql_tru);
                    }
                }
            }
        }
    }

    // ============================================================
    // 4. [BONUS] TÍNH DEADLINE (Xử lý logic 20p vàng tại Class luôn)
    // ============================================================
    public function tinh_deadline($ngay, $gio)
    {
        $thoi_gian_don = $ngay . ' ' . $gio; 
        
        $deadline_timestamp = strtotime($thoi_gian_don) + (20 * 60); 
        
        return date('Y-m-d H:i:s', $deadline_timestamp);
    }

    // ============================================================
    // 5. [BONUS] BÁO HẾT MÓN (Khi bếp thấy hết nguyên liệu)
    // ============================================================
    public function bao_het_mon($id_mon)
    {
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);
        $query = "UPDATE monan SET tinhtrang = 0 WHERE id_mon = '$id_mon'";
        return $this->db->update($query);
    }

    // ============================================================
    // 6. [BONUS] THỐNG KÊ NHANH TRONG NGÀY (Để bếp biết nay làm nhiều hay ít)
    // ============================================================
    public function thong_ke_bep_hom_nay()
    {
        $today = date('Y-m-d');
        
        $q1 = "SELECT SUM(c.soluong) as tong_mon
               FROM hopdong_chitiet c
               JOIN hopdong h ON c.hopdong_id = h.id
               WHERE h.dates = '$today' AND h.status = 1";
               
        $q2 = "SELECT COUNT(*) as don_cho FROM hopdong WHERE dates = '$today' AND status = 0";

        $rs1 = $this->db->select($q1);
        $rs2 = $this->db->select($q2);

        $mon_da_lam = ($rs1 && $rs1->num_rows > 0) ? $rs1->fetch_assoc()['tong_mon'] : 0;
        $don_cho = ($rs2 && $rs2->num_rows > 0) ? $rs2->fetch_assoc()['don_cho'] : 0;

        return ['mon_da_lam' => $mon_da_lam, 'don_cho' => $don_cho];
    }
}
?>