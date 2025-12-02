<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class Luong
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    /**
     * Lấy danh sách lương cơ bản (Legacy method)
     */
    public function layDanhSachLuong()
    {
        $query = "SELECT ns.mans, ns.hoten, l.luongcoban, tk.level
                  FROM nhansu ns
                  LEFT JOIN luong l ON ns.mans = l.mans
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE ns.trangthai = 1
                  ORDER BY ns.mans ASC";
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy danh sách mức lương (New method for quanlyluong.php)
     */
    public function layDanhSachMucLuong()
    {
        // Try to select luong_ca and phu_cap. If they don't exist, this might fail, 
        // but we assume the DB matches quanlyluong.php requirements.
        $query = "SELECT ns.mans, ns.hoten, tk.level, l.luong_ca, l.phu_cap
                  FROM nhansu ns
                  LEFT JOIN luong l ON ns.mans = l.mans
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE ns.trangthai = 1
                  ORDER BY ns.mans ASC";
        return $this->db->select($query);
    }

    /**
     * Lấy thông tin lương của 1 nhân sự
     */
    public function layLuongMotNhanSu($mans)
    {
        $mans = (int)$mans;
        $query = "SELECT ns.mans, ns.hoten, l.luongcoban, l.luong_ca, l.phu_cap
                  FROM nhansu ns
                  LEFT JOIN luong l ON ns.mans = l.mans
                  WHERE ns.mans = '$mans'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    /**
     * Cập nhật lương cơ bản (Legacy)
     */
    public function capNhatLuong($mans, $luongcoban)
    {
        $mans = (int)$mans;
        $luongcoban = str_replace('.', '', $luongcoban);
        $luongcoban = (float)$luongcoban;

        if (empty($mans) || $luongcoban < 0) {
            return "<span class='error'>Dữ liệu không hợp lệ.</span>";
        }

        $checkQuery = "SELECT id_luong FROM luong WHERE mans = '$mans'";
        $exists = $this->db->select($checkQuery);

        if ($exists) {
            $query = "UPDATE luong SET luongcoban = '$luongcoban' WHERE mans = '$mans'";
            $result = $this->db->update($query);
        } else {
            $query = "INSERT INTO luong (mans, luongcoban) VALUES ('$mans', '$luongcoban')";
            $result = $this->db->insert($query);
        }

        if ($result) {
            return "<span class='success'>Cập nhật lương thành công.</span>";
        } else {
            return "<span class='error'>Cập nhật lương thất bại.</span>";
        }
    }

    /**
     * Cập nhật mức lương và phụ cấp (New method)
     */
    public function capNhatMucLuong($mans, $luong_ca, $phu_cap)
    {
        $mans = (int)$mans;
        $luong_ca = (float)$luong_ca;
        $phu_cap = (float)$phu_cap;

        $check = "SELECT id_luong FROM luong WHERE mans = '$mans'";
        if ($this->db->select($check)) {
            $query = "UPDATE luong SET luong_ca = '$luong_ca', phu_cap = '$phu_cap' WHERE mans = '$mans'";
            return $this->db->update($query);
        } else {
            $query = "INSERT INTO luong (mans, luong_ca, phu_cap) VALUES ('$mans', '$luong_ca', '$phu_cap')";
            return $this->db->insert($query);
        }
    }

    /**
     * Lấy bảng lương tổng hợp theo tháng (Used in in_luong.php)
     */
    public function layBangLuongTheoThang($thang, $nam)
    {
        $thang = (int)$thang;
        $nam = (int)$nam;
        // Join with nhansu and tb_admin to get details
        $query = "SELECT bl.*, ns.hoten, tk.level 
                  FROM bang_luong bl
                  JOIN nhansu ns ON bl.mans = ns.mans
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE bl.thang = '$thang' AND bl.nam = '$nam'
                  ORDER BY ns.mans ASC";
        return $this->db->select($query);
    }

    /**
     * Lấy lịch sử lương (Alias for layBangLuongTheoThang, used in quanlyluong.php)
     */
    public function layLichSuLuong($thang, $nam)
    {
        return $this->layBangLuongTheoThang($thang, $nam);
    }

    /**
     * Lấy chi tiết lương của 1 bản ghi (Used in in_luong.php)
     */
    public function layChiTietLuongByID($id)
    {
        $id = (int)$id;
        $query = "SELECT bl.*, ns.hoten, tk.level 
                  FROM bang_luong bl
                  JOIN nhansu ns ON bl.mans = ns.mans
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE bl.id_bangluong = '$id'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    /**
     * Tính lương tháng (Preview)
     * Note: This requires implementation of attendance calculation.
     * Returning empty array for now to prevent crash.
     */
    public function tinhLuongThang($thang, $nam)
    {
        // TODO: Implement logic to calculate salary from chamcong table
        return [];
    }

    /**
     * Chốt bảng lương
     */
    public function chotBangLuong($thang, $nam, $data)
    {
        // TODO: Implement logic to save calculated salary to bang_luong table
        return "<span class='error'>Chức năng chốt lương chưa được cài đặt.</span>";
    }

    /**
     * Xác nhận thanh toán
     */
    public function xacNhanThanhToan($payID)
    {
        $payID = (int)$payID;
        $query = "UPDATE bang_luong SET trang_thai = 1, ngay_thanh_toan = NOW() WHERE id_bangluong = '$payID'";
        return $this->db->update($query);
    }
}
