<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/nguyenvatlieu.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Kiểm tra quyền
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập trang này!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$nl  = new nguyenvatlieu();
$msg = '';

// Món đang được chọn (nếu có)
$id_mon_selected = isset($_GET['id_mon']) ? (int)$_GET['id_mon'] : 0;

// Thông tin món đang chọn (để hiển thị tên ở ô chọn món)
$mon_info = null;
if ($id_mon_selected > 0) {
    $rsMon = $nl->get_monan($id_mon_selected);
    if ($rsMon) {
        $mon_info = $rsMon->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

    $id_mon       = isset($_POST['id_mon']) ? (int)$_POST['id_mon'] : 0;
    $id_nl_arr    = $_POST['id_nl']    ?? [];
    $so_luong_arr = $_POST['so_luong'] ?? [];
    // [FIX] Lấy thêm mảng đơn vị tính từ form
    $id_dvt_arr   = $_POST['id_dvt']   ?? [];

    // để khi submit lỗi vẫn giữ lại món đã chọn
    $id_mon_selected = $id_mon;

    if ($id_mon <= 0 || empty($id_nl_arr)) {
        $msg = "<span style='color:red;'>Vui lòng chọn món và ít nhất 1 nguyên liệu.</span>";
    } else {
        // [FIX] Truyền đủ 4 tham số vào hàm save_congthuc_mon (thêm $id_dvt_arr)
        $ok = $nl->save_congthuc_mon($id_mon, $id_nl_arr, $so_luong_arr, $id_dvt_arr);

        if ($ok) {
            echo "<script>
                    alert('Lưu công thức cho món thành công!');
                    window.location = 'congthuc_list.php';
                  </script>";
            exit();
        } else {
            $msg = "<span style='color:red;'>Có lỗi khi lưu công thức, vui lòng thử lại.</span>";
        }
    }
}

/* LẤY DANH SÁCH MÓN, LOẠI, NGUYÊN LIỆU */
$list_monan_rs = $nl->show_monan();
$ds_monan      = [];
if ($list_monan_rs) {
    while ($row = $list_monan_rs->fetch_assoc()) {
        $ds_monan[] = $row;
    }
}

$list_loai_rs = $nl->show_loai_monan();
$loai_arr     = [];
if ($list_loai_rs) {
    while ($row_l = $list_loai_rs->fetch_assoc()) {
        $loai_arr[$row_l['id_loai']] = $row_l['name_loai'];
    }
}

$list_nl_rs = $nl->show_nguyen_lieu();
$ds_nguyen_lieu = [];
if ($list_nl_rs) {
    while ($row_nl = $list_nl_rs->fetch_assoc()) {
        $ds_nguyen_lieu[] = $row_nl;
    }
}

// [MỚI] Lấy danh sách Đơn Vị Tính để đổ vào Select Box
$list_dvt_rs = $nl->show_don_vi_tinh();
$ds_dvt = [];
if ($list_dvt_rs) {
    while ($row_dvt = $list_dvt_rs->fetch_assoc()) {
        $ds_dvt[] = $row_dvt;
    }
}

/* LẤY CÔNG THỨC HIỆN TẠI CỦA MÓN (NẾU ĐÃ CHỌN MÓN) */
$congthuc_rows = [];
if ($id_mon_selected > 0) {
    $rs_ct = $nl->get_congthuc_by_mon($id_mon_selected);
    if ($rs_ct) {
        while ($row_ct = $rs_ct->fetch_assoc()) {
            $congthuc_rows[] = $row_ct;
        }
    }
}

// Tên món đang chọn (để hiển thị ở ô selector)
$ten_mon_selected = $mon_info ? $mon_info['name_mon'] : '-- Chọn món --';

?>

<style>
    .form-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 30px 40px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        font-family: 'Segoe UI', sans-serif;
    }
    .form-wrapper h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 26px;
        color: #2c3e50;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-group select,
    .form-group input[type="number"],
    .form-group input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 15px;
        transition: border 0.2s ease;
        background-color: #fff;
    }
    .form-group select:focus,
    .form-group input:focus {
        border-color: #007bff;
        outline: none;
    }
    .form-actions {
        text-align: center;
        margin-top: 25px;
    }
    .btn-main {
        background-color: #007bff;
        color: white;
        padding: 10px 30px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-right: 10px;
    }
    .btn-main:hover {
        background-color: #0056b3;
    }
    .btn-back {
        background-color: #6c757d;
        color: white;
        padding: 10px 22px;
        font-size: 15px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .btn-back:hover {
        background-color: #5a6268;
    }
    table.ct-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    table.ct-table th,
    table.ct-table td {
        border: 1px solid #e1e1e1;
        padding: 6px 8px;
        text-align: left;
        vertical-align: middle;
    }
    table.ct-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    table.ct-table select,
    table.ct-table input[type="number"],
    table.ct-table input[type="text"] {
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
        border: 1px solid #ccc;
        padding: 6px 8px;
        margin: 0;
    }
    table.ct-table .btn-row {
        display: inline-block;
        width: 100%;
        text-align: center;
        padding: 6px 0;
    }
    .btn-row {
        padding: 6px 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-add-row {
        background: #28a745;
        color: #fff;
        margin-top: 10px;
    }
    .btn-remove-row {
        background: #dc3545;
        color: #fff;
    }

    /* Custom selector món */
    .mon-selector {
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        background-color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .mon-selector-arrow {
        font-size: 12px;
    }
    .mon-panel {
        border: 1px solid #ccc;
        border-radius: 8px;
        margin-top: 6px;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        display: none;
        position: relative;
        z-index: 100;
    }
    .mon-panel-header {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }
    .mon-panel-header input,
    .mon-panel-header select {
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    .mon-panel-header input {
        flex: 2;
    }
    .mon-panel-header select {
        flex: 1;
    }
    .mon-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        max-height: 260px;
        overflow-y: auto;
    }
    .mon-item {
        border: 1px solid #e1e1e1;
        border-radius: 6px;
        padding: 6px 8px;
        font-size: 14px;
        cursor: pointer;
        background-color: #fafafa;
    }
    .mon-item:hover {
        background-color: #e9f5ff;
        border-color: #007bff;
    }
    .mon-item.active {
        background-color: #e9f5ff;
        border-color: #007bff;
    }
    .mon-item-name {
        font-weight: 600;
        margin-bottom: 3px;
    }
    .mon-item-loai {
        font-size: 12px;
        color: #666;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Thêm / sửa công thức cho món</h2>

            <form action="congthuc_add.php<?php echo $id_mon_selected > 0 ? '?id_mon='.$id_mon_selected : ''; ?>" method="post">
                <div class="form-group">
                    <label>Chọn món</label>

                    <input type="hidden" name="id_mon" id="id_mon" value="<?php echo $id_mon_selected > 0 ? $id_mon_selected : ''; ?>">

                    <div id="mon-selector" class="mon-selector">
                        <span id="mon-selector-text"><?php echo htmlspecialchars($ten_mon_selected, ENT_QUOTES); ?></span>
                        <span class="mon-selector-arrow">&#9662;</span>
                    </div>

                    <div id="mon-panel" class="mon-panel">
                        <div class="mon-panel-header">
                            <input type="text" id="mon-search" placeholder="Tìm theo tên món...">

                            <select id="mon-loai-filter">
                                <option value="">Tất cả loại</option>
                                <?php
                                foreach ($loai_arr as $id_loai => $name_loai) {
                                    echo '<option value="'.$id_loai.'">'.$name_loai.'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mon-grid">
                            <?php
                            foreach ($ds_monan as $row_m) {
                                $id_loai = (int)$row_m['id_loai'];
                                $ten_loai = isset($loai_arr[$id_loai]) ? $loai_arr[$id_loai] : '';
                                $activeClass = ($id_mon_selected == $row_m['id_mon']) ? ' mon-item active' : ' mon-item';
                                ?>
                                <div class="<?php echo $activeClass; ?>"
                                     data-id="<?php echo $row_m['id_mon']; ?>"
                                     data-name="<?php echo htmlspecialchars($row_m['name_mon'], ENT_QUOTES); ?>"
                                     data-idloai="<?php echo $id_loai; ?>">
                                    <div class="mon-item-name"><?php echo $row_m['name_mon']; ?></div>
                                    <?php if ($ten_loai) { ?>
                                        <div class="mon-item-loai"><?php echo $ten_loai; ?></div>
                                    <?php } ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nguyên liệu &amp; số lượng</label>

                    <table class="ct-table">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Nguyên liệu</th>
                                <th style="width: 25%;">Số lượng</th>
                                <th style="width: 15%;">Đơn vị</th>
                                <th style="width: 15%;">Xử lý</th>
                            </tr>
                        </thead>
                        <tbody id="ct-body">
                        <?php
                        if (!empty($congthuc_rows)) {
                            // Đã có công thức -> in theo DB
                            foreach ($congthuc_rows as $row_ct) {
                                $id_nl_ct    = (int)$row_ct['id_nl'];
                                $so_luong_ct = (float)$row_ct['so_luong'];
                                // Lấy ID ĐVT đã lưu
                                $id_dvt_ct   = isset($row_ct['id_dvt']) ? (int)$row_ct['id_dvt'] : 0;
                                ?>
                                <tr>
                                    <td>
                                        <select name="id_nl[]" required>
                                            <option value="">-- Chọn nguyên liệu --</option>
                                            <?php
                                            foreach ($ds_nguyen_lieu as $nl_info) {
                                                $sel_nl = ($nl_info['id_nl'] == $id_nl_ct) ? 'selected' : '';
                                                // Lưu ID đơn vị mặc định của NL vào data-dvt-default (nếu có)
                                                $dvt_default = isset($nl_info['id_dvt']) ? $nl_info['id_dvt'] : '';
                                                echo '<option value="'.$nl_info['id_nl'].'" '.$sel_nl.' data-dvt-default="'.$dvt_default.'">'.$nl_info['ten_nl'].'</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="so_luong[]" min="0" step="0.001" required
                                               value="<?php echo $so_luong_ct; ?>">
                                    </td>
                                    <td>
                                        <select name="id_dvt[]" required>
                                            <?php
                                            foreach ($ds_dvt as $dvt) {
                                                // Nếu trong DB đã lưu id_dvt này thì chọn
                                                $sel_dvt = ($dvt['id_dvt'] == $id_dvt_ct) ? 'selected' : '';
                                                echo '<option value="'.$dvt['id_dvt'].'" '.$sel_dvt.'>'.$dvt['ten_dvt'].'</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-row btn-remove-row">Xóa</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            // Chưa có công thức -> 1 dòng trống mặc định
                            ?>
                            <tr>
                                <td>
                                    <select name="id_nl[]" required>
                                        <option value="">-- Chọn nguyên liệu --</option>
                                        <?php
                                        foreach ($ds_nguyen_lieu as $row_nl) {
                                            $dvt_default = isset($row_nl['id_dvt']) ? $row_nl['id_dvt'] : '';
                                            echo '<option value="'.$row_nl['id_nl'].'" data-dvt-default="'.$dvt_default.'">'.$row_nl['ten_nl'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="so_luong[]" min="0" step="0.001" required>
                                </td>
                                <td>
                                    <select name="id_dvt[]" required>
                                        <?php
                                        foreach ($ds_dvt as $dvt) {
                                            echo '<option value="'.$dvt['id_dvt'].'">'.$dvt['ten_dvt'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn-row btn-remove-row">Xóa</button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>

                    <button type="button" id="add-row" class="btn-row btn-add-row">+ Thêm dòng</button>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu công thức" class="btn-main">
                    <a href="congthuc_list.php"><button type="button" class="btn-back">Quay lại</button></a>

                    <?php
                    if (!empty($msg)) {
                        echo "<div style='margin-top:15px;'>$msg</div>";
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// [MỚI] Tự động chọn Đơn vị khi thay đổi nguyên liệu
function updateDonVi(selectNl) {
    var optionSelected = selectNl.options[selectNl.selectedIndex];
    var defaultDvtId   = optionSelected.getAttribute('data-dvt-default');
    
    var tr = selectNl.closest('tr');
    if (tr) {
        var selectDvt = tr.querySelector('select[name="id_dvt[]"]');
        if (selectDvt && defaultDvtId) {
            selectDvt.value = defaultDvtId;
        }
    }
}

document.addEventListener('change', function(e) {
    if (e.target.name === 'id_nl[]') {
        updateDonVi(e.target);
    }
});

// Thêm dòng nguyên liệu
document.getElementById('add-row').addEventListener('click', function() {
    var tbody   = document.getElementById('ct-body');
    var firstTr = tbody.querySelector('tr');
    var newTr   = firstTr.cloneNode(true);

    // reset giá trị input
    newTr.querySelectorAll('input').forEach(function(input){
        input.value = '';
    });
    // reset select về mặc định
    newTr.querySelectorAll('select').forEach(function(sel){
        sel.selectedIndex = 0;
    });

    tbody.appendChild(newTr);
});

// Xóa dòng
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-row')) {
        var tbody = document.getElementById('ct-body');
        var tr    = e.target.closest('tr');
        if (tbody.rows.length > 1) {
            tr.remove();
        }
    }
});

// Custom chọn món: menu + tìm kiếm + lọc loại
(function () {
    var selector     = document.getElementById('mon-selector');
    var selectorText = document.getElementById('mon-selector-text');
    var panel        = document.getElementById('mon-panel');
    var hiddenInput  = document.getElementById('id_mon');
    var searchInput  = document.getElementById('mon-search');
    var loaiFilter   = document.getElementById('mon-loai-filter');
    var items        = document.querySelectorAll('.mon-item');

    if (!selector || !panel) return;

    function openPanel() {
        panel.style.display = 'block';
    }
    function closePanel() {
        panel.style.display = 'none';
    }

    selector.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.style.display === 'block') {
            closePanel();
        } else {
            openPanel();
        }
    });

    // chọn món
    items.forEach(function (item) {
        item.addEventListener('click', function (e) {
            var id   = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');

            hiddenInput.value        = id;
            selectorText.textContent = name || '-- Chọn món --';

            // load lại trang để PHP tự load công thức hiện có
            window.location = 'congthuc_add.php?id_mon=' + encodeURIComponent(id);
        });
    });

    // lọc theo tên + loại
    function applyFilter() {
        var keyword = (searchInput.value || '').toLowerCase().trim();
        var loaiId  = loaiFilter.value;

        items.forEach(function (item) {
            var name     = (item.getAttribute('data-name') || '').toLowerCase();
            var itemLoai = item.getAttribute('data-idloai') || '';

            var matchName = !keyword || name.indexOf(keyword) !== -1;
            var matchLoai = !loaiId || loaiId === itemLoai;

            item.style.display = (matchName && matchLoai) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }
    if (loaiFilter) {
        loaiFilter.addEventListener('change', applyFilter);
    }

    // click ra ngoài thì đóng panel
    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && !selector.contains(e.target)) {
            closePanel();
        }
    });
})();
</script>

<?php include 'inc/footer.php'; ?>