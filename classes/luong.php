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
                  FROM bangluong bl
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
                  FROM bangluong bl
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
    /**
     * Tính lương tháng (Preview)
     */
    public function tinhLuongThang($thang, $nam)
    {
        $thang = (int)$thang;
        $nam = (int)$nam;
        $data = [];

        // 1. Lấy danh sách nhân sự có mức lương
        $query_ns = "SELECT ns.mans, ns.hoten, l.luong_ca, l.phu_cap, tk.level 
                     FROM nhansu ns 
                     JOIN luong l ON ns.mans = l.mans 
                     LEFT JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                     WHERE ns.trangthai = 1";
        $nhansu = $this->db->select($query_ns);

        if ($nhansu) {
            while ($ns = $nhansu->fetch_assoc()) {
                $mans = $ns['mans'];
                $luong_ca = (float)$ns['luong_ca'];
                $phu_cap = (float)$ns['phu_cap'];

                // 2. Đếm số ca làm việc đã hoàn thành trong tháng
                // Chỉ tính các ca 'Đã hoàn thành' hoặc 'Đã check-in' (nếu muốn tính cả ca đang làm dở, nhưng thường là hoàn thành)
                // Ở đây ta tính 'Đã hoàn thành' và 'Đã check-in' (coi như chấm công rồi)
                $query_ca = "SELECT COUNT(*) as tong_ca, SUM(tien_phat) as tong_phat 
                             FROM tbl_dangkylich 
                             WHERE mans = '$mans' 
                             AND MONTH(ngay) = '$thang' AND YEAR(ngay) = '$nam' 
                             AND (trang_thai_cham_cong = 'Đã hoàn thành' OR trang_thai_cham_cong = 'Đã check-in')";

                $result_ca = $this->db->select($query_ca);
                $info_ca = $result_ca ? $result_ca->fetch_assoc() : ['tong_ca' => 0, 'tong_phat' => 0];

                $tong_ca = (int)$info_ca['tong_ca'];
                $tien_phat = (float)$info_ca['tong_phat'];

                // 3. Tính lương
                // Công thức: (Tổng ca * Lương ca) + Phụ cấp - Tiền phạt
                $tong_luong_ca = $tong_ca * $luong_ca;
                $thuc_lanh = $tong_luong_ca + $phu_cap - $tien_phat;

                $data[] = [
                    'mans' => $mans,
                    'hoten' => $ns['hoten'],
                    'level' => $ns['level'] ?? 0, // Default level 0 if null
                    'so_ca' => $tong_ca, // Mapped for quanlyluong.php
                    'tong_ca' => $tong_ca,
                    'luong_cung' => $tong_luong_ca + $phu_cap, // Mapped for quanlyluong.php (Includes allowance)
                    'luong_ca' => $luong_ca,
                    'phu_cap' => $phu_cap,
                    'tien_phat' => $tien_phat,
                    'thuc_lanh' => $thuc_lanh
                ];
            }
        }
        return $data;
    }

    /**
     * Chốt bảng lương
     */
    /**
     * Chốt bảng lương
     */
    public function chotBangLuong($thang, $nam)
    {
        $thang = (int)$thang;
        $nam = (int)$nam;

        // 1. Tính lương hiện tại
        $bang_luong = $this->tinhLuongThang($thang, $nam);
        if (empty($bang_luong)) return "<span class='error'>Không có dữ liệu để chốt lương.</span>";

        $count = 0;
        foreach ($bang_luong as $item) {
            $mans = $item['mans'];
            $tong_ca = $item['tong_ca'];
            $muc_luong_ca = $item['luong_ca'];
            $phu_cap = $item['phu_cap'];
            $tien_phat = $item['tien_phat'];
            $thuc_lanh = $item['thuc_lanh'];
            $now = date('Y-m-d H:i:s');

            // Kiểm tra xem đã chốt chưa
            $check = "SELECT id_bangluong FROM bangluong WHERE mans = '$mans' AND thang = '$thang' AND nam = '$nam'";
            $exists = $this->db->select($check);

            if ($exists) {
                // Update
                $query = "UPDATE bangluong 
                          SET tong_ca = '$tong_ca', 
                              muc_luong_ca = '$muc_luong_ca', 
                              phu_cap = '$phu_cap', 
                              tien_phat = '$tien_phat', 
                              thuc_lanh = '$thuc_lanh',
                              ngay_tao = '$now'
                          WHERE mans = '$mans' AND thang = '$thang' AND nam = '$nam' AND trang_thai = 0"; // Chỉ update nếu chưa thanh toán
                $this->db->update($query);
            } else {
                // Insert
                $query = "INSERT INTO bangluong (mans, thang, nam, tong_ca, muc_luong_ca, phu_cap, tien_phat, thuc_lanh, trang_thai, ngay_tao)
                          VALUES ('$mans', '$thang', '$nam', '$tong_ca', '$muc_luong_ca', '$phu_cap', '$tien_phat', '$thuc_lanh', 0, '$now')";
                $this->db->insert($query);
            }
            $count++;
        }

        return "<span class='success'>Đã chốt lương cho $count nhân viên.</span>";
    }

    /**
     * Xác nhận thanh toán
     */
    public function xacNhanThanhToan($payID)
    {
        $payID = (int)$payID;
        $query = "UPDATE bangluong SET trang_thai = 1, ngay_thanh_toan = NOW() WHERE id_bangluong = '$payID'";
        return $this->db->update($query);
    }
}
