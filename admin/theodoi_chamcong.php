<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/lichdangky.php';

$lich = new lichdangky();

// Default to current month/year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$attendance_list = $lich->get_attendance_history($month, $year);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['scan_absences'])) {
    $count = $lich->scan_past_absences();
    if ($count !== false) {
        echo "<script>alert('Đã quét thành công! Cập nhật $count ca vắng.'); window.location='theodoi_chamcong.php?month=$month&year=$year';</script>";
    } else {
        echo "<script>alert('Lỗi khi quét!');</script>";
    }
}
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Theo Dõi Chấm Công - Tháng <?php echo "$month/$year"; ?></h2>
        <div class="block">
            <form method="get" action="theodoi_chamcong.php" style="margin-bottom: 20px;">
                <label>Chọn Tháng:</label>
                <select name="month">
                    <?php
                    for ($i = 1; $i <= 12; $i++) {
                        $selected = ($i == $month) ? 'selected' : '';
                        echo "<option value='$i' $selected>Tháng $i</option>";
                    }
                    ?>
                </select>
                <select name="year">
                    <?php
                    $current_year = date('Y');
                    for ($i = $current_year; $i >= $current_year - 2; $i--) {
                        $selected = ($i == $year) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
                <button type="submit" style="padding: 5px 10px; cursor: pointer;">Xem</button>
            </form>

            <form method="post" action="theodoi_chamcong.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" style="display:inline-block; float:right;" onsubmit="return confirm('Bạn có chắc muốn quét các ca chưa chấm công trong quá khứ thành Vắng? Hành động này sẽ phạt 500k/ca.');">
                <button type="submit" name="scan_absences" style="padding: 5px 10px; cursor: pointer; background: #dc3545; color: white; border: none;">Quét Vắng & Phạt</button>
            </form>
            <div style="clear:both;"></div>

            <table class="data display datatable" id="example">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Ca Làm Việc</th>
                        <th>Nhân Viên</th>
                        <th>Giờ Vào (Check-in)</th>
                        <th>Giờ Ra (Check-out)</th>
                        <th>Trạng Thái</th>
                        <th>Đi Trễ (Phút)</th>
                        <th>Phạt (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($attendance_list)) {
                        foreach ($attendance_list as $row) {
                            $di_tre = $row['calculated_di_tre'];
                            $tien_phat = $row['calculated_tien_phat'];
                            $ve_som = $row['calculated_ve_som'];
                            $status_text = $row['status_text'];
                            $status_color = $row['status_color'];

                            echo "<tr class='odd gradeX'>";
                            echo "<td>" . date('d/m/Y', strtotime($row['ngay'])) . "</td>";
                            echo "<td>" . $row['ten_ca'] . " (" . date('H:i', strtotime($row['gio_bat_dau'])) . " - " . date('H:i', strtotime($row['gio_ket_thuc'])) . ")</td>";
                            echo "<td>" . $row['hoten'] . "</td>";

                            // Check-in Time
                            $checkin_time = $row['gio_cham_cong'] ? date('H:i:s', strtotime($row['gio_cham_cong'])) : '-';
                            echo "<td style='font-weight:bold; color:#007bff;'>$checkin_time</td>";

                            // Check-out Time
                            $checkout_time = $row['gio_check_out'] ? date('H:i:s', strtotime($row['gio_check_out'])) : '-';
                            echo "<td>$checkout_time</td>";

                            // Status & Late Info
                            echo "<td style='color:$status_color; font-weight:bold;'>$status_text</td>";

                            if ($di_tre > 0) {
                                echo "<td style='color:red; font-weight:bold;'>$di_tre phút</td>";
                                echo "<td style='color:red; font-weight:bold;'>" . number_format($tien_phat) . "</td>";
                            } else {
                                echo "<td>0</td>";
                                echo "<td>0</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        setupLeftMenu();
        $('.datatable').dataTable();
        setSidebarHeight();
    });
</script>

<?php include 'inc/footer.php'; ?>