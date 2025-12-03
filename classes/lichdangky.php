<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class lichdangky
{
    private $db;
    private $fm;

    // Định nghĩa giới hạn số lượng theo level
    private $level_limits = [
        1 => 1, // Kế toán
        2 => 1, // Quầy
        3 => 6, // Bếp
        4 => 10  // Phục vụ
    ];
    private $max_per_shift = 12; // Tổng số người tối đa mỗi ca
    private $min_shifts_per_week = 10; // Số ca tối thiểu đăng ký
    private $max_shifts_per_day = 2; // (MỚI) Giới hạn số ca tối đa mỗi nhân viên/ngày

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    /**
     * Lấy tất cả nhân sự đang làm việc kèm level
     */
    // public function get_all_nhansu_active()
    // {
    //     // Lấy cả level từ tb_admin
    //     $query = "SELECT ns.mans, ns.hoten, ta.level
    //               FROM nhansu ns
    //               JOIN tb_admin ta ON ns.id_admin = ta.id_admin
    //               WHERE ns.trangthai = 1 ORDER BY ns.hoten ASC";
    //     return $this->db->select($query);
    // }
    public function get_all_nhansu_active()
    {
        // THÊM ns.id_admin VÀO SELECT ĐỂ ĐỐI CHIẾU
        $query = "SELECT ns.mans, ns.hoten, ns.id_admin, ta.level
                  FROM nhansu ns
                  JOIN tb_admin ta ON ns.id_admin = ta.id_admin
                  WHERE ns.trangthai = 1 ORDER BY ns.hoten ASC";
        return $this->db->select($query);
    }
    /**
     * Lấy tất cả các ca làm việc
     */
    public function get_all_ca()
    {
        $query = "SELECT * FROM tbl_ca ORDER BY id_ca ASC";
        return $this->db->select($query);
    }

    /**
     * Lấy lịch đăng ký và trạng thái chấm công cho tuần/nhân viên
     */
    public function get_registered_schedule_with_status($week_string, $filter_mans = null)
    {
        list($start_date, $end_date) = $this->get_week_start_end($week_string);
        if (!$start_date) return [];

        // Thêm dk.level vào câu SELECT
        $query = "SELECT
                    dk.id_dangky, dk.ngay, dk.id_ca, dk.mans, dk.level,
                    dk.gio_cham_cong, dk.gio_check_out, dk.trang_thai_cham_cong,
                    ns.hoten AS ten_nhansu, ca.ten_ca, ca.gio_bat_dau, ca.gio_ket_thuc
                  FROM tbl_dangkylich dk
                  JOIN nhansu ns ON dk.mans = ns.mans
                  JOIN tbl_ca ca ON dk.id_ca = ca.id_ca
                  WHERE dk.ngay BETWEEN '$start_date' AND '$end_date'";

        if ($filter_mans !== null && (int)$filter_mans > 0) {
            $mans_safe = (int)$filter_mans;
            $query .= " AND dk.mans = $mans_safe";
        }
        $query .= " ORDER BY dk.ngay ASC, ca.gio_bat_dau ASC, ns.hoten ASC";
        $result = $this->db->select($query);

        $schedule_data = [];
        // Khởi tạo mảng ngày (giữ nguyên)
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        while ($current_date <= $end_date_obj) {
            $schedule_data[$current_date->format('Y-m-d')] = [];
            $current_date->modify('+1 day');
        }

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ngay = $row['ngay'];
                $id_ca = $row['id_ca'];
                if (!isset($schedule_data[$ngay][$id_ca])) {
                    $schedule_data[$ngay][$id_ca] = [
                        'ten_ca' => $row['ten_ca'],
                        'gio_bat_dau' => $row['gio_bat_dau'],
                        'gio_ket_thuc' => $row['gio_ket_thuc'],
                        'nhan_vien_dang_ky' => [] // Sẽ chứa thông tin nhân viên
                    ];
                }
                // Thêm level vào thông tin nhân viên đăng ký
                $schedule_data[$ngay][$id_ca]['nhan_vien_dang_ky'][] = [
                    'id_dangky' => $row['id_dangky'],
                    'mans' => $row['mans'],
                    'hoten' => $row['ten_nhansu'],
                    'level' => $row['level'], // Thêm level
                    'gio_cham_cong' => $row['gio_cham_cong'],
                    'gio_check_out' => $row['gio_check_out'],
                    'trang_thai_cham_cong' => $row['trang_thai_cham_cong']
                ];
            }
        }
        return $schedule_data;
    }

    /**
     * Xử lý đăng ký/cập nhật lịch - CÓ VALIDATION
     */
    public function register_shifts($data)
    {
        $mans = isset($data['mans']) ? mysqli_real_escape_string($this->db->link, $data['mans']) : null;
        $week_string = isset($data['week']) ? mysqli_real_escape_string($this->db->link, $data['week']) : null;
        $shifts = isset($data['shifts']) && is_array($data['shifts']) ? $data['shifts'] : [];

        if (empty($mans) || empty($week_string)) {
            return "<script>alert('LỖI: Vui lòng chọn nhân viên và tuần.');</script>";
        }

        // Lấy thông tin nhân viên (bao gồm level)
        $nhanvien_info_query = "SELECT ns.hoten, ta.level
                                FROM nhansu ns JOIN tb_admin ta ON ns.id_admin = ta.id_admin
                                WHERE ns.mans = '$mans'";
        $nhanvien_result = $this->db->select($nhanvien_info_query);
        if (!$nhanvien_result || $nhanvien_result->num_rows === 0) {
            return "<script>alert('LỖI: Không tìm thấy thông tin nhân viên với mã \\'$mans\\'.');</script>";
        }
        $nhanvien_data = $nhanvien_result->fetch_assoc();
        $employee_level = $nhanvien_data['level'];
        $employee_name = $nhanvien_data['hoten'];

        list($start_date, $end_date) = $this->get_week_start_end($week_string);
        if (!$start_date) return "<script>alert('LỖI: Định dạng tuần không hợp lệ.');</script>";
        list($year, $week_num) = $this->parse_week_string($week_string);

        // Đếm số ca mới được chọn trong form
        $new_selected_shifts_count = 0;
        $new_shifts_to_insert = []; // Lưu các ca mới hợp lệ để insert
        foreach ($shifts as $ngay => $cas) {
            if (is_array($cas) && ($ngay >= $start_date && $ngay <= $end_date)) {
                foreach ($cas as $id_ca => $value) {
                    if ($value == 'on' && filter_var($id_ca, FILTER_VALIDATE_INT)) {
                        $new_selected_shifts_count++;
                        $new_shifts_to_insert[] = ['ngay' => $ngay, 'id_ca' => (int)$id_ca];
                    }
                }
            }
        }

        // Lấy các ca nhân viên này ĐÃ ĐĂNG KÝ và CHƯA CHẤM CÔNG trong tuần
        $existing_unprocessed_query = "SELECT ngay, id_ca FROM tbl_dangkylich
                                       WHERE mans = '$mans' AND ngay BETWEEN '$start_date' AND '$end_date'
                                       AND trang_thai_cham_cong = 'Chưa chấm công'";
        $existing_unprocessed_result = $this->db->select($existing_unprocessed_query);
        $existing_unprocessed_map = [];
        if ($existing_unprocessed_result) {
            while ($row = $existing_unprocessed_result->fetch_assoc()) {
                $existing_unprocessed_map[$row['ngay']][$row['id_ca']] = true;
            }
        }

        // Lấy các ca nhân viên này ĐÃ CHẤM CÔNG trong tuần (không thể xóa)
        $existing_processed_query = "SELECT ngay, id_ca FROM tbl_dangkylich
                                     WHERE mans = '$mans' AND ngay BETWEEN '$start_date' AND '$end_date'
                                     AND trang_thai_cham_cong != 'Chưa chấm công'";
        $existing_processed_result = $this->db->select($existing_processed_query);
        $processed_shifts_count = 0;
        $processed_shifts_map = [];
        if ($existing_processed_result) {
            while ($row = $existing_processed_result->fetch_assoc()) {
                $processed_shifts_map[$row['ngay']][$row['id_ca']] = true;
                $processed_shifts_count++;
            }
        }

        // Tính tổng số ca sẽ có sau khi cập nhật (ca mới + ca đã chấm công)
        $total_final_shifts = $new_selected_shifts_count + $processed_shifts_count;

        // *** (MỚI) KIỂM TRA SỐ CA TỐI ĐA MỖI NGÀY (2 ca/ngày) ***
        $daily_shift_count = [];
        $validation_errors_daily_limit = [];

        // 1. Đếm các ca đã chấm công (không thể thay đổi)
        foreach ($processed_shifts_map as $ngay => $cas) {
            if (!isset($daily_shift_count[$ngay])) {
                $daily_shift_count[$ngay] = 0;
            }
            $daily_shift_count[$ngay] += count($cas);
        }

        // 2. Đếm các ca mới hoặc ca cũ được giữ lại (từ form)
        foreach ($new_shifts_to_insert as $shift) {
            $ngay = $shift['ngay'];
            // Chỉ đếm nếu nó CHƯA phải là ca đã xử lý (tránh đếm trùng)
            if (!isset($processed_shifts_map[$ngay][$shift['id_ca']])) {
                if (!isset($daily_shift_count[$ngay])) {
                    $daily_shift_count[$ngay] = 0;
                }
                $daily_shift_count[$ngay]++;
            }
        }

        // 3. Kiểm tra map đếm
        foreach ($daily_shift_count as $ngay => $count) {
            if ($count > $this->max_shifts_per_day) {
                // Định dạng lại ngày cho dễ đọc
                try {
                    $ngay_obj = new DateTime($ngay);
                    $ngay_formatted = $ngay_obj->format('d/m/Y');
                } catch (Exception $e) {
                    $ngay_formatted = $ngay;
                }
                $validation_errors_daily_limit[] = "Ngày $ngay_formatted đăng ký $count ca (vượt quá giới hạn {$this->max_shifts_per_day} ca/ngày).";
            }
        }

        // 4. Nếu có lỗi, trả về ngay (chưa vào transaction nên không cần rollback)
        if (!empty($validation_errors_daily_limit)) {
            // (MỚI) Chuyển đổi mảng lỗi thành chuỗi cho alert
            $alert_msg = "LỖI ĐĂNG KÝ CA:" . implode($validation_errors_daily_limit);
            // addslashes để xử lý các ký tự đặc biệt như ' trong tên
            return "<script>alert('" . addslashes($alert_msg) . "');</script>";
        }
        // *** KẾT THÚC KIỂM TRA CA TỐI ĐA MỖI NGÀY ***


        // *** KIỂM TRA SỐ CA TỐI THIỂU ***
        if ($total_final_shifts < $this->min_shifts_per_week) {
            // (MỚI) Chuyển thành alert
            $alert_msg = "Đăng ký thất bại! Phải đăng ký đủ {$this->min_shifts_per_week} ca (Hiện tại chọn {$new_selected_shifts_count} ca mới + {$processed_shifts_count} ca đã chấm công = {$total_final_shifts} ca).";
            return "<script>alert('" . addslashes($alert_msg) . "');</script>";
        }

        $this->db->link->begin_transaction();
        try {
            // Xác định ca cần xóa (ca cũ chưa chấm công và không được chọn lại)
            $shifts_to_delete = [];
            foreach ($existing_unprocessed_map as $ngay => $cas) {
                foreach ($cas as $id_ca => $value) {
                    // Kiểm tra xem ca này có trong mảng new_shifts_to_insert không
                    $found_in_new = false;
                    foreach ($new_shifts_to_insert as $new_shift) {
                        if ($new_shift['ngay'] == $ngay && $new_shift['id_ca'] == $id_ca) {
                            $found_in_new = true;
                            break;
                        }
                    }
                    if (!$found_in_new) {
                        $shifts_to_delete[] = "(ngay = '" . mysqli_real_escape_string($this->db->link, $ngay) . "' AND id_ca = " . (int)$id_ca . ")";
                    }
                }
            }

            // Thực hiện xóa
            if (!empty($shifts_to_delete)) {
                $delete_query = "DELETE FROM tbl_dangkylich
                                  WHERE mans = '$mans' AND (" . implode(" OR ", $shifts_to_delete) . ")";
                if (!$this->db->delete($delete_query)) {
                    throw new Exception("Lỗi hệ thống khi xóa đăng ký ca cũ.");
                }
            }

            // Xác định ca cần thêm (ca mới được chọn và chưa tồn tại hoặc đã bị xóa ở bước trên)
            $shifts_to_add_values = [];
            $validation_errors = [];
            foreach ($new_shifts_to_insert as $shift) {
                $ngay = $shift['ngay'];
                $id_ca = $shift['id_ca'];

                // Chỉ thêm nếu ca đó KHÔNG phải là ca đã chấm công VÀ KHÔNG phải là ca chưa chấm công đã tồn tại (tức là ca không đổi)
                if (!isset($processed_shifts_map[$ngay][$id_ca]) && !isset($existing_unprocessed_map[$ngay][$id_ca])) {
                    // *** KIỂM TRA GIỚI HẠN CA TRƯỚC KHI THÊM ***
                    // Sửa lỗi: Chuyển từ execute_query (PHP 8.2+) về $this->db->select (tương thích PHP 8.1)
                    $ngay_escaped_check = mysqli_real_escape_string($this->db->link, $ngay);
                    $id_ca_escaped_check = (int)$id_ca;
                    $check_limit_query = "SELECT level, COUNT(*) as count
                                          FROM tbl_dangkylich
                                          WHERE ngay = '$ngay_escaped_check' AND id_ca = $id_ca_escaped_check
                                          GROUP BY level";
                    $check_limit_result = $this->db->select($check_limit_query); // Sử dụng $this->db->select

                    $current_counts = [];
                    $total_in_shift = 0;
                    if ($check_limit_result) {
                        while ($row = $check_limit_result->fetch_assoc()) {
                            $current_counts[$row['level']] = $row['count'];
                            $total_in_shift += $row['count'];
                        }
                    }

                    // 1. Kiểm tra tổng số người
                    if ($total_in_shift >= $this->max_per_shift) {
                        $validation_errors[] = "Ca ngày $ngay (ID: $id_ca) đã đủ {$this->max_per_shift} người.";
                        continue; // Bỏ qua ca này
                    }

                    // 2. Kiểm tra giới hạn theo level
                    if (isset($this->level_limits[$employee_level])) {
                        $current_level_count = $current_counts[$employee_level] ?? 0;
                        if ($current_level_count >= $this->level_limits[$employee_level]) {
                            $validation_errors[] = "Ca ngày $ngay (ID: $id_ca) đã đủ số lượng cho Level $employee_level.";
                            continue; // Bỏ qua ca này
                        }
                    }
                    // Thêm vào danh sách chờ insert nếu hợp lệ
                    $ngay_escaped = mysqli_real_escape_string($this->db->link, $ngay);
                    $id_ca_escaped = (int)$id_ca;
                    $shifts_to_add_values[] = "('$mans', '$ngay_escaped', '$id_ca_escaped', '$employee_level')"; // Thêm level vào INSERT
                }
            }

            // Nếu có lỗi validation, rollback và thông báo
            if (!empty($validation_errors)) {
                // (MỚI) Chuyển đổi mảng lỗi thành chuỗi cho alert
                $alert_msg = "Lỗi đăng ký ca:\\n\\n" . implode("\\n", $validation_errors);
                throw new Exception($alert_msg); // Ném lỗi (sẽ được bắt ở dưới)
            }

            // Thực hiện thêm mới nếu có
            if (!empty($shifts_to_add_values)) {
                // Thêm cột level vào INSERT
                $insert_query = "INSERT INTO tbl_dangkylich (mans, ngay, id_ca, level) VALUES " . implode(", ", $shifts_to_add_values);
                if (!$this->db->insert($insert_query)) {
                    throw new Exception("Lỗi hệ thống khi lưu đăng ký ca mới.");
                }
            }

            // *** KIỂM TRA SỐ NGƯỜI TỐI THIỂU SAU KHI CẬP NHẬT ***
            $check_min_query = "SELECT ngay, id_ca, COUNT(*) as total_count
                                FROM tbl_dangkylich
                                WHERE ngay BETWEEN '$start_date' AND '$end_date'
                                GROUP BY ngay, id_ca
                                HAVING total_count = 0"; // Tìm các ca không còn ai
            $min_check_result = $this->db->select($check_min_query);
            if ($min_check_result && $min_check_result->num_rows > 0) {
                $empty_shifts = [];
                while ($row = $min_check_result->fetch_assoc()) {
                    $empty_shifts[] = "Ca ngày " . $row['ngay'] . " (ID: " . $row['id_ca'] . ")";
                }
                // Quyết định: Rollback hay chỉ cảnh báo? Hiện tại sẽ rollback để đảm bảo quy tắc
                $alert_msg = "Lỗi: Thao tác này khiến các ca sau không còn ai đăng ký:\\n\\n" . implode("\\n", $empty_shifts);
                throw new Exception($alert_msg); // Ném lỗi
            }


            $this->db->link->commit();

            // Đếm lại tổng số ca cuối cùng của nhân viên này trong tuần
            $final_count_query = "SELECT COUNT(*) as total FROM tbl_dangkylich WHERE mans = '$mans' AND ngay BETWEEN '$start_date' AND '$end_date'";
            $final_count_result = $this->db->select($final_count_query);
            $actual_final_total_shifts = $final_count_result ? $final_count_result->fetch_assoc()['total'] : $total_final_shifts;

            // (MỚI) Chuyển thành alert và thêm JS để xóa param 'submit' khỏi URL
            $alert_msg = "THÀNH CÔNG: Đã cập nhật lịch thành công (Tổng cộng {$actual_final_total_shifts} ca) cho " . htmlspecialchars($employee_name) . " - Tuần $week_num ($start_date đến $end_date).";

            // Script này sẽ hiển thị alert, sau đó xóa 'submit=...' khỏi URL để tránh alert lại khi F5
            return "<script>
                        alert('" . addslashes($alert_msg) . "');
                        if (window.history.replaceState) {
                            let url = new URL(window.location.href);
                            url.searchParams.delete('submit');
                            window.history.replaceState({path:url.href}, '', url.href);
                        }
                    </script>";
        } catch (Exception $e) {
            $this->db->link->rollback();
            // (MỚI) Chuyển thành alert
            $alert_msg = "Lỗi cập nhật lịch: " . $e->getMessage() . " Vui lòng thử lại.";
            return "<script>alert('" . addslashes($alert_msg) . "');</script>";
        }
    }


    /**
     * Xử lý check-in - Logic thời gian đã chuyển qua API Python
     * Hàm này có thể giữ lại để gọi từ PHP nếu cần, nhưng logic chính ở Python
     */
    public function check_in($mans)
    {
        // Có thể gọi API Python từ đây hoặc giữ logic PHP (ít ưu tiên hơn)
        // Hiện tại trả về thông báo yêu cầu dùng nhận diện
        return "<span class='error'>Chức năng check-in chỉ thực hiện qua giao diện nhận diện khuôn mặt.</span>";
    }

    /**
     * Xử lý check-out - Logic thời gian đã chuyển qua API Python
     */
    public function check_out($mans)
    {
        // Tương tự check-in
        return "<span class='error'>Chức năng check-out chỉ thực hiện qua giao diện nhận diện khuôn mặt.</span>";
    }

    // --- Helpers ---
    /**
     * Helper: Lấy ngày bắt đầu (Thứ 2) và kết thúc (Chủ Nhật) từ chuỗi tuần 'YYYY-Www'
     * @param string $week_string
     * @return array|false Mảng [start_date, end_date] hoặc false nếu lỗi
     */
    public function get_week_start_end($week_string)
    {
        list($year, $week_num) = $this->parse_week_string($week_string);
        if (!$year) return [false, false];
        try {
            $dto = new DateTime();
            $dto->setISODate($year, $week_num, 1); // 1 = Monday
            $start = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $end = $dto->format('Y-m-d');
            return [$start, $end];
        } catch (Exception $e) {
            return [false, false];
        }
    }

    /**
     * Helper: Tách năm và số tuần từ chuỗi 'YYYY-Www'
     * @param string $week_string
     * @return array Mảng [year, week_num] hoặc [false, false] nếu định dạng sai
     */
    public function parse_week_string($week_string)
    {
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $week_string, $matches)) {
            $year = (int)$matches[1];
            $week_num = (int)$matches[2];
            // Kiểm tra tuần hợp lệ (PHP DateTime::setISODate tự xử lý tuần 53)
            if ($week_num >= 1 && $week_num <= 53) return [$year, $week_num];
        }
        return [false, false];
    }

    /**
     * Lấy lịch sử chấm công theo tháng
     */
    /**
     * Lấy lịch sử chấm công theo tháng (Trả về mảng dữ liệu đã tính toán)
     */
    public function get_attendance_history($month, $year)
    {
        $month = (int)$month;
        $year = (int)$year;

        $query = "SELECT dk.*, 
                         ca.ten_ca, ca.gio_bat_dau, ca.gio_ket_thuc, 
                         ns.hoten
                  FROM tbl_dangkylich dk
                  JOIN tbl_ca ca ON dk.id_ca = ca.id_ca
                  JOIN nhansu ns ON dk.mans = ns.mans
                  WHERE MONTH(dk.ngay) = '$month' AND YEAR(dk.ngay) = '$year'
                  AND dk.trang_thai_cham_cong != 'Chưa chấm công'
                  ORDER BY dk.ngay DESC, ca.gio_bat_dau ASC";

        $result = $this->db->select($query);
        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // 1. Xác định Timestamp Bắt đầu & Kết thúc ca
                // Lưu ý: gio_bat_dau trong tbl_ca là TIME (H:i:s), ngay là DATE (Y-m-d)
                $shift_start = strtotime($row['ngay'] . ' ' . $row['gio_bat_dau']);
                $shift_end   = strtotime($row['ngay'] . ' ' . $row['gio_ket_thuc']);

                // 2. Xác định Timestamp Check-in & Check-out thực tế
                // gio_cham_cong trong tbl_dangkylich là DATETIME hoặc TIME. 
                // Nếu là TIME thì phải ghép với ngày. Nếu là DATETIME thì dùng luôn.
                // Để an toàn, ta kiểm tra độ dài chuỗi hoặc giả định nó là DATETIME (chuẩn thường dùng).
                // Tuy nhiên, nếu code cũ dùng date('H:i:s', strtotime(...)) thì strtotime xử lý được cả 2.
                // Nhưng để trừ nhau chính xác, ta cần đảm bảo cùng ngày.

                // Giả sử gio_cham_cong là DATETIME.
                $check_in = $row['gio_cham_cong'] ? strtotime($row['gio_cham_cong']) : null;
                $check_out = $row['gio_check_out'] ? strtotime($row['gio_check_out']) : null;

                // --- SỬ DỤNG DỮ LIỆU TỪ DB (Đã được tính bởi Python) ---
                $di_tre_phut = $row['di_tre_phut'] ? $row['di_tre_phut'] : 0;
                $tien_phat = $row['tien_phat'] ? $row['tien_phat'] : 0;

                $trang_thai_text = "Đúng giờ";
                $style_color = "green";

                // Xử lý trường hợp VẮNG
                if ($row['trang_thai_cham_cong'] == 'Vắng') {
                    $trang_thai_text = "Vắng Mặt";
                    $style_color = "red";
                    // tien_phat đã có trong DB (500k)
                }
                // Xử lý trường hợp NGHỈ CÓ PHÉP
                else if ($row['trang_thai_cham_cong'] == 'Nghỉ có phép') {
                    $trang_thai_text = "Nghỉ có phép";
                    $style_color = "#17a2b8"; // Màu xanh dương nhạt (Info color)
                }
                // Xử lý đi trễ (Dựa trên DB)
                else if ($di_tre_phut > 0) {
                    $trang_thai_text = "Đi Trễ";
                    $style_color = "red";
                }

                // --- TÍNH VỀ SỚM (Chỉ báo, không phạt tiền theo yêu cầu) ---
                $ve_som_phut = 0;
                if ($check_out && $check_out < $shift_end) {
                    $seconds_early = $shift_end - $check_out;
                    $minutes_early = ceil($seconds_early / 60);

                    // Quy định: Về sớm <= 15 phút -> Bỏ qua
                    if ($minutes_early > 15) {
                        $ve_som_phut = $minutes_early;
                        if ($trang_thai_text == "Đúng giờ") {
                            $trang_thai_text = "Về Sớm";
                            $style_color = "orange"; // Màu cam cho về sớm
                        } else {
                            $trang_thai_text .= " & Về Sớm";
                        }
                    }
                }

                // Đưa dữ liệu đã tính toán vào mảng
                $row['calculated_di_tre'] = $di_tre_phut;
                $row['calculated_tien_phat'] = $tien_phat;
                $row['calculated_ve_som'] = $ve_som_phut;
                $row['status_text'] = $trang_thai_text;
                $row['status_color'] = $style_color;

                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Lấy danh sách đơn xin nghỉ
     */
    public function get_leave_requests()
    {
        $query = "SELECT xn.*, ns.hoten, ca.ten_ca 
                  FROM tbl_xinnghi xn
                  JOIN nhansu ns ON xn.mans = ns.mans
                  JOIN tbl_ca ca ON xn.id_ca = ca.id_ca
                  ORDER BY xn.ngay_tao DESC";
        return $this->db->select($query);
    }

    /**
     * Xử lý đơn xin nghỉ (Duyệt/Từ chối)
     */
    public function process_leave_request($id, $status)
    {
        $id = (int)$id;
        $status = (int)$status;

        // 1. Cập nhật trạng thái đơn xin nghỉ
        $query = "UPDATE tbl_xinnghi SET trang_thai = '$status' WHERE id_xinnghi = '$id'";
        $result = $this->db->update($query);

        // 2. Nếu ĐÃ DUYỆT (status = 1) -> Cập nhật trạng thái trong bảng chấm công
        if ($result && $status == 1) {
            $query_get = "SELECT * FROM tbl_xinnghi WHERE id_xinnghi = '$id'";
            $req = $this->db->select($query_get)->fetch_assoc();

            if ($req) {
                $mans = $req['mans'];
                $id_ca = $req['id_ca'];
                $ngay = $req['ngay'];

                // Cập nhật: Trang thái = Nghỉ có phép, Xóa phạt (nếu có)
                $query_update = "UPDATE tbl_dangkylich 
                                 SET trang_thai_cham_cong = 'Nghỉ có phép', 
                                     tien_phat = 0, 
                                     di_tre_phut = 0 
                                 WHERE mans = '$mans' AND id_ca = '$id_ca' AND ngay = '$ngay'";
                $this->db->update($query_update);
            }
        }
        return $result;
    }

    /**
     * Lấy các ca đăng ký sắp tới của nhân viên
     */
    public function get_future_shifts_by_employee($mans, $start_date = null, $end_date = null)
    {
        $today = date('Y-m-d');
        $query = "SELECT dk.*, ca.ten_ca, ca.gio_bat_dau, ca.gio_ket_thuc 
                  FROM tbl_dangkylich dk
                  JOIN tbl_ca ca ON dk.id_ca = ca.id_ca
                  WHERE dk.mans = '$mans' AND dk.ngay >= '$today' AND dk.trang_thai_cham_cong = 'Chưa chấm công'";

        if ($start_date) {
            $query .= " AND dk.ngay >= '$start_date'";
        }
        if ($end_date) {
            $query .= " AND dk.ngay <= '$end_date'";
        }

        $query .= " ORDER BY dk.ngay ASC, ca.gio_bat_dau ASC";
        return $this->db->select($query);
    }

    /**
     * Tạo yêu cầu xin nghỉ
     */
    public function create_leave_request($id_dangky, $ly_do)
    {
        // 1. Lấy thông tin ca đăng ký
        $query_get = "SELECT * FROM tbl_dangkylich WHERE id_dangky = '$id_dangky'";
        $result = $this->db->select($query_get);

        if ($result) {
            $row = $result->fetch_assoc();
            $mans = $row['mans'];
            $id_ca = $row['id_ca'];
            $ngay = $row['ngay'];
            $ly_do = mysqli_real_escape_string($this->db->link, $ly_do);

            // 2. Insert vào tbl_xinnghi
            $query_insert = "INSERT INTO tbl_xinnghi (mans, id_ca, ngay, ly_do, trang_thai) 
                             VALUES ('$mans', '$id_ca', '$ngay', '$ly_do', 0)";
            return $this->db->insert($query_insert);
        }
        return false;
    }

    /**
     * Quét và cập nhật các ca vắng (Chưa chấm công của ngày hôm qua trở về trước)
     */
    public function scan_past_absences()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        // Cập nhật: Trang thái = Vắng, Phạt = 500k
        // Chỉ áp dụng cho các ca có ngày <= hôm qua VÀ trạng thái là 'Chưa chấm công'
        $query = "UPDATE tbl_dangkylich 
                  SET trang_thai_cham_cong = 'Vắng', tien_phat = 500000 
                  WHERE ngay <= '$yesterday' AND trang_thai_cham_cong = 'Chưa chấm công'";
        return $this->db->update($query);
    }
    public function get_week_registrations_details($week_string)
    {
        list($start_date, $end_date) = $this->get_week_start_end($week_string);
        if (!$start_date) return [];

        // Lấy thông tin: Tên, Level, Ngày, Ca
        $query = "SELECT dk.ngay, dk.id_ca, ns.hoten, dk.level
                  FROM tbl_dangkylich dk
                  JOIN nhansu ns ON dk.mans = ns.mans
                  WHERE dk.ngay BETWEEN '$start_date' AND '$end_date'
                  ORDER BY dk.level ASC, ns.hoten ASC"; // Ưu tiên xếp theo chức vụ rồi đến tên

        $result = $this->db->select($query);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ngay = $row['ngay'];
                $id_ca = $row['id_ca'];

                // Gom nhóm theo Ngày và Ca
                $data[$ngay][$id_ca][] = [
                    'hoten' => $row['hoten'],
                    'level' => $row['level']
                ];
            }
        }
        return $data;
    }
} // End class lichdangky
