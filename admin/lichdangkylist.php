<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/lichdangky.php';

$lich = new lichdangky();
$nhansu_list = $lich->get_all_nhansu_active();
$ca_list_result = $lich->get_all_ca();
$ca_data = [];
if ($ca_list_result) {
    while ($row = $ca_list_result->fetch_assoc()) $ca_data[$row['id_ca']] = $row;
}

$selected_week_string = date('Y-\WW');
if (isset($_GET['week']) && preg_match('/^(\d{4})-W(\d{1,2})$/', $_GET['week'])) $selected_week_string = $_GET['week'];
$filter_mans = isset($_GET['mans']) ? (int)$_GET['mans'] : null;

list($year, $week_num) = $lich->parse_week_string($selected_week_string);
$days_of_week = [];
$error_msg = '';
if ($year) {
    try {
        $dto = new DateTime();
        $dto->setISODate($year, $week_num, 1);
        for ($i = 0; $i < 7; $i++) {
            $days_of_week[] = ['date' => $dto->format('Y-m-d'), 'day_name' => $dto->format('l')];
            $dto->modify('+1 day');
        }
    } catch (Exception $e) {
        $error_msg = "Lỗi tính ngày.";
    }
} else {
    $error_msg = "Tuần không hợp lệ.";
}

$schedule_data = [];
if (!$error_msg && !empty($ca_data)) {
    // Hàm này trong class lichdangky đã được sửa ở bước trước để trả về cả 'level'
    $schedule_data = $lich->get_registered_schedule_with_status($selected_week_string, $filter_mans);
} elseif (empty($ca_data)) {
    $error_msg = "Lỗi: Thiếu thông tin ca.";
}

$thu_viet = ['Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'];

// (MỚI) Định nghĩa tên chức vụ để hiển thị
$role_names = [
    0 => 'Admin',
    1 => 'Kế toán',
    2 => 'Quầy',
    3 => 'Bếp',
    4 => 'Phục vụ'
];

