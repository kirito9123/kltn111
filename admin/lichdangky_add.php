<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/lichdangky.php';

$lich = new lichdangky();
$nhansu_list = $lich->get_all_nhansu_active(); // Hàm này đã được cập nhật để lấy cả level
$ca_list_result = $lich->get_all_ca();
$ca_data = [];
if ($ca_list_result) {
    while ($row = $ca_list_result->fetch_assoc()) { $ca_data[$row['id_ca']] = $row; }
}

$notification = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $notification = $lich->register_shifts($_POST); // Hàm này đã có validation mới
}

// Lấy tuần và mans từ request (giữ nguyên)
$selected_week_string = date('Y-\WW');
if (isset($_REQUEST['week']) && preg_match('/^(\d{4})-W(\d{1,2})$/', $_REQUEST['week'])) {
     $selected_week_string = $_REQUEST['week'];
}
$selected_mans = isset($_REQUEST['mans']) ? (int)$_REQUEST['mans'] : null;

// Lấy ngày trong tuần (giữ nguyên)
list($year, $week_num) = $lich->parse_week_string($selected_week_string);
$days_of_week = [];
$error_msg_date = '';
if ($year) {
    try {
        $dto = new DateTime(); $dto->setISODate($year, $week_num, 1);
        for ($i = 0; $i < 7; $i++) {
            $days_of_week[] = ['date' => $dto->format('Y-m-d'), 'day_name' => $dto->format('l')];
            $dto->modify('+1 day');
        }
    } catch (Exception $e) { $error_msg_date = "<span class='error'>Lỗi tính ngày.</span>"; }
} else { $error_msg_date = "<span class='error'>Tuần không hợp lệ.</span>"; }
if($error_msg_date && empty($notification)) $notification = $error_msg_date;

// Lấy trạng thái ca đã đăng ký (giữ nguyên)
$registered_shifts_status = [];
if ($selected_mans && !empty($days_of_week)) {
     $schedule_for_employee = $lich->get_registered_schedule_with_status($selected_week_string, $selected_mans);
     foreach($schedule_for_employee as $ngay => $cas) {
         if (is_array($cas)){ // Thêm kiểm tra is_array
            foreach($cas as $id_ca => $details) {
                if (!empty($details['nhan_vien_dang_ky'])) {
                    $nv_info = $details['nhan_vien_dang_ky'][0]; // Lấy nhân viên đầu tiên (vì đang lọc theo 1 mans)
                    $registered_shifts_status[$ngay][$id_ca] = $nv_info['trang_thai_cham_cong'];
                }
            }
         }
     }
}
// Mảng dịch tên Thứ (giữ nguyên)
$thu_viet = [
    'Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư',
    'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'
];
?>

