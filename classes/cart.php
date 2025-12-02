<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class cart
{
   private $db;
   private $fm;

   public function __construct()
   {
      $this->db = new Database();
      $this->fm = new Format();
   }

   /* ======================= CART (giỏ tạm) ======================= */

   public function insert_cart($id, $soluong)
   {
      $soluong = $this->fm->validation($soluong);
      $soluong = mysqli_real_escape_string($this->db->link, $soluong);
      $id      = mysqli_real_escape_string($this->db->link, $id);
      $sid     = session_id();

      $qMon   = "SELECT id_mon, name_mon, gia_mon, images FROM monan WHERE id_mon='$id'";
      $result = $this->db->select($qMon);
      if (!$result) { header('Location:404.php'); return; }
      $mon = $result->fetch_assoc();

      $namemon = $mon['name_mon'];
      $giamon  = $mon['gia_mon'];
      $image   = $mon['images'];

      $query_insert = "
         INSERT INTO cart (id_mon, sesid, name_mon, gia_mon, soluong, images)
         VALUES ('$id', '$sid', '$namemon', '$giamon', '$soluong', '$image')
      ";
      $ok = $this->db->insert($query_insert);
      header('Location:' . ($ok ? 'cartt.php' : '404.php'));
   }

   public function get_cart()
   {
      $sid   = session_id();
      $query = "SELECT * FROM cart WHERE sesid='$sid'";
      return $this->db->select($query);
   }

   public function update_cart($soluong, $id_cart)
   {
      $soluong = mysqli_real_escape_string($this->db->link, $soluong);
      $id_cart = mysqli_real_escape_string($this->db->link, $id_cart);

      $query = "UPDATE cart SET soluong='$soluong' WHERE cart_id='$id_cart'";
      return $this->db->update($query);
   }

   public function del_loai($id)
   {
      $id    = mysqli_real_escape_string($this->db->link, $id);
      $query = "DELETE FROM cart WHERE cart_id='$id'";
      return $this->db->delete($query);
   }

   public function check()
   {
      $sid   = session_id();
      $query = "SELECT * FROM cart WHERE sesid='$sid'";
      return $this->db->select($query);
   }

   public function sum()
   {
      $query  = "SELECT SUM(gia_mon) AS s FROM cart";
      $result = $this->db->select($query);
      $row    = $result ? $result->fetch_assoc() : ['s' => 0];
      return $row['s'];
   }

   /* ======================= ORDER (đơn/hợp đồng) ======================= */

   // Tạo đơn cho workflow thanh toán online (đã đúng theo schema hopdong + hopdong_chitiet)
   public function insert_order($userid, $time, $date, $khach, $noidung, $vitri_id, $so_ban, $so_tien)
   {
      $sid     = session_id();
      $userid  = mysqli_real_escape_string($this->db->link, $userid);
      $time    = mysqli_real_escape_string($this->db->link, $time);
      $date    = mysqli_real_escape_string($this->db->link, $date);
      $khach   = mysqli_real_escape_string($this->db->link, $khach);
      $noidung = mysqli_real_escape_string($this->db->link, $noidung);

      $created_at = date('Y-m-d H:i:s');
      $this->db->link->begin_transaction();

      try {
         // header
         $sqlHeader = "INSERT INTO hopdong
            (sesis, id_user, dates, tg, noidung, so_user, thanhtien, created_at, vitri_id, so_ban, payment_status, payment_method, so_tien)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 'completed', 'vnpay', ?)";
         $stmt = $this->db->link->prepare($sqlHeader);
         $stmt->bind_param("sisssisiis", $sid, $userid, $date, $time, $noidung, $khach, $created_at, $vitri_id, $so_ban, $so_tien);
         if (!$stmt->execute()) throw new Exception("Insert header failed");
         $order_id = $this->db->link->insert_id;
         $stmt->close();

         $menu_chon = Session::get('menu_chon');
         if (!$menu_chon || !is_array($menu_chon)) throw new Exception("Không có món ăn được chọn");

         $sqlDet   = "INSERT INTO hopdong_chitiet (hopdong_id, monan_id, soluong, gia, thanhtien) VALUES (?, ?, ?, ?, ?)";
         $stmt_det = $this->db->link->prepare($sqlDet);
         if (!$stmt_det) throw new Exception("Prepare detail failed");

         $total_amount = 0;
         foreach ($menu_chon as $item) {
            $id_mon  = (int)$item['id_mon'];
            $soluong = (int)$item['soluong'];

            $sqlGia  = "SELECT gia_mon FROM monan WHERE id_mon = ?";
            $stmtGia = $this->db->link->prepare($sqlGia);
            $stmtGia->bind_param("i", $id_mon);
            $stmtGia->execute();
            $rsGia = $stmtGia->get_result();
            if ($rsGia->num_rows === 0) { $stmtGia->close(); throw new Exception("Món $id_mon không tồn tại"); }
            $gia = (float)$rsGia->fetch_assoc()['gia_mon'];
            $stmtGia->close();

            $thanhtien     = $gia * $soluong;
            $total_amount += $thanhtien;

            $stmt_det->bind_param("iiidd", $order_id, $id_mon, $soluong, $gia, $thanhtien);
            if (!$stmt_det->execute()) throw new Exception("Insert detail failed");
         }
         $stmt_det->close();

         // update tổng tiền
         $sqlUp = "UPDATE hopdong SET thanhtien=? WHERE id=?";
         $stmUp = $this->db->link->prepare($sqlUp);
         $stmUp->bind_param("di", $total_amount, $order_id);
         $stmUp->execute();
         $stmUp->close();

         $this->db->link->commit();

         $_SESSION['current_sesis']     = $sid;
         $_SESSION['order_created_at']  = $created_at;
         $_SESSION['current_order_id']  = $order_id;
      } catch (Exception $e) {
         $this->db->link->rollback();
         throw $e;
      }
   }

   // (Luồng tạo đơn kiểu cũ – giữ nguyên nếu bạn đang dùng)
   public function insert_order_admin($id_user, $time, $date, $khach, $noidung, $mon_list, $payment_method, $id_km = null, $phantram = 0)
   {
      $sesis      = session_id() . '_' . date('YmdHis');
      $created_at = date('Y-m-d H:i:s');
      $this->db->link->begin_transaction();

      try {
         foreach ($mon_list as $mon) {
            $gia_da_giam = $phantram > 0 ? round($mon['gia_mon'] * (1 - $phantram)) : $mon['gia_mon'];
            $thanhtien   = $gia_da_giam * $mon['soluong'];

            $sql = "INSERT INTO hopdong (
                     sesis, id_mon, name_mon, id_user, dates, tg, soluong, noidung, so_user,
                     gia, thanhtien, images, payment_method, payment_status, created_at, id_km
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'completed',?,?)";

            $stmt = $this->db->link->prepare($sql);
            $stmt->bind_param(
               "ssssssisiddsssi",
               $sesis, $mon['id_mon'], $mon['name_mon'], $id_user, $date, $time,
               $mon['soluong'], $noidung, $khach, $gia_da_giam, $thanhtien,
               $mon['images'], $payment_method, $created_at, $id_km
            );
            if (!$stmt->execute()) { $stmt->close(); throw new Exception("Lỗi insert món {$mon['id_mon']}"); }
            $stmt->close();
         }
         $this->db->link->commit();
         return true;
      } catch (Exception $e) {
         $this->db->link->rollback();
         error_log($e->getMessage());
         return false;
      }
   }

   public function del_all_cart()
   {
      $query = "DELETE FROM cart";
      return $this->db->delete($query);
   }

   public function cancel_order($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      // enum hiện có: pending/completed/failed
      $query = "UPDATE hopdong SET payment_status='failed' WHERE sesis='$sesis'";
      return $this->db->update($query);
   }

   public function show()
   {
      $query = "SELECT * FROM hopdong ORDER BY created_at DESC";
      return $this->db->select($query);
   }

   /* ======= CÁC HÀM HIỂN THỊ TỔNG QUAN/CHI TIẾT (ĐÃ JOIN CHUẨN) ======= */

   // Các đơn của 1 user (tổng theo sesis)
   public function show_thongtin($userId) {
      $uid = (int)$userId; // id_user là INT nên ép kiểu luôn

      $query = "
         SELECT
               h.id        AS sesis,
               h.dates     AS dates,
               h.noidung   AS noidung,
               h.so_user   AS so_user,
               h.so_ban    AS so_ban,
               h.tinhtrang AS tinhtrang,
               h.payment_status,                              -- ✨ THÊM DÒNG NÀY
               COALESCE(ct.tongtien, h.so_tien, h.thanhtien, 0) AS tongtien
         FROM hopdong h
         LEFT JOIN (
               SELECT hopdong_id, SUM(thanhtien) AS tongtien
               FROM hopdong_chitiet
               GROUP BY hopdong_id
         ) ct ON ct.hopdong_id = h.id
         WHERE h.id_user = $uid
         ORDER BY h.created_at DESC, h.id DESC
      ";

      return $this->db->select($query);
   }


   // Thông tin header của 1 đơn theo sesis
   public function show_thongtin1($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      $sql = "
        SELECT 
            h.*, kh.ten AS ten_khach
        FROM hopdong h
        LEFT JOIN khach_hang kh ON kh.id = h.id_user
        WHERE h.sesis = '$sesis'
        LIMIT 1
      ";
      return $this->db->select($sql);
   }

   // Chi tiết 1 đơn theo sesis (gồm từng món)
   public function show_thongtinid($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      $sql = "
        SELECT 
            h.sesis, h.dates, h.tg, h.so_user, h.so_ban, h.noidung,
            h.tinhtrang, h.payment_status, kh.ten AS ten_khach,
            c.id AS ct_id, c.monan_id, m.name_mon,
            c.soluong, COALESCE(c.gia, m.gia_mon) AS gia,
            COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) AS thanhtien
        FROM hopdong h
        JOIN khach_hang kh     ON kh.id = h.id_user
        JOIN hopdong_chitiet c ON c.hopdong_id = h.id
        JOIN monan m           ON m.id_mon      = c.monan_id
        WHERE h.sesis = '$sesis'
        ORDER BY c.id ASC
      ";
      return $this->db->select($sql);
   }

   // Danh sách đơn (tổng theo sesis)
   public function show_hopdong()
   {
      $sql = "
         SELECT
               h.id,                        -- dùng cho link chi tiết
               h.sesis,
               MIN(h.dates)        AS dates,
               MIN(h.tg)           AS tg,
               MIN(h.so_user)      AS so_user,
               MIN(h.so_ban)       AS so_ban,
               MIN(h.noidung)      AS noidung,
               MIN(h.tinhtrang)    AS tinhtrang,
               -- trạng thái mới nhất theo created_at
               SUBSTRING_INDEX(GROUP_CONCAT(h.payment_status ORDER BY h.created_at DESC), ',', 1) AS payment_status,
               -- tổng tiền ưu tiên từ chi tiết
               COALESCE(SUM(c.thanhtien), MIN(h.thanhtien), 0) AS tongtien,
               MIN(kh.ten)         AS ten_khach,
               MAX(h.created_at)   AS created_at
         FROM hopdong h
         JOIN khach_hang kh       ON kh.id = h.id_user
         LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
         GROUP BY h.id
         ORDER BY created_at DESC
      ";
      return $this->db->select($sql);
   }


   public function update_tinhtrang($sesis, $tinhtrang)
   {
      $tinhtrang = $this->fm->validation($tinhtrang);
      $tinhtrang = mysqli_real_escape_string($this->db->link, $tinhtrang);
      $sesis     = mysqli_real_escape_string($this->db->link, $sesis);

      $query  = "UPDATE hopdong SET tinhtrang='$tinhtrang' WHERE sesis='$sesis'";
      $ok     = $this->db->update($query);

      if ($ok) {
         echo "<script>alert('Cập nhật trạng thái thành công');window.location.href='danhsachdatban.php';</script>";
      } else {
         echo "<script>alert('Có lỗi khi cập nhật trạng thái');window.location.href='danhsachdatban.php';</script>";
      }
   }

   // Danh sách item chi tiết theo sesis
   public function show_chitiet_hopdong($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      $sql = "
        SELECT 
            c.id AS ct_id, c.hopdong_id, c.monan_id,
            m.name_mon, c.soluong,
            COALESCE(c.gia, m.gia_mon) AS gia,
            COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) AS thanhtien
        FROM hopdong h
        JOIN hopdong_chitiet c ON c.hopdong_id = h.id
        JOIN monan m           ON m.id_mon      = c.monan_id
        WHERE h.sesis = '$sesis'
        ORDER BY c.id ASC
      ";
      return $this->db->select($sql);
   }

   // Tổng tiền theo sesis (tính từ bảng chi tiết)
   public function get_tongtien_hopdong($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      $sql = "
        SELECT SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon))) AS tongtien
        FROM hopdong h
        LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id
        LEFT JOIN monan m           ON m.id_mon      = c.monan_id
        WHERE h.sesis = '$sesis'
      ";
      return $this->db->select($sql);
   }

   // Lấy 1 dòng header theo sesis (nếu cần dùng nơi khác)
   public function get_hopdong_thongtin($sesis)
   {
      $sesis = mysqli_real_escape_string($this->db->link, $sesis);
      $query = "SELECT * FROM hopdong WHERE sesis='$sesis' LIMIT 1";
      return $this->db->select($query);
   }
}
?>
