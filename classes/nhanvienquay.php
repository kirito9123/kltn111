
<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class nhanvienquay
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    /**
     * Lấy danh sách tất cả đơn đặt bàn / đơn hàng
     * Dùng cho: danhsachdatban.php
     */
    public function show_danh_sach_don()
    {
        // Lấy tất cả đơn, trừ những đơn đã bị hủy mềm (nếu status = 0 là xóa)
        // Sắp xếp đơn mới nhất lên đầu
        $query = "SELECT * FROM hopdong WHERE status != 0 ORDER BY created_at DESC";
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy thông tin chi tiết CỦA 1 ĐƠN (Header)
     * Dùng cho: booking_edit.php, thanhtoanhopdong.php
     */
    public function get_thong_tin_don($id_hopdong)
    {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        $query = "SELECT * FROM hopdong WHERE id = '$id_hopdong' LIMIT 1";
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy danh sách MÓN ĂN trong đơn đó
     * Dùng cho: AJAX xem chi tiết, và hiển thị trong booking_edit.php
     */
    public function get_chi_tiet_mon_an($id_hopdong)
    {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        
        // Join bảng chi tiết với bảng món ăn để lấy tên và hình ảnh
        $query = "SELECT c.*, m.name_mon, m.images, m.gia_mon
                  FROM hopdong_chitiet c 
                  JOIN monan m ON c.monan_id = m.id_mon 
                  WHERE c.hopdong_id = '$id_hopdong'";
        
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Cập nhật trạng thái thanh toán (Ví dụ: khách trả tiền xong)
     * Dùng cho: thanhtoanhopdong.php
     */
    public function thanh_toan_don_hang($id_hopdong, $total_money, $payment_method = 'cash')
    {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        $total_money = mysqli_real_escape_string($this->db->link, $total_money);
        
        // Cập nhật: Trạng thái -> completed, Tổng tiền chốt, Phương thức thanh toán, Thời gian update
        $query = "UPDATE hopdong 
                  SET payment_status = 'completed',
                      thanhtien = '$total_money',
                      payment_method = '$payment_method',
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = '$id_hopdong'";
                  
        $result = $this->db->update($query);
        
        if ($result) {
            return "Thanh toán thành công!";
        } else {
            return "Lỗi khi thanh toán.";
        }
    }

    /**
     * Hủy đơn hàng (Soft delete hoặc đổi trạng thái)
     */
    public function huy_don_hang($id_hopdong)
    {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        // Giả sử status = 0 là ẩn, hoặc payment_status = 'cancelled'
        $query = "UPDATE hopdong SET status = 0, payment_status = 'cancelled' WHERE id = '$id_hopdong'";
        $result = $this->db->update($query);
        return $result;
    }
    
    /**
     * Cập nhật ghi chú cho đơn hàng
     */
    public function update_ghi_chu($id_hopdong, $note) {
        $id_hopdong = mysqli_real_escape_string($this->db->link, $id_hopdong);
        $note = mysqli_real_escape_string($this->db->link, $note);
        
        $query = "UPDATE hopdong SET ghichu = '$note' WHERE id = '$id_hopdong'";
        return $this->db->update($query);
    }

    public function show_don_hang_chua_thanh_toan()
    {
        // Lấy các đơn có status là pending hoặc deposit
        // Sắp xếp đơn mới nhất lên đầu
        $query = "SELECT * FROM hopdong 
          ORDER BY created_at DESC";
        
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy thông tin cụ thể của 1 đơn hàng (để hiển thị header trong phần chi tiết)
     */
    public function get_thong_tin_don_hang($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "SELECT * FROM hopdong WHERE id = '$id' LIMIT 1";
        $result = $this->db->select($query);
        return $result;
    }

    // 5. Lấy toàn bộ thực đơn
    public function get_toan_bo_menu() {
        $query = "SELECT * FROM monan WHERE tinhtrang = 1 ORDER BY id_loai ASC";
        return $this->db->select($query);
    }

    // 6. Thêm món vào đơn hàng (Nếu có rồi thì tăng số lượng, chưa có thì thêm mới)
    public function them_mon_vao_don($id_hd, $id_mon) {
        $id_hd = mysqli_real_escape_string($this->db->link, $id_hd);
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);

        // 1. Lấy giá hiện tại của món
        $query_mon = "SELECT * FROM monan WHERE id_mon = '$id_mon' LIMIT 1";
        $mon = $this->db->select($query_mon)->fetch_assoc();
        $gia = $mon['gia_mon'];

        // 2. [QUAN TRỌNG] Kiểm tra món này đã có trong đơn VÀ BẾP CHƯA LÀM (trangthai = 0) chưa?
        // Nếu Bếp làm rồi (trangthai = 1) thì coi như món mới, không gộp.
        $check = "SELECT * FROM hopdong_chitiet 
                WHERE hopdong_id = '$id_hd' 
                AND monan_id = '$id_mon' 
                AND trangthai = 0"; 
        
        $result_check = $this->db->select($check);

        if ($result_check) {
            // Trường hợp A: Món đang chờ làm -> Chỉ cần tăng số lượng
            $row = $result_check->fetch_assoc();
            $new_qty = $row['soluong'] + 1;
            $new_total = $new_qty * $gia;
            
            $update = "UPDATE hopdong_chitiet 
                    SET soluong = '$new_qty', thanhtien = '$new_total' 
                    WHERE id = '{$row['id']}'";
            $this->db->update($update);
        } else {
            // Trường hợp B: Món mới hoàn toàn HOẶC Món cũ đã làm xong -> Tạo dòng mới
            // Lưu ý: trangthai mặc định là 0, nhưng ghi rõ ra cho chắc chắn
            $insert = "INSERT INTO hopdong_chitiet(hopdong_id, monan_id, soluong, gia, thanhtien, trangthai) 
                    VALUES('$id_hd', '$id_mon', 1, '$gia', '$gia', 0)";
            $this->db->insert($insert);
        }

        // 3. [QUAN TRỌNG] "Đánh thức" đơn hàng: 
        // Dù đơn này trước đó đã xong (status=1) hay chưa, cứ có món mới thêm vào là phải set về 0 để hiện lên màn hình Bếp
        $query_wake_up = "UPDATE hopdong SET status = 0 WHERE id = '$id_hd'";
        $this->db->update($query_wake_up);

        // 4. Tính lại tổng tiền cho cả đơn hàng
        $this->update_tong_tien_hopdong($id_hd);
    }

    // 7. Cập nhật số lượng món (khi gõ số vào ô input)
    public function cap_nhat_so_luong($id_chitiet, $soluong) {
        $id_chitiet = mysqli_real_escape_string($this->db->link, $id_chitiet);
        $soluong = (int)$soluong;

        if ($soluong <= 0) {
            // Nếu số lượng <= 0 thì xóa luôn
            return $this->xoa_mon_khoi_don($id_chitiet);
        }

        // Lấy giá gốc để tính thành tiền
        $get_row = $this->db->select("SELECT gia, hopdong_id FROM hopdong_chitiet WHERE id='$id_chitiet'")->fetch_assoc();
        $gia = $get_row['gia'];
        $thanhtien = $gia * $soluong;
        $id_hd = $get_row['hopdong_id'];

        $query = "UPDATE hopdong_chitiet SET soluong = '$soluong', thanhtien = '$thanhtien' WHERE id = '$id_chitiet'";
        $result = $this->db->update($query);
        
        if($result) {
            $this->update_tong_tien_hopdong($id_hd);
        }
        return $result;
    }

    // 8. Xóa món khỏi đơn
    public function xoa_mon_khoi_don($id_chitiet) {
        $id_chitiet = mysqli_real_escape_string($this->db->link, $id_chitiet);
        
        // Lấy id_hd trước khi xóa để update tiền
        $get_hd = $this->db->select("SELECT hopdong_id FROM hopdong_chitiet WHERE id='$id_chitiet'")->fetch_assoc();
        $id_hd = $get_hd['hopdong_id'];

        $query = "DELETE FROM hopdong_chitiet WHERE id = '$id_chitiet'";
        $result = $this->db->delete($query);

        if($result) {
            $this->update_tong_tien_hopdong($id_hd);
        }
        return $result;
    }

    // 9. Hàm phụ: Tính tổng tiền các món và update vào bảng hopdong
    public function update_tong_tien_hopdong($id_hd) {
        $query_sum = "SELECT SUM(thanhtien) as tong FROM hopdong_chitiet WHERE hopdong_id = '$id_hd'";
        $res = $this->db->select($query_sum);
        $tong = 0;
        if($res) {
            $r = $res->fetch_assoc();
            $tong = $r['tong'] ? $r['tong'] : 0;
        }
        // Cập nhật vào bảng hopdong
        $this->db->update("UPDATE hopdong SET thanhtien = '$tong' WHERE id = '$id_hd'");
    }

    public function get_danh_sach_loai_mon() {
        // Sửa 'loaimon' thành 'loai_mon'
        // Sửa cột 'xoa' nếu bảng của bạn có cột này để ẩn danh mục đã xóa
        $query = "SELECT * FROM loai_mon WHERE xoa = 0 ORDER BY id_loai ASC";
        return $this->db->select($query);
    }

    // 5. Lấy menu (Có hỗ trợ lọc theo id_loai và phân trang nếu cần)
    public function get_menu_theo_loai($id_loai = 0, $keyword = '') {
        // Bảng món ăn là 'monan' (theo các hình trước)
        $query = "SELECT * FROM monan WHERE tinhtrang = 1"; // tinhtrang=1 là đang bán

        if ($id_loai > 0) {
            $query .= " AND id_loai = '$id_loai'";
        }
        
        if (!empty($keyword)) {
            $query .= " AND name_mon LIKE '%$keyword%'";
        }

        $query .= " ORDER BY id_mon ASC";
        return $this->db->select($query);
    }

    // --- 10. THANH TOÁN HOÀN TẤT ĐƠN HÀNG ---
    public function thanh_toan_hoan_tat($id_hd) {
        $id_hd = mysqli_real_escape_string($this->db->link, $id_hd);
        
        // Tính tổng tiền món lần cuối
        $tai_chinh = $this->lay_chi_tiet_tai_chinh($id_hd);
        $tong_tien = $tai_chinh['tong_tien_mon'];

        // Cập nhật trạng thái completed, lưu tổng tiền thực tế món ăn
        // Lưu ý: payment_status = 'completed' nghĩa là quy trình phục vụ kết thúc
        $query = "UPDATE hopdong 
                  SET payment_status = 'completed', 
                      thanhtien = '$tong_tien',
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = '$id_hd'";
                  
        return $this->db->update($query);
    }

    public function lay_chi_tiet_tai_chinh($id_hd) {
        $id_hd = mysqli_real_escape_string($this->db->link, $id_hd);
        
        // 1. Lấy tổng tiền món
        $query_sum = "SELECT SUM(thanhtien) as tong FROM hopdong_chitiet WHERE hopdong_id = '$id_hd'";
        $r_sum = $this->db->select($query_sum);
        
        $tong_tien_mon = 0;
        if ($r_sum) {
            $row = $r_sum->fetch_assoc();
            $tong_tien_mon = $row['tong'] ? (float)$row['tong'] : 0;
        }

        // 2. Lấy thông tin cọc
        $query_hd = "SELECT so_tien FROM hopdong WHERE id = '$id_hd' LIMIT 1";
        $r_hd = $this->db->select($query_hd);
        
        $da_coc = 0;
        // --- ĐOẠN SỬA LỖI FATAL ERROR ---
        if ($r_hd) {
            $row_hd = $r_hd->fetch_assoc(); // Chỉ chạy lệnh này khi $r_hd không phải false
            $da_coc = $row_hd['so_tien'] ? (float)$row_hd['so_tien'] : 0;
        }
        // --------------------------------

        // 3. Tính toán
        $can_thanh_toan = $tong_tien_mon - $da_coc; 

        return [
            'tong_tien_mon' => $tong_tien_mon,
            'da_coc' => $da_coc,
            'can_thanh_toan' => $can_thanh_toan
        ];
    }

    public function layCaHienTai() {
        $gio_hien_tai = date('H:i:s');
        // Dùng chuỗi trực tiếp thay vì bindParam để phù hợp với class Database của bạn
        $query = "SELECT * FROM tbl_ca 
                  WHERE '$gio_hien_tai' BETWEEN gio_bat_dau AND gio_ket_thuc
                  LIMIT 1";
        
        $result = $this->db->select($query);
        if ($result) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function tinhDoanhThuCa($gio_bat_dau, $gio_ket_thuc) {
        $ngay_hom_nay = date('Y-m-d');
        $start_datetime = $ngay_hom_nay . ' ' . $gio_bat_dau;
        $end_datetime   = $ngay_hom_nay . ' ' . $gio_ket_thuc;

        // Query tính tổng tiền
        $query = "SELECT SUM(thanhtien) as tong_tien 
                  FROM hopdong 
                  WHERE created_at >= '$start_datetime' 
                  AND created_at <= '$end_datetime' 
                  AND payment_status = 'completed'"; 

        $result = $this->db->select($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['tong_tien'] ? $row['tong_tien'] : 0;
        }
        return 0;
    }

    public function checkCaDaChot($id_ca) {
        $ngay_hom_nay = date('Y-m-d');
        $id_ca = mysqli_real_escape_string($this->db->link, $id_ca);

        $query = "SELECT * FROM tbl_giao_ca 
                  WHERE id_ca = '$id_ca' 
                  AND ngay_lam_viec = '$ngay_hom_nay' 
                  LIMIT 1";
        
        $result = $this->db->select($query);
        if ($result) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function luuBaoCaoChotCa($id_ca, $id_nv, $tong_he_thong, $tien_thuc_te, $ghi_chu) {
        $ngay_lam_viec = date('Y-m-d');
        $chenh_lech = $tien_thuc_te - $tong_he_thong;
        
        // Escape dữ liệu để tránh lỗi SQL Injection
        $ghi_chu = mysqli_real_escape_string($this->db->link, $ghi_chu);

        $query = "INSERT INTO tbl_giao_ca (id_ca, id_nhanvien, ngay_lam_viec, tong_tien_he_thong, tien_thuc_te, chenh_lech, ghi_chu)
                  VALUES ('$id_ca', '$id_nv', '$ngay_lam_viec', '$tong_he_thong', '$tien_thuc_te', '$chenh_lech', '$ghi_chu')";
        
        return $this->db->insert($query);
    }

    // --- CÁC HÀM THỐNG KÊ (ĐÃ SỬA) ---
    public function layMonBanChay($ngay, $limit = 5) {
        $query = "SELECT m.name_mon, 
                         SUM(ct.soluong) as tong_so_luong, 
                         SUM(ct.thanhtien) as tong_doanh_thu_mon
                  FROM hopdong_chitiet ct
                  JOIN hopdong h ON ct.hopdong_id = h.id
                  JOIN monan m ON ct.monan_id = m.id_mon
                  WHERE h.created_at LIKE '$ngay%' 
                  AND h.payment_status = 'completed'
                  GROUP BY m.id_mon, m.name_mon
                  ORDER BY tong_so_luong DESC
                  LIMIT $limit";

        $result = $this->db->select($query);
        $data = [];
        if($result) {
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function baoCaoXuatKho($ngay) {
        $query = "SELECT m.name_mon, 
                         SUM(ct.soluong) as da_ban
                  FROM hopdong_chitiet ct
                  JOIN hopdong h ON ct.hopdong_id = h.id
                  JOIN monan m ON ct.monan_id = m.id_mon
                  WHERE h.created_at LIKE '$ngay%' 
                  AND h.payment_status = 'completed'
                  GROUP BY m.id_mon, m.name_mon
                  ORDER BY m.name_mon ASC";

        $result = $this->db->select($query);
        $data = [];
        if($result) {
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>