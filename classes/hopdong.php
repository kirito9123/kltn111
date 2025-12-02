<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class HopDong
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    // Tạo đơn hàng mới, trạng thái mặc định là 'pending'
    public function createOrder($id_mon, $soluong, $id_km, $payment_method)
    {
        $mon_query = "SELECT * FROM monan WHERE id_mon = '$id_mon'";
        $mon = $this->db->select($mon_query)->fetch_assoc();
        $gia = $mon['gia_mon'];
        $thanhtien = $gia * $soluong;

        $phantram = 0;
        if (!empty($id_km)) {
            $km_query = "SELECT discout FROM khuyenmai WHERE id_km = '$id_km'";
            $km = $this->db->select($km_query)->fetch_assoc();
            if ($km) {
                $phantram = $km['discout'];
                $thanhtien = $thanhtien - ($thanhtien * $phantram / 100);
            }
        }

        if ($thanhtien < 0) $thanhtien = 0;
        $thanhtien = round($thanhtien, 2);

        // Xử lý id_km là NULL khi không chọn khuyến mãi
        $id_km_sql = (empty($id_km)) ? "NULL" : "'$id_km'";

        $query = "INSERT INTO hopdong 
            (id_mon, name_mon, soluong, gia, thanhtien, images, id_km, payment_method, payment_status, dates, tg) 
            VALUES ('$id_mon', '{$mon['name_mon']}', '$soluong', '$gia', '$thanhtien', '{$mon['images']}', $id_km_sql, '$payment_method', 'pending', CURDATE(), CURTIME())";
        $this->db->insert($query);
        return $this->db->link->insert_id;
    }

    // Lấy tất cả đơn hàng
    public function getAllOrders()
    {
        $query = "SELECT h.*, m.name_mon, k.name_km, kh.ten AS ten_khach
                FROM hopdong h
                LEFT JOIN monan m ON h.id_mon = m.id_mon
                LEFT JOIN khuyenmai k ON h.id_km = k.id_km
                LEFT JOIN khach_hang kh ON h.id_user = kh.id";
        return $this->db->select($query);
    }

    public function getAllOrdersSummary()
    {
        $sql = "
            SELECT 
                h.id,                -- duy nhất theo id
                h.sesis,
                h.id_user,
                h.dates,
                h.tg,
                COALESCE(SUM(c.thanhtien), h.thanhtien, 0) AS tongtien,
                h.payment_status,
                h.created_at
            FROM hopdong h
            LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
            GROUP BY h.id, h.sesis, h.id_user, h.dates, h.tg, h.payment_status, h.created_at
            ORDER BY h.created_at DESC
        ";
        return $this->db->select($sql);
    }





    // Lấy đơn hàng theo id
    public function getOrderById($id)
    {
        $query = "SELECT * FROM hopdong WHERE id = '$id'";
        return $this->db->select($query);
    }

    // Sửa đơn hàng
    public function updateOrder($id, $id_mon, $soluong, $id_km, $payment_method)
    {
        $mon_query = "SELECT * FROM monan WHERE id_mon = '$id_mon'";
        $mon = $this->db->select($mon_query)->fetch_assoc();
        $gia = $mon['gia_mon'];
        $thanhtien = $gia * $soluong;

        $phantram = 0;
        if (!empty($id_km)) {
            $km_query = "SELECT discout FROM khuyenmai WHERE id_km = '$id_km'";
            $km = $this->db->select($km_query)->fetch_assoc();
            if ($km) {
                $phantram = $km['discout'];
                $thanhtien = $thanhtien - ($thanhtien * $phantram / 100);
            }
        }

        if ($thanhtien < 0) $thanhtien = 0;
        $thanhtien = round($thanhtien, 2);

        // Xử lý id_km là NULL khi không chọn khuyến mãi
        $id_km_sql = (empty($id_km)) ? "NULL" : "'$id_km'";

        $query = "UPDATE hopdong SET 
                  id_mon = '$id_mon', 
                  name_mon = '{$mon['name_mon']}', 
                  soluong = '$soluong', 
                  gia = '$gia', 
                  thanhtien = '$thanhtien', 
                  images = '{$mon['images']}', 
                  id_km = $id_km_sql, 
                  payment_method = '$payment_method' 
                  WHERE id = '$id'";
        $this->db->update($query);
    }

    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($id, $status)
    {
        $query = "UPDATE hopdong SET payment_status = '$status' WHERE id = '$id'";
        $this->db->update($query);
    }

    // Xóa đơn hàng
    public function deleteOrder($id)
    {
        $query = "DELETE FROM hopdong WHERE id = '$id'";
        $this->db->delete($query);
    }

    // Lấy 1 dòng info tổng hợp cho đơn (chỉ lấy 1 món đại diện, các trường đều như nhau)
    public function getOrderInfoBySesis($sesis)
    {
        $query = "SELECT * FROM hopdong WHERE sesis = '$sesis' LIMIT 1";
        return $this->db->select($query);
    }

    // Lấy danh sách món ăn theo sesis (JOIN chi tiết + món)
    public function getOrderDetailsBySesis($sesis)
    {
        // escape nhẹ để an toàn nếu Database wrapper dùng mysqli
        if (isset($this->db->link)) {
            $sesis = mysqli_real_escape_string($this->db->link, $sesis);
        }

        $query = "
            SELECT
                c.id                  AS ct_id,
                c.hopdong_id,
                c.monan_id,
                m.name_mon,
                c.soluong,
                COALESCE(c.gia, m.gia_mon) AS gia,
                COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) AS thanhtien
            FROM hopdong h
            JOIN hopdong_chitiet c ON c.hopdong_id = h.id
            JOIN monan m           ON m.id_mon      = c.monan_id
            WHERE h.sesis = '$sesis'
            ORDER BY c.id ASC
        ";
        return $this->db->select($query);
    }

    public function getOrderTotalBySesis($sesis)
{
    if (isset($this->db->link)) {
        $sesis = mysqli_real_escape_string($this->db->link, $sesis);
    }

    $query = "
        SELECT
            S   UM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon))) AS tongtien
        FROM hopdong h
        JOIN hopdong_chitiet c ON c.hopdong_id = h.id
        JOIN monan m           ON m.id_mon      = c.monan_id
        WHERE h.sesis = '$sesis'
    ";
    return $this->db->select($query);
}
    public function saveChiTiet($hopdong_id, $menu_snapshot) {
        if ($hopdong_id <= 0 || empty($menu_snapshot)) return false;
        $conn = $this->db->link;

        $stmt = $conn->prepare("
            INSERT INTO hopdong_chitiet (hopdong_id, id_mon, ten_mon, so_luong, don_gia, thanh_tien)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($menu_snapshot as $item) {
            $id_mon  = (int)$item['id_mon'];
            $ten     = $item['name_mon'];
            $sl      = (int)$item['soluong'];
            $gia     = (float)$item['gia_mon'];
            $tt      = (float)$item['thanhtien'];

            $stmt->bind_param("iisidd", $hopdong_id, $id_mon, $ten, $sl, $gia, $tt);
            $stmt->execute();
        }
        $stmt->close();
        return true;
    }

    public function getOrderDetailsByHopdongId($hopdong_id)
    {
        // Ép kiểu & escape để chống injection
        $hopdong_id = (int)$hopdong_id;
        if ($hopdong_id <= 0) return false;

        // Truy vấn: lấy tên món, số lượng, giá, thành tiền
        $query = "
            SELECT 
                c.id              AS ct_id,
                c.hopdong_id,
                c.monan_id,
                m.name_mon,
                c.soluong,
                COALESCE(c.gia, m.gia_mon) AS gia,
                COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) AS thanhtien
            FROM hopdong_chitiet c
            JOIN monan m ON m.id_mon = c.monan_id
            WHERE c.hopdong_id = '$hopdong_id'
            ORDER BY c.id ASC
        ";

        return $this->db->select($query);
    }

    //hợp đồng chưa thanh toán
    public function getUnpaidOrdersWithBanSummary($filter = 'all') {
    $w = "";
    switch ($filter) {
        case 'today': $w = "AND DATE(h.created_at) = CURDATE()"; break;
        case 'week':  $w = "AND YEARWEEK(h.created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
        case 'month': $w = "AND DATE_FORMAT(h.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"; break;
        default:      $w = ""; break; // all
    }

    // đảm bảo đủ độ dài cho GROUP_CONCAT (tuỳ chọn)
    @$this->db->link->query("SET SESSION group_concat_max_len = 4096");

    $sql = "
        SELECT
            h.id                            AS hopdong_id,
            h.dates,
            h.tg,
            COALESCE(h.thanhtien,0)         AS tongtien,
            h.payment_status,
            h.so_ban,
            (
                SELECT GROUP_CONCAT(b.tenban ORDER BY b.id_ban SEPARATOR ', ')
                FROM ban b
                WHERE FIND_IN_SET(b.id_ban, REPLACE(h.so_ban,' ','')) > 0
            ) AS tenban
        FROM hopdong h
        WHERE h.payment_status IN ('pending','deposit')  -- chỉ những HĐ chưa xong
        $w
        ORDER BY h.id DESC
    ";
    return $this->db->select($sql);
}

    // Phần làm cho bếp
    // LẤY DANH SÁCH ĐƠN CHO BẾP (1 DÒNG / 1 ĐƠN)
    public function getKitchenOrders(string $range = 'all')
    {
        $where = [];

        // ĐƠN ĐANG PHỤC VỤ: status NULL hoặc 0 đều tính
        $where[] = "(h.status IS NULL OR h.status = 0)";

        // NGÀY PHỤC VỤ >= HÔM NAY
        $where[] = "STR_TO_DATE(h.dates, '%Y-%m-%d') >= CURDATE()";

        // Nếu muốn lọc thêm theo thời gian tạo đơn
        switch ($range) {
            case 'today':
                $where[] = "DATE(h.created_at) = CURDATE()";
                break;
            case 'week':
                $where[] = "YEARWEEK(h.created_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $where[] = "DATE_FORMAT(h.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
                break;
            default:
                // 'all' -> không thêm điều kiện created_at
                break;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $query = "
            SELECT
                h.id AS hopdong_id,
                TRIM(BOTH ', ' FROM COALESCE(
                    GROUP_CONCAT(DISTINCT b.tenban ORDER BY b.tenban SEPARATOR ', '),
                '')) AS tenban,
                h.dates,
                h.tg,
                h.payment_status,
                h.loaiphong,
                h.phong,
                h.ghichu,
                COALESCE(
                    SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, 0))),
                    h.thanhtien,
                    0
                ) AS tongtien,
                CASE
                    WHEN h.dates IS NOT NULL AND h.tg IS NOT NULL
                        THEN STR_TO_DATE(CONCAT(h.dates, ' ', h.tg), '%Y-%m-%d %H:%i')
                    ELSE NULL
                END AS due_at
            FROM hopdong h
            LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
            LEFT JOIN ban b ON FIND_IN_SET(b.id_ban, h.so_ban)
            {$whereSql}
            GROUP BY h.id
            ORDER BY
                COALESCE(
                    CASE
                        WHEN h.dates IS NOT NULL AND h.tg IS NOT NULL
                            THEN STR_TO_DATE(CONCAT(h.dates,' ',h.tg), '%Y-%m-%d %H:%i')
                        ELSE NULL
                    END,
                    h.created_at
                ) ASC,
                h.id ASC
        ";

        return $this->db->select($query);
    }

    // LẤY DANH SÁCH MÓN CỦA 1 ĐƠN
    public function getKitchenOrderItemsByHopdongId(int $hopdong_id)
    {
        $hopdong_id = (int)$hopdong_id;

        $query = "
            SELECT
                c.id AS ct_id,
                c.hopdong_id,
                c.monan_id,
                m.name_mon,
                c.soluong,
                COALESCE(c.gia, m.gia_mon) AS gia,
                COALESCE(
                    c.thanhtien,
                    c.soluong * COALESCE(c.gia, m.gia_mon, 0)
                ) AS thanhtien,
                COALESCE(m.ghichu_mon, '') AS ghichu
            FROM hopdong_chitiet c
            JOIN monan m ON m.id_mon = c.monan_id
            WHERE c.hopdong_id = {$hopdong_id}
            ORDER BY c.id ASC
        ";

        return $this->db->select($query);
    }

    public function capnhat_bep($id)
    {
        // Xử lý ID đầu vào cho an toàn
        $id = mysqli_real_escape_string($this->db->link, $id);

        // BƯỚC 1: Cập nhật trạng thái đơn hàng trong bảng 'hopdong'
        // status = 1 nghĩa là bếp đã làm xong
        $query = "UPDATE hopdong SET status = '1' WHERE id = '$id'";
        $result = $this->db->update($query);

        if ($result) {
            // ==================================================================
            // BẮT ĐẦU QUY TRÌNH TRỪ KHO
            // ==================================================================

            // BƯỚC 2: Lấy danh sách món ăn từ bảng 'hopdong_chitiet'
            $query_items = "SELECT monan_id, soluong FROM hopdong_chitiet WHERE hopdong_id = '$id'";
            $get_items = $this->db->select($query_items);

            if ($get_items) {
                while ($item = $get_items->fetch_assoc()) {
                    $id_mon = $item['monan_id']; // ID món ăn
                    $sl_mon = $item['soluong'];  // Số lượng món khách gọi

                    // BƯỚC 3: Lấy công thức của món ăn này
                    // Join bảng congthuc_mon với don_vi_tinh để lấy hệ số quy đổi của công thức
                    $query_congthuc = "
                        SELECT ct.id_nl, ct.so_luong, IFNULL(dvt.he_so, 1) as he_so_congthuc
                        FROM congthuc_mon ct
                        LEFT JOIN don_vi_tinh dvt ON ct.id_dvt = dvt.id_dvt
                        WHERE ct.id_mon = '$id_mon'
                    ";
                    $get_congthuc = $this->db->select($query_congthuc);

                    if ($get_congthuc) {
                        while ($ct = $get_congthuc->fetch_assoc()) {
                            $id_nl = $ct['id_nl'];            // ID nguyên liệu
                            $dinh_luong = $ct['so_luong'];    // Định lượng 1 phần (VD: 0.2)
                            $he_so_congthuc = $ct['he_so_congthuc']; // Hệ số đơn vị trong công thức

                            // BƯỚC 4: Lấy thông tin kho hiện tại của nguyên liệu để biết đơn vị kho
                            $query_kho = "
                                SELECT nl.so_luong_ton, IFNULL(dvt.he_so, 1) as he_so_kho
                                FROM nguyen_lieu nl
                                LEFT JOIN don_vi_tinh dvt ON nl.id_dvt = dvt.id_dvt
                                WHERE nl.id_nl = '$id_nl'
                            ";
                            $stock_info = $this->db->select($query_kho);
                            
                            if ($stock_info && $stock_info->num_rows > 0) {
                                $row_kho = $stock_info->fetch_assoc();
                                $he_so_kho = $row_kho['he_so_kho'];

                                // --- TÍNH TOÁN LƯỢNG CẦN TRỪ ---
                                // Công thức: (Số lượng món * Định lượng món * Hệ số CT) / Hệ số Kho
                                // Ví dụ: 2 tô phở * 0.5kg bánh * 1000 (đổi ra gam) / 1000 (kho lưu kg) -> Logic này cover mọi trường hợp
                                
                                $luong_tru = ($sl_mon * $dinh_luong * $he_so_congthuc) / $he_so_kho;

                                // BƯỚC 5: Thực hiện trừ kho
                                // Cho phép trừ âm (để biết bị lệch kho thực tế)
                                $query_tru_kho = "UPDATE nguyen_lieu 
                                                SET so_luong_ton = so_luong_ton - '$luong_tru' 
                                                WHERE id_nl = '$id_nl'";
                                $this->db->update($query_tru_kho);
                            }
                        }
                    }
                }
            }
            // ==================================================================
            
            return true; // Trả về true báo hiệu thành công
        } else {
            return false; // Lỗi không update được trạng thái đơn
        }
    }

    public function getOrdersToday()
    {
        $query = "
            SELECT
                h.id AS hopdong_id,
                TRIM(BOTH ', ' FROM COALESCE(
                    GROUP_CONCAT(DISTINCT b.tenban ORDER BY b.tenban SEPARATOR ', '),
                '')) AS tenban,
                h.dates,
                h.tg,
                h.status,
                h.payment_status,
                h.loaiphong,
                h.phong,
                h.ghichu,
                COALESCE(
                    SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, 0))),
                    h.thanhtien,
                    0
                ) AS tongtien,
                CASE
                    WHEN h.dates IS NOT NULL AND h.tg IS NOT NULL
                        THEN STR_TO_DATE(CONCAT(h.dates, ' ', h.tg), '%Y-%m-%d %H:%i')
                    ELSE NULL
                END AS due_at
            FROM hopdong h
            LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
            LEFT JOIN ban b ON FIND_IN_SET(b.id_ban, h.so_ban)
            WHERE STR_TO_DATE(h.dates, '%Y-%m-%d') = CURDATE()
            GROUP BY h.id
            ORDER BY
                COALESCE(
                    CASE
                        WHEN h.dates IS NOT NULL AND h.tg IS NOT NULL
                            THEN STR_TO_DATE(CONCAT(h.dates,' ',h.tg), '%Y-%m-%d %H:%i')
                        ELSE NULL
                    END,
                    h.created_at
                ) ASC,
                h.id ASC
        ";

        return $this->db->select($query);
    }

    public function getOrdersByDate(string $date)
    {
        // escape đúng cách
        $date = mysqli_real_escape_string($this->db->link, $date);

        $query = "
            SELECT
                h.id AS hopdong_id,
                TRIM(BOTH ', ' FROM COALESCE(
                    GROUP_CONCAT(DISTINCT b.tenban ORDER BY b.tenban SEPARATOR ', '),
                '')) AS tenban,
                h.dates,
                h.tg,
                h.payment_status,
                h.loaiphong,
                h.phong,
                h.ghichu,
                COALESCE(
                    SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, 0))),
                    h.thanhtien,
                    0
                ) AS tongtien,
                CASE
                    WHEN h.dates IS NOT NULL AND h.tg IS NOT NULL
                        THEN STR_TO_DATE(CONCAT(h.dates, ' ', h.tg), '%Y-%m-%d %H:%i')
                    ELSE NULL
                END AS due_at
            FROM hopdong h
            LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
            LEFT JOIN ban b ON FIND_IN_SET(b.id_ban, h.so_ban)
            WHERE h.dates = '{$date}'
            GROUP BY h.id
            ORDER BY due_at ASC, h.id ASC
        ";

        return $this->db->select($query);
    }




}