<style>
    /* CSS giữ nguyên, chỉ sửa đổi phần liên quan đến số ca tối thiểu nếu cần */
    .total-counter.below-min {
        color: #c0392b !important;
        border-color: #e74c3c !important;
        background-color: #fdecea !important;
    }
    .total-counter.met-min {
         color: #27ae60 !important;
         border-color: #2ecc71 !important;
         background-color: #eafaf1 !important;
    }
     /* ... Các style khác giữ nguyên ... */
    .form-wrapper { max-width: 1100px; margin: 30px auto; padding: 25px 35px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1); font-family: 'Segoe UI', sans-serif; }
    .form-wrapper h2 { text-align: center; margin-bottom: 30px; font-size: 26px; color: #34495e; border-bottom: 1px solid #ecf0f1; padding-bottom: 15px; font-weight: 600; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #34495e; font-size: 15px; }
    input[type="text"], input[type="file"], input[type="week"], select { width: 100%; padding: 10px 12px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 14px; transition: border-color 0.3s, box-shadow 0.3s; background-color: #fdfdfd; }
    input:focus, select:focus { border-color: #3498db; box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2); outline: none; }
    .form-actions input[type="submit"] { background-color: #2ecc71; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background-color 0.3s, transform 0.1s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .form-actions input[type="submit"]:hover { background-color: #27ae60; transform: translateY(-1px); }
    .note { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
    .error, .success { display: block; padding: 12px 15px; margin-bottom: 20px; border-radius: 6px; font-weight: 500; text-align: center; font-size: 14px; border: 1px solid transparent; }
    .error { background-color: #fdecea; color: #c0392b; border-color: #e74c3c; text-align: left; } /* Align left for multi-line errors */
    .success { background-color: #eafaf1; color: #27ae60; border-color: #2ecc71; }
    .schedule-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-radius: 6px; overflow: hidden; }
    .schedule-table th, .schedule-table td { border: 1px solid #e0e0e0; padding: 10px 8px; text-align: center; vertical-align: middle; position: relative; }
    .schedule-table thead th { background-color: #f8f9fa; color: #495057; font-weight: 600; white-space: nowrap; }
    .schedule-table tbody td:first-child { background-color: #f8f9fa; font-weight: 600; text-align: left; padding-left: 15px; min-width: 120px; }
    .schedule-table tbody tr:nth-child(even) { background-color: #fdfdfd; }
    .schedule-table input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; vertical-align: middle; margin: 0; }
    .shift-header { font-size: 13px; font-weight: 600; }
    .shift-time { font-size: 11px; color: #6c757d; }
    .total-counter { margin-top: 15px; padding: 10px; font-size: 16px; font-weight: 600; border-radius: 5px; text-align: center; border: 1px dashed; } /* Remove default colors */
    .week-nav { display: inline-block; margin-left: 10px; text-decoration: none; padding: 5px 10px; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; color: #333; font-size: 14px; }
    .week-nav:hover { background-color: #e0e0e0; }
    .form-actions { text-align: center; margin-top: 30px; }
    .readonly-shift { background-color: #e9ecef; cursor: not-allowed; opacity: 0.7; }
    .readonly-shift input[type="checkbox"] { pointer-events: none; }
    .status-tooltip { position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); background-color: rgba(0,0,0,0.7); color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px; white-space: nowrap; opacity: 0; visibility: hidden; transition: opacity 0.2s, visibility 0.2s; z-index: 10; }
    td:hover .status-tooltip { opacity: 1; visibility: visible; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Đăng Ký Lịch Làm Việc Tuần</h2>
            <?php if ($notification) echo $notification; ?>

            <form action="lichdangky_add.php" method="get" id="filter-form">
                 <div class="form-group" style="display: flex; gap: 20px; align-items: flex-end;">
                    <div style="flex: 2;">
                        <label for="mans">Chọn Nhân Viên (*)</label>
                        <select name="mans" id="mans" required onchange="this.form.submit();">
                            <option value="">-- Chọn nhân viên --</option>
                            <?php if ($nhansu_list):
                                $selected_nhansu_name = "Nhân viên ID " . $selected_mans;
                                mysqli_data_seek($nhansu_list, 0); // Reset pointer
                                while ($ns = $nhansu_list->fetch_assoc()):
                                    if ($selected_mans == $ns['mans']) $selected_nhansu_name = htmlspecialchars($ns['hoten']);
                            ?>
                                <option value="<?php echo $ns['mans']; ?>" <?php echo ($selected_mans == $ns['mans']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ns['hoten']) . ' (ID: ' . $ns['mans'] . ', Lvl: ' . $ns['level'] . ')'; // Hiển thị cả level ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="week">Chọn Tuần (*)</label>
                        <input type="week" name="week" id="week" value="<?php echo $selected_week_string; ?>" required onchange="this.form.submit();">
                    </div>
                 </div>
            </form>
            <hr style="margin: 25px 0;">

            <?php if ($selected_mans && !empty($days_of_week)): ?>
            <form action="lichdangky_add.php?week=<?php echo $selected_week_string; ?>&mans=<?php echo $selected_mans; ?>" method="post" id="schedule-form">
                <input type="hidden" name="mans" value="<?php echo $selected_mans; ?>">
                <input type="hidden" name="week" value="<?php echo $selected_week_string; ?>">
                <div class="form-group">
                    <label style="font-size: 16px; margin-bottom: 10px;">
                        Chọn Ca Làm Việc cho: <span style="color: #2980b9;"><?php echo $selected_nhansu_name; ?></span>
                         - Tuần <span style="color: #2980b9;"><?php echo $week_num; ?></span>
                         (<?php echo date('d/m', strtotime($days_of_week[0]['date'])); ?> - <?php echo date('d/m/Y', strtotime($days_of_week[6]['date'])); ?>)
                    </label>
                     <!-- SỬA SỐ CA TỐI THIỂU -->
                     <p class="note" style="color: #c0392b; font-weight: 500;">Yêu cầu tối thiểu 10 ca/tuần. Tối đa 2 ca 1 ngày. Ca đã chấm công không thể sửa.</p>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <?php foreach ($ca_data as $ca): ?>
                                    <th>
                                        <div class="shift-header"><?php echo htmlspecialchars($ca['ten_ca']); ?></div>
                                        <div class="shift-time">(<?php echo date('H:i', strtotime($ca['gio_bat_dau'])); ?>-<?php echo date('H:i', strtotime($ca['gio_ket_thuc'])); ?>)</div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days_of_week as $day): $current_date = $day['date']; ?>
                            <tr>
                                <td><?php echo $thu_viet[$day['day_name']]; ?><br><small><?php echo date('d/m', strtotime($current_date)); ?></small></td>
                                <?php foreach ($ca_data as $ca_id => $ca_info):
                                    $checkbox_name = "shifts[$current_date][$ca_id]";
                                    $is_checked = isset($registered_shifts_status[$current_date][$ca_id]);
                                    $status = $is_checked ? $registered_shifts_status[$current_date][$ca_id] : 'Chưa đăng ký';
                                    $is_readonly = ($status != 'Chưa chấm công' && $status != 'Chưa đăng ký'); // Không cho sửa nếu đã check-in/hoàn thành/vắng
                                    $td_class = $is_readonly ? 'readonly-shift' : '';
                                ?>
                                <td class="<?php echo $td_class; ?>">
                                    <input type="checkbox" class="shift-check" name="<?php echo $checkbox_name; ?>" <?php echo $is_checked ? 'checked' : ''; ?> <?php echo $is_readonly ? 'disabled' : ''; ?>>
                                    <?php if ($is_readonly): ?><span class="status-tooltip"><?php echo htmlspecialchars($status); ?></span><?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- SỬA SỐ CA TỐI THIỂU TRONG JS -->
                    <div id="total-counter" class="total-counter">Tổng số ca đã đăng ký: 0</div>
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu Thay Đổi Lịch" />
                </div>
            </form>
            <?php else: ?>
                <p class="note" style="text-align: center; font-size: 14px; margin-top: 30px;">Vui lòng chọn nhân viên và tuần.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkboxes = document.querySelectorAll('.shift-check:not([disabled])');
    const counter = document.getElementById('total-counter');
    const minShifts = 10; // *** SỬA SỐ CA TỐI THIỂU Ở ĐÂY ***

    function updateCount() {
        if (!counter) return;
        const checkedAndEnabledCount = document.querySelectorAll('.shift-check:checked:not([disabled])').length;
        const checkedAndDisabledCount = document.querySelectorAll('.shift-check:checked[disabled]').length;
        const totalCheckedCount = checkedAndEnabledCount + checkedAndDisabledCount;
        counter.textContent = `Tổng số ca đã đăng ký (bao gồm đã chấm công): ${totalCheckedCount}`;

        // Cập nhật class dựa trên số ca tối thiểu
        counter.classList.remove('below-min', 'met-min'); // Xóa class cũ
        if (totalCheckedCount < minShifts) {
            counter.classList.add('below-min');
        } else {
            counter.classList.add('met-min');
        }
    }
    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    if (document.querySelectorAll('.shift-check').length > 0) updateCount(); // Cập nhật khi tải trang
});
</script>

<?php include 'inc/footer.php'; ?>
