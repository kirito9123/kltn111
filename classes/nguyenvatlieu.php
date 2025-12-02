<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class nguyenvatlieu {

    private $db;
    private $fm;

    public function __construct() {
        $this->db = new Database();
        $this->fm = new Format();
    }

    public function show_nguyen_lieu() {
        $query = "SELECT * FROM nguyen_lieu WHERE xoa = 0 ORDER BY id_nl DESC";
        return $this->db->select($query);
    }

    public function show_nguyen_lieu_deleted() {
        $query = "SELECT * FROM nguyen_lieu WHERE xoa = 1 ORDER BY id_nl DESC";
        return $this->db->select($query);
    }   

    public function get_nguyen_lieu($id) {
        $query = "SELECT * FROM nguyen_lieu WHERE id_nl = '$id' LIMIT 1";
        return $this->db->select($query);
    }

    public function insert_nguyen_lieu($ten_nl, $don_vi, $so_luong_ton, $gia_nhap_tb, $ghichu) {

        $ten_nl       = mysqli_real_escape_string($this->db->link, $ten_nl);
        $don_vi       = mysqli_real_escape_string($this->db->link, $don_vi);
        $so_luong_ton = mysqli_real_escape_string($this->db->link, $so_luong_ton);
        $gia_nhap_tb  = mysqli_real_escape_string($this->db->link, $gia_nhap_tb);
        $ghichu       = mysqli_real_escape_string($this->db->link, $ghichu);

        $query = "INSERT INTO nguyen_lieu (ten_nl, don_vi, so_luong_ton, gia_nhap_tb, ghichu, xoa)
                VALUES ('$ten_nl', '$don_vi', '$so_luong_ton', '$gia_nhap_tb', '$ghichu', 0)";
        return $this->db->insert($query);
    }


    public function update_nguyen_lieu($id, $ten_nl, $don_vi, $so_luong_ton, $gia_nhap_tb, $ghichu) {

        $ten_nl       = mysqli_real_escape_string($this->db->link, $ten_nl);
        $don_vi       = mysqli_real_escape_string($this->db->link, $don_vi);
        $so_luong_ton = mysqli_real_escape_string($this->db->link, $so_luong_ton);
        $gia_nhap_tb  = mysqli_real_escape_string($this->db->link, $gia_nhap_tb);
        $ghichu       = mysqli_real_escape_string($this->db->link, $ghichu);

        $query = "UPDATE nguyen_lieu 
                SET ten_nl = '$ten_nl',
                    don_vi = '$don_vi',
                    so_luong_ton = '$so_luong_ton',
                    gia_nhap_tb = '$gia_nhap_tb',
                    ghichu = '$ghichu'
                WHERE id_nl = '$id'";
        return $this->db->update($query);
    }


    public function delete_nguyen_lieu($id) {
        $query = "UPDATE nguyen_lieu SET xoa = 1 WHERE id_nl = '$id'";
        return $this->db->update($query);
    }

    public function restore_nguyen_lieu($id) {
        $query = "UPDATE nguyen_lieu SET xoa = 0 WHERE id_nl = '$id'";
        return $this->db->update($query);
    }

    public function search_nguyen_lieu($keyword) {
        $keyword = mysqli_real_escape_string($this->db->link, $keyword);
        $query = "SELECT * FROM nguyen_lieu 
                WHERE xoa = 0 AND ten_nl LIKE '%$keyword%'
                ORDER BY id_nl DESC";
        return $this->db->select($query);
    }

    public function show_congthuc_monan() {
        $query = "
            SELECT  m.id_mon,
                    m.name_mon,
                    m.gia_mon,
                    m.id_loai,
                    l.name_loai,
                    COUNT(ct.id_nl) AS so_nguyen_lieu
            FROM monan AS m
            LEFT JOIN congthuc_mon AS ct ON m.id_mon = ct.id_mon
            LEFT JOIN loai_mon    AS l  ON m.id_loai = l.id_loai
            WHERE m.xoa = 0
            GROUP BY m.id_mon, m.name_mon, m.gia_mon, m.id_loai, l.name_loai
            ORDER BY m.id_mon ASC
        ";
        return $this->db->select($query);
    }

    public function get_monan($id_mon)
    {
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);

        $query = "
            SELECT 
                m.id_mon,
                m.name_mon,
                m.id_loai,
                m.gia_mon,
                m.ghichu_mon,
                m.images,
                m.tinhtrang,
                m.xoa,
                l.name_loai,
                l.ghichu AS ghichu_loai,
                l.xoa   AS xoa_loai
            FROM monan AS m
            INNER JOIN loai_mon AS l ON m.id_loai = l.id_loai
            WHERE m.id_mon = '$id_mon'
            AND m.xoa = 0
            AND l.xoa = 0
            LIMIT 1
        ";

        return $this->db->select($query);
    }

    public function show_monan()
    {
        $query = "
            SELECT id_mon, name_mon, id_loai
            FROM monan
            WHERE xoa = 0
            ORDER BY name_mon ASC
        ";
        return $this->db->select($query);
    }


    public function show_loai_monan()
    {
        $query = "
            SELECT id_loai, name_loai
            FROM loai_mon
            WHERE xoa = 0
            ORDER BY name_loai ASC
        ";
        return $this->db->select($query);
    }

    public function delete_congthuc_by_mon($id_mon)
    {
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);
        $query  = "DELETE FROM congthuc_mon WHERE id_mon = '$id_mon'";
        return $this->db->delete($query);
    }

    public function save_congthuc_mon($id_mon, $id_nl_arr, $so_luong_arr, $id_dvt_arr)
    {
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);

        // 1. Xóa công thức cũ
        $this->delete_congthuc_by_mon($id_mon);

        $ok = true;
        // 2. Duyệt qua mảng gửi lên để lưu mới
        for ($i = 0; $i < count($id_nl_arr); $i++) {
            $id_nl    = (int)($id_nl_arr[$i] ?? 0);
            $so_luong = (float)($so_luong_arr[$i] ?? 0);
            $id_dvt   = (int)($id_dvt_arr[$i] ?? 0); // Lấy ID đơn vị người dùng chọn

            if ($id_nl > 0 && $so_luong > 0 && $id_dvt > 0) {
                // Lưu đúng số lượng và đơn vị người dùng chọn (Ví dụ: 100 và id của gram)
                // Việc quy đổi sẽ do bộ phận Bếp hoặc Kho xử lý khi trừ kho
                $query = "INSERT INTO congthuc_mon (id_mon, id_nl, id_dvt, so_luong)
                          VALUES ('$id_mon', '$id_nl', '$id_dvt', '$so_luong')";
                
                $res = $this->db->insert($query);
                if (!$res) $ok = false;
            }
        }
        return $ok;
    }

    // --- CẬP NHẬT: LẤY CÔNG THỨC (ĐỂ HIỂN THỊ LẠI KHI SỬA) ---
    public function get_congthuc_by_mon($id_mon) {
        $id_mon = mysqli_real_escape_string($this->db->link, $id_mon);

        $query = "
            SELECT  
                ct.so_luong,
                ct.id_dvt,        -- Lấy ID đơn vị đã lưu trong công thức
                nl.id_nl,
                nl.ten_nl,
                dvt.ten_dvt,      -- Tên đơn vị (g, kg...)
                nl_dvt.nhom       -- Nhóm của nguyên liệu (để JS lọc)
            FROM congthuc_mon AS ct
            INNER JOIN nguyen_lieu AS nl ON ct.id_nl = nl.id_nl
            LEFT JOIN don_vi_tinh AS dvt ON ct.id_dvt = dvt.id_dvt
            LEFT JOIN don_vi_tinh AS nl_dvt ON nl.id_dvt = nl_dvt.id_dvt
            WHERE ct.id_mon = '$id_mon'
            ORDER BY nl.ten_nl ASC
        ";
        return $this->db->select($query);
    }

    public function insert_congthuc_mon($id_mon, $id_nl, $id_dvt, $so_luong_goc)
    {
        $id_mon       = (int)$id_mon;
        $id_nl        = (int)$id_nl;
        $id_dvt       = (int)$id_dvt;
        $so_luong_goc = (float)$so_luong_goc;

        $query = "
            INSERT INTO congthuc_mon (id_mon, id_nl, id_dvt, so_luong)
            VALUES ($id_mon, $id_nl, $id_dvt, $so_luong_goc)
        ";
        return $this->db->insert($query);
    }

    // Hàm lấy tất cả đơn vị tính từ bảng don_vi_tinh
    public function show_don_vi_tinh()
    {
        $query = "
            SELECT * FROM don_vi_tinh 
            ORDER BY nhom ASC, id_dvt ASC
        ";
        return $this->db->select($query);
    }

    public function tao_phieu_nhap($ma_phieu, $nhan_vien, $ghi_chu, $chi_tiet_nhap) {
        $ma_phieu = mysqli_real_escape_string($this->db->link, $ma_phieu);
        $nhan_vien = mysqli_real_escape_string($this->db->link, $nhan_vien);
        $ghi_chu = mysqli_real_escape_string($this->db->link, $ghi_chu);
        
        $query = "INSERT INTO phieu_nhap (ma_phieu, nhan_vien, tong_tien, ghi_chu) 
                  VALUES ('$ma_phieu', '$nhan_vien', 0, '$ghi_chu')";
        $insert_phieu = $this->db->insert($query);

        if ($insert_phieu) {
            $id_phieu = mysqli_insert_id($this->db->link);
            $tong_tien_phieu = 0;

            foreach ($chi_tiet_nhap as $item) {
                $id_nl = (int)$item['id_nl'];
                $sl_nhap = (float)$item['so_luong']; 
                $id_dvt_nhap = (int)$item['id_dvt_nhap'];
                
                // QUAN TRỌNG: Lấy Thành Tiền làm chuẩn (Người dùng nhập bao nhiêu tính bấy nhiêu)
                $thanh_tien = (float)$item['thanh_tien']; 
                $tong_tien_phieu += $thanh_tien;

                // A. LẤY HỆ SỐ QUY ĐỔI
                $q_info = "SELECT nl.id_dvt AS id_dvt_goc, 
                                  dvt_goc.he_so AS he_so_goc,
                                  dvt_nhap.he_so AS he_so_nhap
                           FROM nguyen_lieu nl
                           JOIN don_vi_tinh dvt_goc ON nl.id_dvt = dvt_goc.id_dvt
                           JOIN don_vi_tinh dvt_nhap ON dvt_nhap.id_dvt = $id_dvt_nhap
                           WHERE nl.id_nl = $id_nl";
                $info = $this->db->select($q_info)->fetch_assoc();
                
                $he_so_goc = (float)$info['he_so_goc'];
                $he_so_nhap = (float)$info['he_so_nhap'];

                // B. TÍNH SỐ LƯỢNG THỰC TẾ CỘNG VÀO KHO
                $ty_le = $he_so_nhap / $he_so_goc;
                $sl_luu_kho = $sl_nhap * $ty_le;

                // C. TÍNH GIÁ VỐN CHO 1 ĐƠN VỊ GỐC (Dựa trên Tổng tiền / Tổng số lượng thực)
                // Ví dụ: Tổng 50k / 0.5kg = 100k/kg
                $gia_von_goc = ($sl_luu_kho > 0) ? ($thanh_tien / $sl_luu_kho) : 0;
                
                // Lưu vào chi tiết phiếu (Lưu giá đơn vị nhập để tham khảo)
                $gia_don_vi_nhap = ($sl_nhap > 0) ? ($thanh_tien / $sl_nhap) : 0;

                $q_ct = "INSERT INTO phieu_nhap_chitiet (id_phieu, id_nl, so_luong_nhap, gia_nhap, thanh_tien)
                         VALUES ('$id_phieu', '$id_nl', '$sl_luu_kho', '$gia_von_goc', '$thanh_tien')";
                $this->db->insert($q_ct);

                // D. CẬP NHẬT KHO
                $this->cap_nhat_kho_sau_nhap($id_nl, $sl_luu_kho, $gia_von_goc);
            }

            $this->db->update("UPDATE phieu_nhap SET tong_tien = $tong_tien_phieu WHERE id_phieu = $id_phieu");
            return true;
        }
        return false;
    }

    // 2. Hàm phụ: Cập nhật kho (Tăng tồn kho + Tính giá vốn trung bình)
    private function cap_nhat_kho_sau_nhap($id_nl, $sl_nhap, $gia_nhap_moi) {
        // Lấy thông tin hiện tại
        $query = "SELECT so_luong_ton, gia_nhap_tb FROM nguyen_lieu WHERE id_nl = '$id_nl'";
        $result = $this->db->select($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $sl_cu = (float)$row['so_luong_ton'];
            $gia_cu = (float)$row['gia_nhap_tb'];

            // Tính tồn kho mới
            $sl_moi = $sl_cu + $sl_nhap;

            // Tính giá nhập trung bình mới (Weighted Average Cost)
            // Công thức: ((SL cũ * Giá cũ) + (SL nhập * Giá nhập)) / Tổng SL mới
            if ($sl_moi > 0) {
                $gia_tb_moi = (($sl_cu * $gia_cu) + ($sl_nhap * $gia_nhap_moi)) / $sl_moi;
            } else {
                $gia_tb_moi = $gia_nhap_moi;
            }

            // Update database
            $q_update = "UPDATE nguyen_lieu 
                         SET so_luong_ton = '$sl_moi', 
                             gia_nhap_tb = '$gia_tb_moi' 
                         WHERE id_nl = '$id_nl'";
            $this->db->update($q_update);
        }
    }
    
    // 3. Lấy mã phiếu nhập tiếp theo (Tự động tăng: PN001, PN002...)
    public function get_next_ma_phieu() {
        $q = "SELECT ma_phieu FROM phieu_nhap ORDER BY id_phieu DESC LIMIT 1";
        $rs = $this->db->select($q);
        if ($rs) {
            $row = $rs->fetch_assoc();
            $last_ma = $row['ma_phieu']; // Ví dụ: PN005
            $num = (int)substr($last_ma, 2); // Lấy số 5
            $num++;
            return 'PN' . str_pad($num, 3, '0', STR_PAD_LEFT); // Trả về PN006
        }
        return 'PN001';
    }

    public function get_phieu_header($id_phieu) {
        $id_phieu = mysqli_real_escape_string($this->db->link, $id_phieu);
        $query = "SELECT * FROM phieu_nhap WHERE id_phieu = '$id_phieu' LIMIT 1";
        return $this->db->select($query);
    }

    // 2. Lấy chi tiết các món trong phiếu (Dùng cho Body phiếu in & Lịch sử)
    public function get_chi_tiet_phieu($id_phieu) {
        $id_phieu = mysqli_real_escape_string($this->db->link, $id_phieu);
        
        // Join với bảng nguyen_lieu để lấy tên
        $query = "
            SELECT ct.*, nl.ten_nl, nl.don_vi 
            FROM phieu_nhap_chitiet ct
            JOIN nguyen_lieu nl ON ct.id_nl = nl.id_nl
            WHERE ct.id_phieu = '$id_phieu'
        ";
        return $this->db->select($query);
    }

    // 3. Lấy tất cả phiếu nhập (Dùng cho trang Lịch sử)
    public function get_all_phieu_nhap($tungay = '', $denngay = '') {
        $query = "SELECT * FROM phieu_nhap";
        if (!empty($tungay) && !empty($denngay)) {
            $query .= " WHERE DATE(ngay_nhap) BETWEEN '$tungay' AND '$denngay'";
        }
        $query .= " ORDER BY id_phieu DESC";
        return $this->db->select($query);
    }
}

?>
