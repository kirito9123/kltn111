<?php
$filepath = realpath(dirname(__FILE__));
// Đường dẫn tương đối từ classes/nhanvienphucvu.php đến lib/database.php
include_once($filepath . '/../lib/database.php'); 
include_once($filepath . '/../helpers/format.php');

class NhanVienPhucVu 
{
    private $db;
    private $fm; 

    public function __construct() {
        // Khởi tạo đối tượng Database và Format
        $this->db = new Database();
        $this->fm = new Format();
    }

    /**
     * 1. Lấy danh sách đơn hàng CHỜ GIAO (status = 1) TRONG NGÀY HÔM NAY
     * Dùng cho Tab "Chờ giao món"
     */
    public function get_don_can_phuc_vu() {
        $query = "
            SELECT 
                id, 
                tenKH, 
                phong, 
                tg, 
                dates, 
                ghichu,
                noidung  /* Cột này dùng để trích xuất Tên Bàn */
            FROM 
                hopdong 
            WHERE 
                status = 1  
                AND dates = CURDATE() 
            ORDER BY 
                id ASC
        ";
        
        $result = $this->db->select($query); 
        return $result;
    }

    /**
     * 2. Lấy danh sách đơn hàng ĐÃ GIAO (status = 2) TRONG NGÀY HÔM NAY (MỚI THÊM)
     * Dùng cho Tab "Lịch sử hôm nay"
     */
    public function get_don_da_phuc_vu() {
        $query = "
            SELECT 
                id, 
                tenKH, 
                phong, 
                tg, 
                dates, 
                ghichu,
                noidung,
                updated_at /* Cần lấy thời gian hoàn thành để hiển thị */
            FROM 
                hopdong 
            WHERE 
                status = 2 
                AND dates = CURDATE() 
            ORDER BY 
                updated_at DESC /* Đơn mới giao xong hiện lên đầu */
        ";
        
        $result = $this->db->select($query); 
        return $result;
    }

    /**
     * 3. Lấy chi tiết món ăn và số lượng trong một đơn hàng cụ thể
     */
    public function get_chi_tiet_mon_cho_phuc_vu($id_hopdong) {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        
        // Join hopdong_chitiet (c) với monan (m) để lấy Tên món và Số lượng
        $query = "
            SELECT 
                c.soluong, 
                m.name_mon  
            FROM 
                hopdong_chitiet c
            JOIN
                monan m ON c.monan_id = m.id_mon
            WHERE 
                c.hopdong_id = '$id_hopdong'
        ";
        
        $result = $this->db->select($query); 
        return $result;
    }

    /**
     * 4. Cập nhật trạng thái đơn hàng sau khi nhân viên phục vụ đã giao món.
     */
    public function hoan_thanh_phuc_vu($order_id) {
        $order_id = mysqli_real_escape_string($this->db->link, $order_id);
        
        // status = 2: Đã giao món/Đã phục vụ
        // Cập nhật thêm updated_at để biết giờ giao xong
        $query = "UPDATE hopdong 
                  SET status = 2, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = '$order_id'"; 
        
        $update_row = $this->db->update($query); 
        return $update_row ? true : false;
    }
}
?>