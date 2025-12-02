<?php
include '../classes/lichdangky.php';
$lich = new lichdangky();

// Handle AJAX Request for Shifts (MUST BE AT THE TOP)
if (isset($_GET['ajax_shifts']) && isset($_GET['mans'])) {
    $mans = $_GET['mans'];
    $start_date = isset($_GET['start']) ? $_GET['start'] : null;
    $end_date = isset($_GET['end']) ? $_GET['end'] : null;

    $shifts = $lich->get_future_shifts_by_employee($mans, $start_date, $end_date);

    if ($shifts) {
        $current_date = '';
        echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;'>";
        while ($row = $shifts->fetch_assoc()) {
            $date = date('d/m/Y', strtotime($row['ngay']));
            $date_class = str_replace('/', '-', $date);

            // Group by Date
            if ($date != $current_date) {
                if ($current_date != '') echo "<hr style='margin: 5px 0;'>";
                echo "<div style='font-weight:bold; background:#eee; padding:5px;'>Ngày $date <label style='float:right; font-weight:normal; font-size:12px;'><input type='checkbox' onclick=\"toggleDate(this, '$date_class')\"> Chọn cả ngày</label></div>";
                $current_date = $date;
            }

            $time = date('H:i', strtotime($row['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($row['gio_ket_thuc']));
            echo "<div style='padding-left: 15px; margin-bottom: 5px;'>";
            echo "<label><input type='checkbox' name='id_dangky[]' value='" . $row['id_dangky'] . "' class='shift-checkbox date-$date_class'> " . $row['ten_ca'] . " ($time)</label>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div style='color:red; padding:10px;'>Không có ca đăng ký nào trong khoảng thời gian này.</div>";
    }
    exit; // Stop execution for AJAX
}

// Include Header and Sidebar ONLY if not AJAX
include 'inc/header.php';
include 'inc/sidebar.php';

$nhansu_list = $lich->get_all_nhansu_active();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_leave'])) {
    $mans = $_POST['mans'];
    $ids_dangky = isset($_POST['id_dangky']) ? $_POST['id_dangky'] : []; // Array of IDs
    $ly_do = $_POST['ly_do'];

    if ($mans && !empty($ids_dangky) && $ly_do) {
        $count = 0;
        foreach ($ids_dangky as $id) {
            if ($lich->create_leave_request($id, $ly_do)) {
                $count++;
            }
        }
        echo "<script>alert('Đã tạo $count yêu cầu xin nghỉ thành công!'); window.location='xinnghi_list.php';</script>";
    } else {
        echo "<script>alert('Vui lòng chọn nhân viên, ít nhất 1 ca và nhập lý do!');</script>";
    }
}
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Tạo Yêu Cầu Xin Nghỉ</h2>
        <div class="block">
            <form action="xinnghi_add.php" method="post">
                <table class="form">
                    <tr>
                        <td style="vertical-align: top;"><label>Chọn Nhân Viên:</label></td>
                        <td>
                            <select name="mans" id="mans" onchange="loadShifts()" style="width: 300px; padding: 5px;">
                                <option value="">-- Chọn nhân viên --</option>
                                <?php
                                if ($nhansu_list) {
                                    while ($ns = $nhansu_list->fetch_assoc()) {
                                        echo "<option value='" . $ns['mans'] . "'>" . $ns['hoten'] . " (ID: " . $ns['mans'] . ")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;"><label>Lọc Theo Ngày:</label></td>
                        <td>
                            Từ: <input type="date" id="start_date" onchange="loadShifts()" style="padding: 5px;">
                            Đến: <input type="date" id="end_date" onchange="loadShifts()" style="padding: 5px;">
                            <small style="color:#666;">(Để trống sẽ lấy tất cả ca sắp tới)</small>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;"><label>Chọn Ca Cần Nghỉ:</label></td>
                        <td id="shift_container">
                            <div style="padding: 10px; border: 1px dashed #ccc; color: #666;">
                                Vui lòng chọn nhân viên để xem lịch.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;"><label>Lý Do Nghỉ:</label></td>
                        <td>
                            <textarea name="ly_do" style="width: 400px; height: 100px;" required placeholder="Nhập lý do xin nghỉ..."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="submit" name="submit_leave" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Gửi Yêu Cầu</button>
                            <a href="xinnghi_list.php" style="margin-left: 10px; text-decoration: none; color: #666;">Quay lại danh sách</a>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<script>
    function loadShifts() {
        var mans = document.getElementById('mans').value;
        var start = document.getElementById('start_date').value;
        var end = document.getElementById('end_date').value;
        var container = document.getElementById('shift_container');

        if (mans) {
            container.innerHTML = "Đang tải dữ liệu...";

            var url = 'xinnghi_add.php?ajax_shifts=1&mans=' + mans;
            if (start) url += '&start=' + start;
            if (end) url += '&end=' + end;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = "Lỗi tải dữ liệu";
                });
        } else {
            container.innerHTML = "<div style='padding: 10px; border: 1px dashed #ccc; color: #666;'>Vui lòng chọn nhân viên để xem lịch.</div>";
        }
    }

    function toggleDate(source, date) {
        var checkboxes = document.querySelectorAll('.date-' + date);
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
</script>

<?php include 'inc/footer.php'; ?>