<?php
// Bắt đầu bộ đệm ngay lập tức để chặn lỗi header
ob_start(); 

// =================================================================================
// 1. PHẦN XỬ LÝ AJAX (LƯU DỮ LIỆU)
// =================================================================================
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_chotca') {
    // Xóa sạch mọi output (HTML/Space) trước đó để đảm bảo JSON sạch
    ob_end_clean(); 
    
    // Kiểm tra file tồn tại
    $path_db = __DIR__ . '/../lib/database.php';
    $path_session = __DIR__ . '/../lib/session.php';
    
    if (!file_exists($path_db) || !file_exists($path_session)) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi: Không tìm thấy file hệ thống.']);
        exit;
    }

    include_once $path_session;
    Session::checkSession();
    include_once $path_db;
    
    header('Content-Type: application/json');
    
    try {
        $db = new Database();
        $today = date('Y-m-d');

        // [BẢO MẬT] Kiểm tra xem hôm nay đã chốt chưa
        $check = "SELECT id FROM tbl_chotca WHERE DATE(ngay_chot) = '$today'";
        $existed = $db->select($check);
        if($existed) {
            echo json_encode(['status' => 'error', 'msg' => 'Hôm nay đã có người chốt ca rồi!']);
            exit;
        }

        $adminName = Session::get('adminname') ? Session::get('adminname') : 'Admin';
        $tong_doanh_thu = $_POST['tong_doanh_thu'];
        $tien_vnpay = $_POST['tien_vnpay'];
        $tien_mat_sys = $_POST['tien_mat_sys'];
        $tien_mat_real = $_POST['tien_mat_real'];
        
        $chenh_lech = $tien_mat_real - $tien_mat_sys;
        $ngay_chot = date('Y-m-d H:i:s');
        
        $ghi_chu = "Khớp";
        if($chenh_lech > 0) $ghi_chu = "Dư tiền";
        if($chenh_lech < 0) $ghi_chu = "Thiếu tiền";

        $query = "INSERT INTO tbl_chotca(ngay_chot, nhanvien_chot, tong_doanh_thu, tien_vnpay, tien_mat_he_thong, tien_mat_thuc_te, chenh_lech, ghi_chu) 
                  VALUES('$ngay_chot', '$adminName', '$tong_doanh_thu', '$tien_vnpay', '$tien_mat_sys', '$tien_mat_real', '$chenh_lech', '$ghi_chu')";

        $insert = $db->insert($query);

        if ($insert) {
            echo json_encode(['status' => 'success', 'msg' => 'Đã chốt ca thành công! Đang quay về danh sách...']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Lỗi Database: Không thể ghi dữ liệu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    exit; 
}

// =================================================================================
// 2. PHẦN GIAO DIỆN (HTML)
// =================================================================================
// Xả bộ đệm cũ
ob_end_flush();

include 'inc/header.php';
include 'inc/sidebar.php';
include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';

$db = new Database();
$today = date('Y-m-d'); 

// --- [SỬA] KIỂM TRA GIỜ CHỐT CA (LOGIC ĐÚNG) ---
date_default_timezone_set('Asia/Ho_Chi_Minh'); 
$current_hour = (int)date('H'); 

// Quy định: Chỉ được chốt từ 21h tối đến 4h sáng hôm sau
$allow_chot = false;
if ($current_hour >= 21 || $current_hour < 4) {
    $allow_chot = true;
}
// -----------------------------------------------

// Kiểm tra xem đã chốt chưa
$is_locked = false;
$saved_data = null;

$query_check_lock = "SELECT * FROM tbl_chotca WHERE DATE(ngay_chot) = '$today' ORDER BY id DESC LIMIT 1";
$res_lock = $db->select($query_check_lock);

if ($res_lock && $res_lock !== false && $res_lock->num_rows > 0) {
    $is_locked = true; 
    $saved_data = $res_lock->fetch_assoc();
}

// Tính tiền
$query_total = "SELECT SUM(thanhtien) as total FROM hopdong WHERE DATE(created_at) = '$today' AND payment_status = 'completed'";
$res_total = $db->select($query_total);
$doanh_thu_tong = ($res_total) ? ($res_total->fetch_assoc()['total'] ?? 0) : 0;

$query_ck = "SELECT SUM(thanhtien) as ck FROM hopdong WHERE DATE(created_at) = '$today' AND payment_status = 'completed' AND payment_method = 'vnpay'";
$res_ck = $db->select($query_ck);
$tien_chuyen_khoan = ($res_ck) ? ($res_ck->fetch_assoc()['ck'] ?? 0) : 0;

$tien_mat_he_thong = $doanh_thu_tong - $tien_chuyen_khoan;
$val_real_cash = $is_locked ? $saved_data['tien_mat_thuc_te'] : '';

// Data khác
$query_orders = "SELECT id, created_at, thanhtien, payment_method FROM hopdong WHERE DATE(created_at) = '$today' AND payment_status = 'completed' ORDER BY id DESC";
$list_orders = $db->select($query_orders);

$query_mon = "SELECT m.name_mon, SUM(c.soluong) as sl_ban, SUM(c.thanhtien) as tong_tien FROM hopdong_chitiet c JOIN hopdong h ON h.id = c.hopdong_id JOIN monan m ON m.id_mon = c.monan_id WHERE DATE(h.created_at) = '$today' AND h.payment_status = 'completed' GROUP BY m.id_mon ORDER BY sl_ban DESC"; 
$list_mon = $db->select($query_mon);
$data_mon = [];
if ($list_mon) { while($row = $list_mon->fetch_assoc()) $data_mon[] = $row; }
?>

<style>
    .stat-container { display: flex; gap: 20px; margin-bottom: 20px; }
    .stat-box { flex: 1; padding: 20px; border-radius: 8px; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); position: relative; }
    .bg-info { background: linear-gradient(135deg, #0984e3, #74b9ff); cursor: pointer; }
    .bg-input { background: #fff; border: 2px solid #dfe6e9; color: #2d3436; }
    .money-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; border-bottom: 1px dashed rgba(255,255,255,0.3); padding-bottom: 5px; }
    .big-money { font-size: 26px; font-weight: 800; margin-top: 5px; }
    .money-input { width: 100%; padding: 12px; font-size: 22px; font-weight: bold; border: 1px solid #b2bec3; border-radius: 6px; color: #00b894; outline: none; }
    .money-input:disabled { background: #f1f2f6; color: #636e72; cursor: not-allowed; border-color: #ddd; }
    .diff-badge { margin-top: 10px; padding: 8px; border-radius: 4px; font-weight: bold; text-align: center; font-size: 15px; background: #f1f2f6; }
    .food-layout { display: flex; gap: 20px; height: 500px; }
    .col-hot { width: 35%; display: flex; flex-direction: column; gap: 15px; overflow-y: auto; padding-right: 5px; }
    .hot-card { background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #eee; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .hot-rank { width: 30px; height: 30px; background: #e17055; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; flex-shrink: 0;}
    .col-list { width: 65%; border: 1px solid #e1e1e1; background: #fff; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
    .table-scroll { overflow-y: auto; flex: 1; }
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th { position: sticky; top: 0; background: #f8f9fa; z-index: 2; padding: 10px; text-align: left; font-size: 13px; color: #636e72; border-bottom: 2px solid #dfe6e9; }
    .custom-table td { padding: 10px; border-bottom: 1px solid #f1f2f6; font-size: 14px; color: #2d3436; }
    .btn-chot-ca { width: 100%; padding: 15px; background: #d63031; color: white; font-size: 18px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; text-transform: uppercase; margin-top: 20px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-chot-ca:hover { background: #c0392b; }
    .alert-locked { padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; text-align: center; margin-top: 20px; font-size: 16px; font-weight: bold; }
    .btn-back { display: inline-block; margin-top: 10px; padding: 10px 20px; background: #0984e3; color: white; text-decoration: none; border-radius: 5px; font-weight: normal; font-size: 14px; }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal-box { background: white; width: 600px; max-height: 80vh; border-radius: 10px; padding: 0; display: flex; flex-direction: column; }
    .modal-header { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; background: #0984e3; color: white; border-radius: 10px 10px 0 0; }
    .modal-body { overflow-y: auto; flex: 1; }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: #f4f6f9; box-shadow: none; border:none; padding: 0;">
        <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h2 style="margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #2d3436;">
                <i class="fa fa-calculator"></i> CHỐT CA HÔM NAY (<?php echo date('d/m/Y'); ?>)
            </h2>

            <div class="stat-container">
                <div class="stat-box bg-info" onclick="document.getElementById('orderModal').style.display='flex'">
                    <div style="position: absolute; top: 10px; right: 10px; opacity: 0.5;"><i class="fa fa-search-plus"></i> Chi tiết</div>
                    <div class="money-row"><span>Tổng doanh thu:</span><strong><?php echo number_format($doanh_thu_tong); ?></strong></div>
                    <div class="money-row"><span>- VNPay (CK):</span><strong><?php echo number_format($tien_chuyen_khoan); ?></strong></div>
                    <div style="margin-top: 15px;">
                        <span style="opacity: 0.8; font-size: 13px; text-transform: uppercase;">Tiền mặt hệ thống</span>
                        <div class="big-money" id="sys-cash"><?php echo number_format($tien_mat_he_thong); ?></div>
                    </div>
                </div>
                <div class="stat-box bg-input">
                    <label style="font-size: 13px; color: #636e72; font-weight: bold; margin-bottom: 5px; display: block;">TIỀN TRONG KÉT THỰC TẾ:</label>
                    <input type="text" id="real-cash" class="money-input" placeholder="Nhập số tiền..." onkeyup="checkDiff()" autocomplete="off"
                           value="<?php echo ($val_real_cash !== '') ? number_format($val_real_cash) : ''; ?>"
                           <?php echo $is_locked ? 'disabled' : ''; ?> >
                    <div id="diff-res" class="diff-badge">...</div>
                </div>
            </div>

            <div class="food-layout">
                <div class="col-hot">
                    <div style="font-weight: bold; color: #e17055; margin-bottom: 5px; text-transform: uppercase;"><i class="fa fa-fire"></i> Top bán chạy</div>
                    <?php 
                    $count = 0;
                    foreach($data_mon as $m): 
                        if($count >= 5) break; 
                        $count++;
                    ?>
                    <div class="hot-card">
                        <div class="hot-rank"><?php echo $count; ?></div>
                        <div class="hot-info">
                            <h4 style="margin:0; font-size:14px;"><?php echo htmlspecialchars($m['name_mon']); ?></h4>
                            <div style="font-size:13px; color:#e17055;">Đã bán: <?php echo $m['sl_ban']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-list">
                    <div style="padding: 12px; background: #f8f9fa; border-bottom: 1px solid #ddd; font-weight: bold;"><i class="fa fa-list-alt"></i> Kiểm kê kho</div>
                    <div class="table-scroll">
                        <table class="custom-table">
                            <thead><tr><th>Tên món</th><th style="text-align: center;">SL</th><th style="text-align: right;">Doanh thu</th></tr></thead>
                            <tbody>
                                <?php foreach($data_mon as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['name_mon']); ?></td>
                                    <td align="center" style="font-weight:bold;"><?php echo $m['sl_ban']; ?></td>
                                    <td align="right"><?php echo number_format($m['tong_tien']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!$is_locked): ?>
                
                <?php if ($allow_chot): ?>
                    <button class="btn-chot-ca" onclick="confirmChotCa()">
                        <i class="fa fa-lock"></i> XÁC NHẬN CHỐT CA
                    </button>
                <?php else: ?>
                    <div class="alert-locked" style="background: #fff3cd; color: #856404; border-color: #ffeeba;">
                        <i class="fa fa-clock-o"></i> CHƯA ĐẾN GIỜ CHỐT CA!<br>
                        <small>Vui lòng thực hiện chốt ca từ 21:00 đến 04:00.</small>
                        <br>
                        <a href="booking_list.php" class="btn-back" style="background:#e0a800;">Quay về bán hàng</a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                
                <div class="alert-locked">
                    <i class="fa fa-check-circle"></i> CA NGÀY <?php echo date('d/m/Y', strtotime($saved_data['ngay_chot'])); ?> ĐÃ ĐƯỢC CHỐT!<br>
                    <small>Người chốt: <?php echo $saved_data['nhanvien_chot']; ?> | Lúc: <?php echo date('H:i', strtotime($saved_data['ngay_chot'])); ?></small>
                    <br>
                    <a href="booking_list.php" class="btn-back"><i class="fa fa-arrow-left"></i> Quay về Danh Sách Bàn</a>
                </div>

            <?php endif; ?>
            </div>
    </div>
</div>

<div id="orderModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header"><h3 style="margin:0;">Chi tiết đơn hôm nay</h3><span style="cursor:pointer" onclick="document.getElementById('orderModal').style.display='none'">&times;</span></div>
        <div class="modal-body">
            <table class="custom-table" style="margin:0;">
                <thead><tr><th>ID</th><th>Giờ</th><th>PTTT</th><th style="text-align: right;">Tiền</th></tr></thead>
                <tbody>
                    <?php 
                    if($list_orders){
                        while($o = $list_orders->fetch_assoc()){
                            $method = ($o['payment_method'] == 'vnpay') ? '<b style="color:#0984e3">VNPay</b>' : '<b style="color:#00b894">Tiền mặt</b>';
                            echo '<tr><td>#'.$o['id'].'</td><td>'.date('H:i', strtotime($o['created_at'])).'</td><td>'.$method.'</td><td align="right">'.number_format($o['thanhtien']).'</td></tr>';
                        }
                    } else echo '<tr><td colspan="4" align="center">Chưa có đơn.</td></tr>';
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function formatMoney(num) {
        return new Intl.NumberFormat('en-US').format(num);
    }
    document.addEventListener("DOMContentLoaded", function() { checkDiff(); });

    function checkDiff() {
        let sysCash = parseFloat(document.getElementById('sys-cash').innerText.replace(/,/g, '')) || 0;
        let inputVal = document.getElementById('real-cash').value.replace(/\D/g, ''); 
        let realCash = parseFloat(inputVal) || 0;
        let diff = realCash - sysCash;
        let diffBadge = document.getElementById('diff-res');
        
        if (document.getElementById('real-cash').value.trim() === '') {
            diffBadge.innerHTML = "Vui lòng nhập tiền";
            diffBadge.style.background = "#f1f2f6"; diffBadge.style.color = "#333"; return;
        }
        let diffText = formatMoney(Math.abs(diff));
        if (diff === 0) {
            diffBadge.innerHTML = '<i class="fa fa-check"></i> Đủ tiền (Khớp)'; diffBadge.style.background = "#d4edda"; diffBadge.style.color = "#155724";
        } else if (diff > 0) {
            diffBadge.innerHTML = '<i class="fa fa-plus"></i> Dư: ' + diffText + ' đ'; diffBadge.style.background = "#cce5ff"; diffBadge.style.color = "#004085";
        } else {
            diffBadge.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Thiếu: ' + diffText + ' đ'; diffBadge.style.background = "#f8d7da"; diffBadge.style.color = "#721c24";
        }
    }

    async function confirmChotCa() {
        let sysCash = parseFloat(document.getElementById('sys-cash').innerText.replace(/,/g, '')) || 0;
        let inputVal = document.getElementById('real-cash').value.replace(/\D/g, '');
        if (inputVal === '') { alert("Vui lòng nhập số tiền thực tế!"); document.getElementById('real-cash').focus(); return; }
        let realCash = parseFloat(inputVal);
        let diff = realCash - sysCash;

        let msg = "BẠN CÓ CHẮC CHẮN MUỐN CHỐT CA?\n--------------------------------\n";
        msg += "- Tiền hệ thống: " + formatMoney(sysCash) + " đ\n";
        msg += "- Tiền thực tế: " + formatMoney(realCash) + " đ\n";
        if(diff != 0) msg += "\n⚠️ CẢNH BÁO: Đang lệch " + formatMoney(Math.abs(diff)) + " đ";

        if(confirm(msg)) {
            let formData = new FormData();
            formData.append('ajax_action', 'save_chotca');
            formData.append('tong_doanh_thu', '<?php echo $doanh_thu_tong; ?>');
            formData.append('tien_vnpay', '<?php echo $tien_chuyen_khoan; ?>');
            formData.append('tien_mat_sys', sysCash);
            formData.append('tien_mat_real', realCash);

            try {
                let response = await fetch('chotca.php', { method: 'POST', body: formData });
                let text = await response.text();
                
                // --- [FIX LỖI QUAN TRỌNG: TRIM KHOẢNG TRẮNG] ---
                text = text.trim(); 
                // ----------------------------------------------

                try {
                    let result = JSON.parse(text);
                    if(result.status === 'success') {
                        alert("✅ " + result.msg);
                        window.location.href = 'booking_list.php'; 
                    } else { 
                        alert("❌ " + result.msg); 
                    }
                } catch (e) {
                    console.error("Lỗi JSON:", text);
                    if(text.includes('"status":"success"')) {
                        alert("✅ Đã chốt ca thành công!");
                        window.location.href = 'booking_list.php';
                    } else {
                        alert("Lỗi phản hồi: " + text.substring(0, 100));
                    }
                }
            } catch (error) { 
                alert("Lỗi kết nối mạng!"); 
            }
        }
    }
</script>

<?php include 'inc/footer.php'; ?>