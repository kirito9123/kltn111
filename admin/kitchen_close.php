<?php
// =================================================================================
// TRANG CHỐT CA BẾP - KIỂM SOÁT MÓN VÀ NGUYÊN LIỆU
// =================================================================================
ob_start();

// --- 1. XỬ LÝ AJAX LƯU CHỐT CA ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_chotca_bep') {
    ob_end_clean();
    
    $path_db = __DIR__ . '/../lib/database.php';
    $path_session = __DIR__ . '/../lib/session.php';
    
    if (!file_exists($path_db) || !file_exists($path_session)) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi file hệ thống.']); exit;
    }

    include_once $path_session;
    Session::checkSession();
    include_once $path_db;
    
    header('Content-Type: application/json');

    try {
        $db = new Database();
        $today = date('Y-m-d');
        $adminName = Session::get('adminname') ? Session::get('adminname') : 'Bếp Trưởng';
        $tong_mon = (int)$_POST['tong_mon'];
        $ghi_chu  = $_POST['ghi_chu'];
        $ngay_chot = date('Y-m-d H:i:s');

        // Kiểm tra đã chốt chưa
        $check = "SELECT id FROM tbl_chotca_bep WHERE DATE(ngay_chot) = '$today'";
        if($db->select($check)) {
            echo json_encode(['status' => 'error', 'msg' => 'Hôm nay bếp đã chốt ca rồi!']); exit;
        }

        $query = "INSERT INTO tbl_chotca_bep(ngay_chot, nhanvien_chot, tong_mon_da_lam, ghi_chu) 
                  VALUES('$ngay_chot', '$adminName', '$tong_mon', '$ghi_chu')";
        
        if ($db->insert($query)) {
            echo json_encode(['status' => 'success', 'msg' => 'Đã chốt sổ bếp thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Lỗi Database.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

/* ==== XỬ LÝ AJAX LẤY CHI TIẾT ĐƠN HÀNG (SỬA) ==== */
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'get_order_details') {
    ob_end_clean();
    include_once __DIR__ . '/../lib/database.php';
    $db = new Database();
    $today = date('Y-m-d');
    
    // Lấy tất cả đơn hàng đã được bếp xác nhận (status >= 1)
    $q_detail = "
        SELECT h.id, h.ghichu, h.created_at, h.tenKH,
               GROUP_CONCAT(CONCAT(m.name_mon, ' (x', c.soluong, ')') SEPARATOR ' | ') AS items_list
        FROM hopdong h
        JOIN hopdong_chitiet c ON h.id = c.hopdong_id
        JOIN monan m ON m.id_mon = c.monan_id
        WHERE DATE(h.created_at) = '$today' AND h.status >= 1
        GROUP BY h.id, h.ghichu, h.created_at, h.tenKH
        ORDER BY h.created_at DESC
    ";

    $rs = $db->select($q_detail);
    $output = '';

    if ($rs && $rs->num_rows > 0) {
        $output .= '<table class="k-table" style="margin-bottom: 0;">';
        $output .= '<thead><tr><th style="width: 80px;">Đơn #</th><th style="width: 150px;">Khách hàng</th><th>Chi tiết món</th><th style="width: 120px; text-align: center;">Thời gian</th></tr></thead>';
        $output .= '<tbody>';
        while($row = $rs->fetch_assoc()) {
            $items_text = $row['items_list'];
            // [SỬA UX] Giới hạn hiển thị và dùng thuộc tính title để xem đầy đủ
            if (strlen($items_text) > 100) {
                $items_display = substr($items_text, 0, 100) . '... (Rê chuột để xem full)';
            } else {
                $items_display = $items_text;
            }

            $output .= '<tr>';
            $output .= '<td><b>#'.$row['id'].'</b></td>';
            $output .= '<td>'.($row['tenKH'] ?: 'Khách lẻ').'</td>';
            $output .= '<td>';
            $output .= '<div style="font-size: 13px;" title="'.htmlspecialchars($items_text).'">'.$items_display.'</div>';
            if ($row['ghichu']) {
                $output .= '<div style="font-style: italic; color: #e74c3c; font-size: 12px;">Ghi chú: '.htmlspecialchars($row['ghichu']).'</div>';
            }
            $output .= '</td>';
            $output .= '<td align="center">'.date('H:i:s', strtotime($row['created_at'])).'</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    } else {
        $output = '<div style="text-align: center; color: #999;">Chưa có đơn hàng nào bếp xác nhận hoàn thành hôm nay.</div>';
    }

    echo $output;
    exit;
}

// --- 2. GIAO DIỆN HTML ---
ob_end_flush();
include 'inc/header.php';
include 'inc/sidebar.php';
include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';

$db = new Database();
$today = date('Y-m-d');

// --- KIỂM TRA TRẠNG THÁI CHỐT ---
$is_locked = false;
$saved_data = null;
$q_lock = "SELECT * FROM tbl_chotca_bep WHERE DATE(ngay_chot) = '$today' LIMIT 1";
$rs_lock = $db->select($q_lock);
if ($rs_lock && $rs_lock->num_rows > 0) {
    $is_locked = true;
    $saved_data = $rs_lock->fetch_assoc();
}

// --- LẤY DỮ LIỆU THỐNG KÊ ---

// 1. Thống kê Món ăn đã làm hôm nay (Dựa trên đơn đã được bếp xác nhận)
$q_mon = "SELECT m.name_mon, SUM(c.soluong) as sl_lam 
          FROM hopdong_chitiet c 
          JOIN hopdong h ON h.id = c.hopdong_id 
          JOIN monan m ON m.id_mon = c.monan_id 
          WHERE DATE(h.created_at) = '$today' AND h.status >= 1
          GROUP BY m.id_mon 
          ORDER BY sl_lam DESC";
$list_mon = $db->select($q_mon);
$data_mon = [];
$total_items_today = 0;
if ($list_mon) {
    while($r = $list_mon->fetch_assoc()) {
        $data_mon[] = $r;
        $total_items_today += $r['sl_lam'];
    }
}

// 2. Tính toán Nguyên liệu tiêu hao (SỬA LỖI LOGIC QUY ĐỔI ĐƠN VỊ LỚN - TẠM THỜI)
$q_nl = "SELECT nl.id_nl, nl.ten_nl, nl.don_vi, nl.so_luong_ton, 
                SUM(ct.so_luong * hdct.soluong) as tong_dung_du_kien
         FROM hopdong_chitiet hdct
         JOIN hopdong h ON h.id = hdct.hopdong_id
         JOIN congthuc_mon ct ON ct.id_mon = hdct.monan_id
         JOIN nguyen_lieu nl ON nl.id_nl = ct.id_nl
         WHERE DATE(h.created_at) = '$today' AND h.status >= 1
         GROUP BY nl.id_nl
         ORDER BY tong_dung_du_kien DESC";

$list_nl = $db->select($q_nl);
$data_nl = [];
if ($list_nl) {
    while($r = $list_nl->fetch_assoc()) {
        // [SỬA LỖI ĐƠN VỊ LỚN] Giả định công thức lưu là gram, đơn vị gốc là kg/lít.
        $tong_dung_goc = (float)$r['tong_dung_du_kien'];
        $don_vi_goc = strtolower(trim($r['don_vi']));
        
        $tong_dung_sau_sua = $tong_dung_goc;

        // Nếu đơn vị gốc là kg/lít (đơn vị lớn) và số lượng tính ra lớn (giả định là đơn vị nhỏ)
        // Đây là fix tạm thời do không được dùng hệ số từ DB
        if (in_array($don_vi_goc, ['kg', 'lít', 'lit']) && $tong_dung_goc >= 100) {
             // Chia cho 1000 (quy đổi từ g/ml sang kg/lít)
            $tong_dung_sau_sua = $tong_dung_goc / 1000; 
        }

        $r['tong_dung_du_kien'] = $tong_dung_sau_sua;
        $data_nl[] = $r;
    }
} 

// 3. Lấy những nguyên liệu sắp hết (đã thêm cột đơn vị để hiển thị chính xác)
$q_low = "SELECT ten_nl, so_luong_ton, don_vi FROM nguyen_lieu WHERE so_luong_ton <= 5 AND xoa = 0";
$list_low = $db->select($q_low);
?>

<style>
    /* Custom CSS cho Bếp */
    .k-container { display: flex; gap: 20px; flex-wrap: wrap; }
    .k-col-left { flex: 1; min-width: 300px; }
    .k-col-right { flex: 1.5; min-width: 400px; }
    
    .k-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 20px; }
    .k-header { background: #2c3e50; color: #fff; padding: 12px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
    .k-body { padding: 0; max-height: 400px; overflow-y: auto; }
    
    /* Table Styles */
    .k-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .k-table th { background: #ecf0f1; color: #2c3e50; padding: 10px; text-align: left; position: sticky; top: 0; z-index: 2; border-bottom: 2px solid #bdc3c7; }
    .k-table td { padding: 10px; border-bottom: 1px solid #eee; color: #333; }
    .k-table tr:hover { background: #f9f9f9; }

    /* Badge & Highlight */
    .qty-badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-weight: bold; font-size: 12px; }
    .unit-badge { color: #7f8c8d; font-size: 12px; font-style: italic; }
    .stock-ok { color: #27ae60; font-weight: bold; }
    .stock-low { color: #e67e22; font-weight: bold; }
    .stock-crit { color: #c0392b; font-weight: bold; animation: blink 2s infinite; }
    
    /* Summary Boxes */
    .summary-box { 
        background: linear-gradient(135deg, #e67e22, #f39c12); color: white; padding: 20px; border-radius: 8px; 
        text-align: center; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(230, 126, 34, 0.3);
        cursor: pointer; 
        transition: 0.2s;
    }
    .summary-box:hover {
        opacity: 0.9;
    }
    .big-num { font-size: 36px; font-weight: 800; display: block; margin: 10px 0; }
    
    /* Button */
    .btn-close-kitchen { width: 100%; padding: 15px; background: #2c3e50; color: white; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-close-kitchen:hover { background: #1a252f; }
    
    .alert-locked { padding: 20px; background: #dff9fb; border: 1px solid #c7ecee; color: #130f40; border-radius: 8px; text-align: center; font-size: 16px; margin-top: 20px; }

    /* Modal Styles */
    /* [SỬA] Tăng kích thước Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal-box { background: white; width: 900px; max-width: 95%; max-height: 85vh; border-radius: 10px; padding: 0; display: flex; flex-direction: column; }
    .modal-header { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; background: #f39c12; color: white; border-radius: 10px 10px 0 0; }
    .modal-body { overflow-y: auto; flex: 1; }

    @keyframes blink { 50% { opacity: 0.5; } }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: #f4f6f9; border:none; padding: 10px;">
        
        <h2 style="color: #2c3e50; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="fa fa-fire"></i> BÁO CÁO & CHỐT CA BẾP (<?php echo date('d/m/Y'); ?>)
        </h2>

        <div class="k-container">
            <div class="k-col-left">
                <div class="summary-box" onclick="showOrderDetails()">
                    <span>TỔNG SỐ MÓN ĐÃ LÀM HÔM NAY (Bấm để xem)</span>
                    <span class="big-num"><?php echo number_format($total_items_today); ?></span>
                    <small>Dựa trên các đơn đã được bếp hoàn thành</small>
                </div>

                <div class="k-card">
                    <div class="k-header" style="background: #c0392b;">
                        <span><i class="fa fa-exclamation-triangle"></i> NGUYÊN LIỆU SẮP HẾT</span>
                    </div>
                    <div class="k-body">
                        <?php if ($list_low && $list_low->num_rows > 0): ?>
                            <table class="k-table">
                                <?php while($low = $list_low->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $low['ten_nl']; ?></td>
                                    <td style="text-align: right;">
                                        <span class="stock-crit">Còn: <?php echo $low['so_luong_ton']; ?> <?php echo $low['don_vi']; ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </table>
                        <?php else: ?>
                            <div style="padding: 15px; text-align: center; color: #27ae60;">Kho hàng ổn định <i class="fa fa-check"></i></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="k-card">
                    <div class="k-header">
                        <span><i class="fa fa-cutlery"></i> MÓN ĂN ĐÃ CHẾ BIẾN</span>
                    </div>
                    <div class="k-body">
                        <table class="k-table">
                            <thead><tr><th>Tên món</th><th style="text-align: right;">SL</th></tr></thead>
                            <tbody>
                                <?php if (!empty($data_mon)): ?>
                                    <?php foreach ($data_mon as $m): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($m['name_mon']); ?></td>
                                        <td style="text-align: right;"><span class="qty-badge"><?php echo $m['sl_lam']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" align="center">Hôm nay chưa làm món nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="k-col-right">
                <div class="k-card">
                    <div class="k-header" style="background: #2980b9;">
                        <span><i class="fa fa-flask"></i> ƯỚC TÍNH TIÊU HAO NGUYÊN LIỆU</span>
                        <small style="color: #dff9fb; font-weight: normal;">(Dựa trên công thức)</small>
                    </div>
                    <div class="k-body" style="max-height: 600px;">
                        <table class="k-table">
                            <thead>
                                <tr>
                                    <th>Nguyên liệu</th>
                                    <th style="text-align: center;">Đã dùng (Est)</th>
                                    <th style="text-align: center;">Tồn kho HT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data_nl)): ?>
                                    <?php foreach ($data_nl as $nl): 
                                        $ton = (float)$nl['so_luong_ton'];
                                        $style = "stock-ok";
                                        if($ton <= 5) $style = "stock-crit";
                                        elseif($ton <= 10) $style = "stock-low";
                                    ?>
                                    <tr>
                                        <td><?php echo $nl['ten_nl']; ?></td>
                                        <td align="center">
                                            <b><?php echo number_format($nl['tong_dung_du_kien'], 2); ?></b>
                                            <span class="unit-badge"><?php echo $nl['don_vi']; ?></span>
                                        </td>
                                        <td align="center">
                                            <span class="<?php echo $style; ?>"><?php echo number_format($ton, 2); ?></span>
                                            <span class="unit-badge"><?php echo $nl['don_vi']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" align="center" style="padding: 20px;">Chưa có dữ liệu tiêu hao (Hoặc món chưa cấu hình công thức).</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 10px; background: #f1f1f1; font-size: 12px; color: #666;">
                        * <i>Đã dùng (Est)</i>: Là lượng nguyên liệu lý thuyết tính theo tổng số món bán ra.<br>
                        * <i>Tồn kho HT</i>: Là lượng tồn kho thực tế hiện tại trên hệ thống.
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$is_locked): ?>
            <div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Ghi chú cuối ca (Nếu có sự cố, đồ hỏng, v.v...):</label>
                <textarea id="kitchen-note" style="width: 100%; height: 60px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Ví dụ: Hôm nay vỡ 2 cái đĩa, cháy 1 phần bò..."></textarea>
                
                <br><br>
                <button class="btn-close-kitchen" onclick="submitKitchenClose()">
                    <i class="fa fa-lock"></i> XÁC NHẬN CHỐT SỔ BẾP
                </button>
            </div>
        <?php else: ?>
            <div class="alert-locked">
                <h3><i class="fa fa-check-circle"></i> ĐÃ CHỐT CA BẾP NGÀY HÔM NAY</h3>
                <p>Người chốt: <b><?php echo $saved_data['nhanvien_chot']; ?></b> | Thời gian: <?php echo date('H:i d/m/Y', strtotime($saved_data['ngay_chot'])); ?></p>
                <p style="font-style: italic;">"<?php echo $saved_data['ghi_chu']; ?>"</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<div id="orderDetailModal" class="modal-overlay">
    <div class="modal-box" style="width: 900px;">
        <div class="modal-header" style="background:#f39c12;">
            <h3 style="margin:0;"><i class="fa fa-list-ul"></i> CHI TIẾT CÁC ĐƠN ĐÃ LÀM HÔM NAY</h3>
            <span style="cursor:pointer" onclick="document.getElementById('orderDetailModal').style.display='none'">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modal-content-orders" style="padding: 15px;">
                Đang tải dữ liệu...
            </div>
        </div>
    </div>
</div>

<script>
    function submitKitchenClose() {
        if (!confirm("Xác nhận chốt sổ bếp?\nDữ liệu về số lượng món và tiêu hao sẽ được lưu lại.")) return;

        let note = document.getElementById('kitchen-note').value;
        let total = <?php echo $total_items_today; ?>;

        let formData = new FormData();
        formData.append('ajax_action', 'save_chotca_bep');
        formData.append('tong_mon', total);
        formData.append('ghi_chu', note);

        fetch('kitchen_close.php', { method: 'POST', body: formData })
        .then(res => res.text()) // Đổi sang đọc phản hồi là text trước
        .then(text => {
            
            // RẤT QUAN TRỌNG: Loại bỏ khoảng trắng/ký tự thừa từ PHP
            text = text.trim(); 

            try {
                // Cố gắng parse JSON
                let data = JSON.parse(text);

                if (data.status === 'success') {
                    alert("✅ " + data.msg);
                    location.reload();
                } else {
                    alert("❌ Lỗi: " + data.msg);
                }
            } catch (e) {
                // Xử lý lỗi nếu JSON không hợp lệ (tức là PHP có Fatal Error)
                console.error("Lỗi Fatal Error PHP ẩn/JSON không hợp lệ:", text);
                alert("❌ Lỗi Server. Vui lòng kiểm tra console hoặc log PHP. Phản hồi server: " + text.substring(0, 100));
            }
        })
        .catch(err => {
            console.error("Lỗi kết nối mạng:", err);
            alert("❌ Lỗi kết nối mạng!");
        });
    }

    function showOrderDetails() {
        // [SỬA] Tăng kích thước Modal
        document.getElementById('orderDetailModal').querySelector('.modal-box').style.width = '900px'; 
        // Hiển thị modal
        document.getElementById('orderDetailModal').style.display = 'flex';
        document.getElementById('modal-content-orders').innerHTML = 'Đang tải dữ liệu...';
        
        let formData = new FormData();
        formData.append('ajax_action', 'get_order_details');

        fetch('kitchen_close.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(html => {
            document.getElementById('modal-content-orders').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('modal-content-orders').innerHTML = '<div style="color:red; text-align:center;">Lỗi tải chi tiết đơn hàng.</div>';
            console.error(err);
        });
    }
</script>

<?php include 'inc/footer.php'; ?>