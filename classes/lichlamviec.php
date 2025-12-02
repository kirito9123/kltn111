<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class LichLamViec
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    /**
     * Tự động khởi tạo lịch làm việc cho 1 tuần (nếu chưa có) và cập nhật theo đơn nghỉ phép đã duyệt.
     * @param int $mans Mã nhân sự
     * @param int $offset Số tuần (+1: tuần sau, -1: tuần trước, 0: tuần này)
     */
    public function khoiTaoLichTuan($mans, $offset = 0)
    {
        $mans = (int)$mans;

        // Xác định ngày bắt đầu và kết thúc của tuần
        $today = new DateTime();
        $monday = clone $today;
        $monday->modify('monday this week');
        $monday->modify(($offset >= 0 ? '+' : '') . ($offset * 7) . ' days');
        $sunday = clone $monday;
        $sunday->modify('+6 days');

        $startDate = $monday->format('Y-m-d');
        $endDate = $sunday->format('Y-m-d');

        // Kiểm tra lịch đã tồn tại cho tuần này chưa
        $checkQuery = "SELECT COUNT(*) as count FROM lichlamviec WHERE mans = '$mans' AND ngay BETWEEN '$startDate' AND '$endDate'";
        $checkResult = $this->db->select($checkQuery);
        $count = $checkResult ? $checkResult->fetch_assoc()['count'] : 0;

        $this->db->link->begin_transaction();
        try {
            if ($count > 0) {
                // Nếu đã có, chỉ cập nhật trạng thái theo đơn nghỉ phép đã duyệt
                $updateQuery = "UPDATE lichlamviec l
                                JOIN nghiphep n ON l.mans = n.mans AND l.ngay = n.ngay_nghi
                                SET l.trang_thai = 'Nghỉ Phép',
                                    l.ghi_chu = 'Nghỉ phép đã duyệt',
                                    l.trang_thai_cham_cong = 'Không áp dụng'
                                WHERE n.trang_thai = 'Đã duyệt'
                                  AND l.mans = '$mans'
                                  AND l.ngay BETWEEN '$startDate' AND '$endDate'
                                  AND l.trang_thai != 'Nghỉ Phép'"; // Chỉ cập nhật nếu chưa phải nghỉ phép
                $this->db->update($updateQuery);
                // Bạn có thể thêm logic để reset trạng thái nếu đơn nghỉ bị hủy (hiếm)
            } else {
                // Nếu chưa có, tạo lịch mới cho cả tuần
                for ($i = 0; $i < 7; $i++) {
                    $currentDate = clone $monday;
                    $currentDate->modify("+$i days");
                    $ngayStr = $currentDate->format('Y-m-d');
                    $dayOfWeek = $currentDate->format('N'); // 1 (Mon) to 7 (Sun)
                    $thuStr = $this->fm->convertDayOfWeek($dayOfWeek); // Helper để chuyển số sang Thứ

                    // Kiểm tra nghỉ phép đã duyệt cho ngày này
                    $nghiPhepQuery = "SELECT COUNT(*) as count FROM nghiphep
                                      WHERE mans = '$mans' AND ngay_nghi = '$ngayStr' AND trang_thai = 'Đã duyệt'";
                    $nghiPhepResult = $this->db->select($nghiPhepQuery);
                    $isNghiPhep = $nghiPhepResult ? ($nghiPhepResult->fetch_assoc()['count'] > 0) : false;

                    $gioBatDau = '08:00:00';
                    $gioKetThuc = '17:00:00';
                    $ghiChu = 'Nghỉ trưa 12h-13h';
                    $trangThaiChamCong = 'Chưa chấm công';
                    $trangThai = 'Đang hoạt động';

                    if ($dayOfWeek == 7) { // Chủ nhật
                        $gioBatDau = null;
                        $gioKetThuc = null;
                        $ghiChu = 'Nghỉ Chủ Nhật';
                        $trangThaiChamCong = 'Nghỉ';
                        $trangThai = 'Nghỉ';
                    }

                    if ($isNghiPhep) {
                        $ghiChu = 'Nghỉ phép đã duyệt';
                        $trangThaiChamCong = 'Không áp dụng';
                        $trangThai = 'Nghỉ Phép'; // Trạng thái mới
                    }

                    $insertQuery = "INSERT INTO lichlamviec
                                        (mans, ngay, thu, gio_bat_dau, gio_ket_thuc, ghi_chu, trang_thai, trang_thai_cham_cong)
                                    VALUES
                                        ('$mans', '$ngayStr', '$thuStr', " . ($gioBatDau ? "'$gioBatDau'" : "NULL") . ", " . ($gioKetThuc ? "'$gioKetThuc'" : "NULL") . ", '$ghiChu', '$trangThai', '$trangThaiChamCong')";
                    if (!$this->db->insert($insertQuery)) {
                        throw new Exception("Lỗi khi tạo lịch cho ngày $ngayStr.");
                    }
                }
            }
            $this->db->link->commit();
            return true;
        } catch (Exception $e) {
            $this->db->link->rollback();
            // echo $e->getMessage(); // Ghi log lỗi
            return false;
        }
    }

    /**
     * Xem lịch làm việc của nhân sự trong 1 tuần cụ thể
     * @param int $mans Mã nhân sự
     * @param int $offset Số tuần so với tuần hiện tại
     * @return array Mảng chứa thông tin tuần và dữ liệu lịch
     */
    public function xemLichTuan($mans, $offset = 0)
    {
        $mans = (int)$mans;
        // Khởi tạo/cập nhật lịch trước khi xem
        $this->khoiTaoLichTuan($mans, $offset);

        // Xác định ngày bắt đầu và kết thúc
        $today = new DateTime();
        $monday = clone $today;
        $monday->modify('monday this week');
        $monday->modify(($offset >= 0 ? '+' : '') . ($offset * 7) . ' days');
        $sunday = clone $monday;
        $sunday->modify('+6 days');
        $startDate = $monday->format('Y-m-d');
        $endDate = $sunday->format('Y-m-d');

        // Lấy dữ liệu đã được tạo/cập nhật
        $query = "SELECT * FROM lichlamviec
                  WHERE mans = '$mans' AND ngay BETWEEN '$startDate' AND '$endDate'
                  ORDER BY ngay ASC";
        $result = $this->db->select($query);

        return array(
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $result // Trả về đối tượng kết quả query
        );
    }

    /**
     * Lấy lịch làm việc theo ngày cụ thể (cho tìm kiếm)
     * @param int $mans Mã nhân sự
     * @param string $date Ngày (Y-m-d)
     * @return mixed Kết quả query hoặc false
     */
    public function xemLichNgay($mans, $date)
    {
        $mans = (int)$mans;
        $date = $this->fm->validation($date);
        if (empty($date)) return false;

        $query = "SELECT * FROM lichlamviec WHERE mans = '$mans' AND ngay = '$date'";
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy danh sách chấm công theo ngày (cho quản lý)
     * @param string $ngay Ngày (Y-m-d)
     * @return mixed Kết quả query hoặc false
     */
    public function layDanhSachChamCongNgay($ngay) {
        $ngay = $this->fm->validation($ngay);
        if (empty($ngay)) return false;

        $query = "SELECT l.*, n.hoten
                  FROM lichlamviec l
                  JOIN nhansu n ON l.mans = n.mans
                  WHERE l.ngay = '$ngay'
                  ORDER BY n.mans ASC";
        return $this->db->select($query);
    }

    /**
     * Cập nhật trạng thái chấm công thủ công (cho quản lý)
     * @param int $mans Mã nhân sự
     * @param string $ngay Ngày (Y-m-d)
     * @param string $trangthai Trạng thái mới
     * @return mixed Thông báo
     */
    public function capNhatTrangThaiChamCong($mans, $ngay, $trangthai) {
        $mans = (int)$mans;
        $ngay = $this->fm->validation($ngay);
        $trangthai = $this->fm->validation($trangthai);

        if (empty($mans) || empty($ngay) || empty($trangthai)) {
            return "<span class='error'>Dữ liệu không hợp lệ.</span>";
        }

        $query = "UPDATE lichlamviec SET trang_thai_cham_cong = '$trangthai'
                  WHERE mans = '$mans' AND ngay = '$ngay'";
        $result = $this->db->update($query);
        if ($result) {
            return "<span class='success'>Cập nhật trạng thái chấm công thành công.</span>";
        } else {
            return "<span class='error'>Cập nhật thất bại.</span>";
        }
    }

     /**
     * Ghi nhận check-in hoặc check-out bằng quét khuôn mặt
     * @param int $id_admin ID admin (từ nhận diện khuôn mặt)
     * @param string $type 'checkin' hoặc 'checkout'
     * @return array Kết quả ['success' => true/false, 'message' => '...']
     */
     public function ghiNhanChamCongKhuonMat($id_admin, $type = 'checkin') {
         $id_admin = (int)$id_admin;
         $now = new DateTime();
         $currentTime = $now->format('H:i:s');
         $currentDate = $now->format('Y-m-d');

         // 1. Tìm mans tương ứng với id_admin
         $queryFindMans = "SELECT mans FROM nhansu WHERE id_admin = '$id_admin'";
         $resultMans = $this->db->select($queryFindMans);
         if (!$resultMans || $resultMans->num_rows == 0) {
             return ['success' => false, 'message' => 'Không tìm thấy hồ sơ nhân sự ứng với tài khoản này.'];
         }
         $mans = $resultMans->fetch_assoc()['mans'];

         // 2. Tìm lịch làm việc của ngày hôm nay
         $queryLich = "SELECT * FROM lichlamviec WHERE mans = '$mans' AND ngay = '$currentDate'";
         $resultLich = $this->db->select($queryLich);
          if (!$resultLich || $resultLich->num_rows == 0) {
             return ['success' => false, 'message' => 'Không tìm thấy lịch làm việc cho hôm nay.'];
         }
          $lich = $resultLich->fetch_assoc();

         // 3. Kiểm tra trạng thái lịch (Nghỉ, Nghỉ phép...)
         if ($lich['trang_thai'] != 'Đang hoạt động') {
             return ['success' => false, 'message' => 'Hôm nay bạn không có lịch làm việc (' . $lich['trang_thai'] . ').'];
         }

          // 4. Xử lý check-in/check-out
          $updateQuery = "";
          $message = "";
          if ($type == 'checkin') {
             if ($lich['gio_cham_cong'] !== null) {
                  return ['success' => false, 'message' => 'Bạn đã check-in lúc ' . $lich['gio_cham_cong'] . ' rồi.'];
             }
             // So sánh giờ check-in với giờ bắt đầu để xác định trạng thái
             $gioBatDau = new DateTime($lich['gio_bat_dau']);
             $gioCheckIn = new DateTime($currentTime);
             $trangThaiChamCong = ($gioCheckIn <= $gioBatDau->modify('+15 minutes')) ? 'Đúng giờ' : 'Đi trễ'; // Cho phép trễ 15p

              $updateQuery = "UPDATE lichlamviec SET gio_cham_cong = '$currentTime', trang_thai_cham_cong = '$trangThaiChamCong'
                              WHERE id_lichlamviec = '{$lich['id_lichlamviec']}'";
              $message = "Check-in thành công lúc $currentTime ($trangThaiChamCong).";
          } elseif ($type == 'checkout') {
              if ($lich['gio_cham_cong'] === null) {
                  return ['success' => false, 'message' => 'Bạn chưa check-in hôm nay.'];
             }
              if ($lich['gio_check_out'] !== null) {
                 return ['success' => false, 'message' => 'Bạn đã check-out lúc ' . $lich['gio_check_out'] . ' rồi.'];
              }
              // So sánh giờ check-out với giờ kết thúc
             $gioKetThuc = new DateTime($lich['gio_ket_thuc']);
             $gioCheckOut = new DateTime($currentTime);
              $trangThaiPhu = ($gioCheckOut < $gioKetThuc->modify('-15 minutes')) ? '(Về sớm)' : ''; // Ghi nhận về sớm nếu trước 15p

               $updateQuery = "UPDATE lichlamviec SET gio_check_out = '$currentTime'
                              -- Bạn có thể cập nhật thêm trang_thai_cham_cong ở đây nếu muốn
                               WHERE id_lichlamviec = '{$lich['id_lichlamviec']}'";
              $message = "Check-out thành công lúc $currentTime $trangThaiPhu.";
          } else {
               return ['success' => false, 'message' => 'Loại chấm công không hợp lệ.'];
          }

          // 5. Thực hiện cập nhật
          $resultUpdate = $this->db->update($updateQuery);
           if ($resultUpdate) {
               return ['success' => true, 'message' => $message];
           } else {
               return ['success' => false, 'message' => 'Lỗi khi cập nhật CSDL chấm công.'];
           }
      }

}
?>