$prev_week_string = '';
$next_week_string = '';
if ($year) {
    try {
        $currentDto = new DateTime();
        $currentDto->setISODate($year, $week_num, 1);
        $prevDto = clone $currentDto;
        $prevDto->modify('-7 days');
        $prev_week_string = $prevDto->format('Y-\WW');
        $nextDto = clone $currentDto;
        $nextDto->modify('+7 days');
        $next_week_string = $nextDto->format('Y-\WW');
    } catch (Exception $e) {
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<style>
    .table-wrapper {
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-top: 20px;
        overflow-x: auto;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5em;
        display: inline-block;
        width: auto;
        padding: 6px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .dataTables_wrapper .dataTables_length select {
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

    .schedule-view-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 900px;
    }

    .schedule-view-table th,
    .schedule-view-table td {
        border: 1px solid #e0e0e0;
        padding: 10px 8px;
        text-align: center;
        vertical-align: top;
    }

    .schedule-view-table thead th {
        background-color: #f1f3f5;
        color: #343a40;
        font-weight: 600;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .schedule-view-table tbody td.day-header {
        background-color: #f8f9fa;
        font-weight: 600;
        text-align: left;
        padding-left: 15px;
        vertical-align: middle;
        min-width: 120px;
        position: sticky;
        left: 0;
        z-index: 1;
    }

    .schedule-view-table tbody td ul {
        list-style: none;
        padding: 0;
        margin: 0;
        text-align: left;
    }

    .schedule-view-table tbody td ul li {
        padding: 6px 0;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-bottom: 1px dotted #eee;
    }

    .schedule-view-table tbody td ul li:last-child {
        border-bottom: none;
    }

    .schedule-view-table tbody td.no-shift {
        color: #adb5bd;
        font-style: italic;
        font-size: 11px;
        vertical-align: middle;
    }

    .shift-header-view {
        font-size: 13px;
        font-weight: 600;
    }

    .shift-time-view {
        font-size: 11px;
        color: #6c757d;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        align-items: flex-end;
        border: 1px solid #dee2e6;
    }

    .filter-section label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #495057;
        font-size: 14px;
    }

    .filter-section select,
    .filter-section input[type="week"] {
        padding: 8px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-section button {
        padding: 8px 15px;
        font-size: 14px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .filter-section button:hover {
        background-color: #0056b3;
    }

    .week-navigation {
        text-align: center;
        margin-bottom: 15px;
    }

    .week-navigation a {
        text-decoration: none;
        padding: 8px 15px;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 4px;
        color: #495057;
        margin: 0 5px;
        font-weight: 500;
        transition: background-color 0.2s;
    }

    .week-navigation a:hover {
        background-color: #dee2e6;
    }

    .week-navigation span {
        font-weight: 600;
        font-size: 16px;
        margin: 0 15px;
        vertical-align: middle;
    }

    .error {
        color: red;
        text-align: center;
        margin: 15px 0;
        background-color: #fdd;
        padding: 10px;
        border: 1px solid red;
        border-radius: 5px;
    }

    /* CSS Trạng thái */
    .status-chua-cham-cong {
        color: #888;
        font-style: italic;
        font-size: 11px;
    }

    .status-da-check-in {
        color: #ff7f50;
        font-weight: bold;
        font-size: 11px;
    }

    .status-da-hoan-thanh {
        color: #28a745;
        font-weight: bold;
        font-size: 11px;
    }

    .status-vang {
        color: #dc3545;
        font-weight: bold;
        font-size: 11px;
    }

    .check-times {
        font-size: 10px;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }

    /* (MỚI) CSS hiển thị chức vụ */
    .role-badge {
        display: inline-block;
        padding: 1px 4px;
        background-color: #e9ecef;
        color: #495057;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 600;
        margin-right: 4px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="grid_10">
    <div class="box round first grid" id="table-container">
        <h2>Xem Lịch Làm Việc Đã Đăng Ký (Kèm Chấm Công)</h2>
        <?php if ($error_msg) echo "<p class='error'>$error_msg</p>"; ?>

        <div class="filter-section">
            <form action="lichdangkylist.php" method="get" style="display: flex; gap: 20px; align-items: flex-end; width: 100%;">
                <div style="flex: 1;"><label for="week">Chọn Tuần:</label><input type="week" name="week" id="week" value="<?php echo $selected_week_string; ?>" required></div>
                <div style="flex: 2;"><label for="mans">Lọc theo nhân viên:</label><select name="mans" id="mans">
                        <option value="">-- Tất cả --</option><?php if ($nhansu_list): mysqli_data_seek($nhansu_list, 0);
                                                                    while ($ns = $nhansu_list->fetch_assoc()): ?><option value="<?php echo $ns['mans']; ?>" <?php echo ($filter_mans == $ns['mans']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ns['hoten']) . ' (ID: ' . $ns['mans'] . ')'; ?></option><?php endwhile;
                                                                                                                                                                                                                                                                                                                                                                endif; ?>
                    </select></div>
                <div><button type="submit">Xem Lịch</button></div>
            </form>
        </div>

        <?php if ($year): ?>
            <div class="week-navigation">
                <?php $prev_link = "lichdangkylist.php?week=$prev_week_string" . ($filter_mans ? "&mans=$filter_mans" : "");
                $next_link = "lichdangkylist.php?week=$next_week_string" . ($filter_mans ? "&mans=$filter_mans" : ""); ?>
                <a href="<?php echo $prev_link; ?>">&laquo; Tuần trước</a>
                <span>Tuần <?php echo $week_num; ?> (<?php echo date('d/m', strtotime($days_of_week[0]['date'])); ?> - <?php echo date('d/m/Y', strtotime($days_of_week[6]['date'])); ?>)</span>
                <a href="<?php echo $next_link; ?>">Tuần sau &raquo;</a>
            </div>
        <?php endif; ?>

        <div class="block">
            <div class="table-wrapper">
                <?php if (empty($ca_data) && !$error_msg): echo "<p class='error'>Lỗi: Thiếu thông tin ca.</p>";
                else: ?>
                    <table class="schedule-view-table display" id="scheduleTable">
                        <thead>
                            <tr>
                                <th style="position: sticky; left: 0; z-index: 2; background-color: #f1f3f5;">Ngày</th><?php foreach ($ca_data as $ca): ?><th>
                                        <div class="shift-header-view"><?php echo htmlspecialchars($ca['ten_ca']); ?></div>
                                        <div class="shift-time-view">(<?php echo date('H:i', strtotime($ca['gio_bat_dau'])); ?>-<?php echo date('H:i', strtotime($ca['gio_ket_thuc'])); ?>)</div>
                                    </th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($days_of_week)): ?>
                                <?php foreach ($days_of_week as $day): $current_date = $day['date']; ?>
                                    <tr>
                                        <td class="day-header"><?php echo $thu_viet[$day['day_name']]; ?><br><small><?php echo date('d/m', strtotime($current_date)); ?></small></td>
                                        <?php foreach ($ca_data as $ca_id => $ca_info): ?>
                                            <td>
                                                <?php if (isset($schedule_data[$current_date][$ca_id]['nhan_vien_dang_ky']) && !empty($schedule_data[$current_date][$ca_id]['nhan_vien_dang_ky'])):
                                                    echo '<ul>';
                                                    foreach ($schedule_data[$current_date][$ca_id]['nhan_vien_dang_ky'] as $nv_dang_ky):
                                                        echo '<li>';

                                                        // --- XỬ LÝ HIỂN THỊ CHỨC VỤ ---
                                                        $lvl = isset($nv_dang_ky['level']) ? $nv_dang_ky['level'] : 4; // Mặc định là Phục vụ nếu lỗi
                                                        $role_txt = isset($role_names[$lvl]) ? $role_names[$lvl] : "Lvl $lvl";
                                                        echo "<span class='role-badge'>$role_txt</span>";
                                                        // -------------------------------

                                                        echo htmlspecialchars($nv_dang_ky['hoten']);

                                                        $status_text = htmlspecialchars($nv_dang_ky['trang_thai_cham_cong'] ?? 'Chưa chấm công');
                                                        $status_class_base = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $status_text)));
                                                        $status_class = 'status-' . $status_class_base;
                                                        echo "<br><span class='$status_class'>($status_text)</span>";

                                                        $check_in_time = $nv_dang_ky['gio_cham_cong'] ? date('H:i', strtotime($nv_dang_ky['gio_cham_cong'])) : '--:--';
                                                        $check_out_time = $nv_dang_ky['gio_check_out'] ? date('H:i', strtotime($nv_dang_ky['gio_check_out'])) : '--:--';

                                                        if ($nv_dang_ky['gio_cham_cong'] || $nv_dang_ky['gio_check_out'])
                                                            echo "<span class='check-times'>In: $check_in_time | Out: $check_out_time</span>";

                                                        echo '</li>';
                                                    endforeach;
                                                    echo '</ul>';
                                                else:
                                                    echo '<span class="no-shift">(Trống)</span>';
                                                endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?><tr>
                                    <td colspan="<?php echo count($ca_data) + 1; ?>" style="text-align: center; padding: 20px;">Không có dữ liệu ngày.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<?php include 'inc/footer.php'; ?>