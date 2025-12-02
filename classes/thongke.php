<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class ThongKe
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    // 1. Thêm chi phí phát sinh (Nhập hàng...)
    public function themChiPhi($data)
    {
        $ten = mysqli_real_escape_string($this->db->link, $data['ten_chiphi']);
        $tien = str_replace(',', '', $data['so_tien']);
        $ngay = $data['ngay_chi'];
        $note = mysqli_real_escape_string($this->db->link, $data['ghi_chu']);
        $user = "Kế toán"; // Có thể lấy từ Session::get('adminName')

        if (empty($ten) || empty($tien) || empty($ngay)) return "<span class='error'>Vui lòng nhập đủ thông tin!</span>";

        $query = "INSERT INTO tbl_chiphi(ten_chiphi, so_tien, ngay_chi, ghi_chu, nguoi_tao) 
                  VALUES('$ten', '$tien', '$ngay', '$note', '$user')";
        return $this->db->insert($query) ? "<span class='success'>Thêm chi phí thành công!</span>" : "<span class='error'>Lỗi.</span>";
    }

    public function xoaChiPhi($id)
    {
        return $this->db->delete("DELETE FROM tbl_chiphi WHERE id_chiphi = '$id'");
    }

    // 2. Lấy Tổng DOANH THU (Từ Hợp đồng đã hoàn thành & thanh toán)
    public function getTongDoanhThu($thang, $nam)
    {
        $query = "SELECT SUM(thanhtien) as total FROM hopdong 
                  WHERE MONTH(dates) = '$thang' AND YEAR(dates) = '$nam' 
                  AND tinhtrang = 1 AND payment_status = 'completed'";
        $result = $this->db->select($query);
        return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
    }

    // 3. Lấy Tổng CHI LƯƠNG (Từ bảng lương đã thanh toán)
    public function getTongChiLuong($thang, $nam)
    {
        // Lưu ý: Lấy theo tháng/năm CỦA BẢNG LƯƠNG (tức là lương tháng đó)
        // Hoặc lấy theo ngay_thanh_toan nếu muốn tính dòng tiền thực tế chi ra trong tháng đó.
        // Ở đây tôi lấy theo kỳ lương để dễ đối soát.
        $query = "SELECT SUM(thuc_lanh) as total FROM bangluong 
                  WHERE thang = '$thang' AND nam = '$nam' AND trang_thai = 1";
        $result = $this->db->select($query);
        return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
    }

    // 4. Lấy Tổng CHI PHÍ KHÁC (Nhập hàng...)
    public function getTongChiPhiKhac($thang, $nam)
    {
        $query = "SELECT SUM(so_tien) as total FROM tbl_chiphi 
                  WHERE MONTH(ngay_chi) = '$thang' AND YEAR(ngay_chi) = '$nam'";
        $result = $this->db->select($query);
        return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
    }

    // 5. Lấy danh sách chi tiết Doanh thu
    public function getListDoanhThu($thang, $nam)
    {
        $query = "SELECT * FROM hopdong 
                  WHERE MONTH(dates) = '$thang' AND YEAR(dates) = '$nam' 
                  AND tinhtrang = 1 AND payment_status = 'completed' 
                  ORDER BY dates DESC";
        return $this->db->select($query);
    }

    // 6. Lấy danh sách chi tiết Chi Phí Khác
    public function getListChiPhi($thang, $nam)
    {
        $query = "SELECT * FROM tbl_chiphi 
                  WHERE MONTH(ngay_chi) = '$thang' AND YEAR(ngay_chi) = '$nam' 
                  ORDER BY ngay_chi DESC";
        return $this->db->select($query);
    }
}
