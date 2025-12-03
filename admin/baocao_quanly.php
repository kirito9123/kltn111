<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';

$db = new Database();
$fm = new Format();

// --- 1. XỬ LÝ BỘ LỌC NGÀY ---
// Mặc định là NGÀY HÔM NAY (Theo yêu cầu)
$today = date('Y-m-d');
$first_day_month = date('Y-m-01');
$last_day_month  = date('Y-m-t');
$last_7_days     = date('Y-m-d', strtotime('-6 days'));

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $today;
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : $today;

// --- 2. THỐNG KÊ DOANH THU TỔNG QUAN ---
$q_revenue = "SELECT 
                SUM(thanhtien) as total_rev,
                COUNT(id) as total_orders,
                SUM(CASE WHEN payment_method = 'vnpay' THEN thanhtien ELSE 0 END) as total_vnpay,
                SUM(CASE WHEN payment_method != 'vnpay' THEN thanhtien ELSE 0 END) as total_cash
              FROM hopdong 
              WHERE payment_status = 'completed' 
              AND DATE(created_at) BETWEEN '$from_date' AND '$to_date'";
$rs_revenue = $db->select($q_revenue);
$data_rev = $rs_revenue->fetch_assoc();

// --- 3. DỮ LIỆU BIỂU ĐỒ ĐƯỜNG (DOANH THU) ---
$q_chart_line = "SELECT DATE(created_at) as ngay, SUM(thanhtien) as doanh_thu
                 FROM hopdong
                 WHERE payment_status = 'completed'
                 AND DATE(created_at) BETWEEN '$from_date' AND '$to_date'
                 GROUP BY DATE(created_at)
                 ORDER BY ngay ASC";
$rs_chart_line = $db->select($q_chart_line);
$labels_line = [];
$data_line   = [];
if ($rs_chart_line) {
    while ($row = $rs_chart_line->fetch_assoc()) {
        $labels_line[] = date('d/m', strtotime($row['ngay'])); 
        $data_line[]   = (int)$row['doanh_thu'];
    }
}

// --- 4. DANH SÁCH CHI TIẾT ĐƠN HÀNG (MODAL) ---
$q_list_orders = "SELECT id, tenKH, created_at, payment_method, thanhtien
                  FROM hopdong
                  WHERE payment_status = 'completed'
                  AND DATE(created_at) BETWEEN '$from_date' AND '$to_date'
                  ORDER BY created_at DESC";
$rs_list_orders = $db->select($q_list_orders);

// --- 5. THỐNG KÊ CHÊNH LỆCH TIỀN MẶT ---
$q_diff = "SELECT 
            SUM(chenh_lech) as total_diff,
            COUNT(id) as total_shifts
           FROM tbl_chotca 
           WHERE DATE(ngay_chot) BETWEEN '$from_date' AND '$to_date'";
$rs_diff = $db->select($q_diff);
$data_diff = $rs_diff ? $rs_diff->fetch_assoc() : ['total_diff' => 0, 'total_shifts' => 0];

// --- 6. TOP 5 MÓN BÁN CHẠY (BIỂU ĐỒ CỘT DỌC) ---
$q_top = "SELECT m.name_mon, SUM(c.soluong) as total_qty
          FROM hopdong_chitiet c
          JOIN hopdong h ON h.id = c.hopdong_id
          JOIN monan m ON m.id_mon = c.monan_id
          WHERE h.payment_status = 'completed'
          AND DATE(h.created_at) BETWEEN '$from_date' AND '$to_date'
          GROUP BY m.id_mon
          ORDER BY total_qty DESC LIMIT 5";
$rs_top = $db->select($q_top);

$labels_bar = [];
$data_bar_qty = [];
if ($rs_top) {
    while($top = $rs_top->fetch_assoc()) {
        $labels_bar[] = $top['name_mon'];
        $data_bar_qty[] = (int)$top['total_qty'];
    }
}

// --- 7. LỊCH SỬ GHI CHÚ ---
$q_kitchen_notes = "SELECT ngay_chot, nhanvien_chot, ghi_chu FROM tbl_chotca_bep 
                    WHERE DATE(ngay_chot) BETWEEN '$from_date' AND '$to_date' AND ghi_chu != ''";
$rs_kitchen = $db->select($q_kitchen_notes);

$q_cashier_notes = "SELECT ngay_chot, nhanvien_chot, chenh_lech, ghi_chu FROM tbl_chotca 
                    WHERE DATE(ngay_chot) BETWEEN '$from_date' AND '$to_date'";
$rs_cashier = $db->select($q_cashier_notes);
?>

<style>
    /* Dashboard Layout */
    .dashboard-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    
    .card-stat { 
        background: #fff; border-radius: 10px; padding: 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #ddd; 
        position: relative; overflow: hidden; transition: transform 0.2s; cursor: default;
    }
    .card-stat:hover { transform: translateY(-3px); }
    .card-stat h3 { margin: 0; font-size: 13px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; }
    .card-stat .num { font-size: 26px; font-weight: 800; margin: 10px 0; color: #2c3e50; }
    .card-stat .sub { font-size: 13px; color: #95a5a6; }
    
    .border-blue { border-color: #3498db; cursor: pointer; } 
    .border-green { border-color: #2ecc71; }
    .border-orange { border-color: #e67e22; }
    .border-red { border-color: #e74c3c; }

    .chart-section { display: flex; gap: 20px; margin-bottom: 30px; }
    .chart-box { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex: 1; }

    .report-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .report-table th { background: #f8f9fa; padding: 10px; border-bottom: 2px solid #eee; text-align: left; font-size: 13px; color: #666; position: sticky; top: 0; }
    .report-table td { padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 14px; color: #333; }
    
    /* Style mới cho thanh lọc */
    .filter-bar { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); flex-wrap: wrap; }
    .filter-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
    .btn-filter { padding: 8px 15px; background: #2c3e50; color: white; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap:5px; }
    
    /* Các nút chọn nhanh */
    .btn-quick { 
        padding: 8px 15px; border: 1px solid #ddd; background: #f8f9fa; color: #555; 
        text-decoration: none; border-radius: 5px; font-size: 13px; transition: 0.2s; 
    }
    .btn-quick:hover, .btn-quick.active { background: #3498db; color: white; border-color: #3498db; }

    .badge-loss { background: #ff7675; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; }
    .badge-gain { background: #55efc4; color: #00b894; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }

    /* Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .modal-box { background: white; width: 800px; max-width: 95%; max-height: 85vh; border-radius: 10px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .modal-header { padding: 15px 20px; background: #3498db; color: white; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 0; overflow-y: auto; flex: 1; }
    .modal-footer { padding: 15px; border-top: 1px solid #eee; text-align: right; background: #f9f9f9; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="grid_10">
    <div class="box round first grid" style="background: #f4f6f9; border:none; padding: 0;">
        
        <h2 style="margin-bottom: 20px; color: #2c3e50;"><i class="fa fa-pie-chart"></i> BÁO CÁO QUẢN TRỊ</h2>

        <form method="GET" action="" class="filter-bar">
            <a href="?from_date=<?php echo $today; ?>&to_date=<?php echo $today; ?>" 
               class="btn-quick <?php echo ($from_date == $today && $to_date == $today) ? 'active' : ''; ?>">Hôm nay</a>
            
            <a href="?from_date=<?php echo $last_7_days; ?>&to_date=<?php echo $today; ?>" 
               class="btn-quick <?php echo ($from_date == $last_7_days) ? 'active' : ''; ?>">7 ngày qua</a>
            
            <a href="?from_date=<?php echo $first_day_month; ?>&to_date=<?php echo $last_day_month; ?>" 
               class="btn-quick <?php echo ($from_date == $first_day_month) ? 'active' : ''; ?>">Tháng này</a>

            <div style="border-left: 1px solid #ccc; height: 30px; margin: 0 10px;"></div>

            <label>Từ:</label>
            <input type="date" name="from_date" class="filter-input" value="<?php echo $from_date; ?>">
            <label>Đến:</label>
            <input type="date" name="to_date" class="filter-input" value="<?php echo $to_date; ?>">
            <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Lọc</button>
        </form>

        <div class="dashboard-grid">
            <div class="card-stat border-blue" onclick="document.getElementById('revenueModal').style.display='flex'" title="Bấm để xem chi tiết">
                <h3>Tổng Doanh Thu <i class="fa fa-search-plus" style="float:right"></i></h3>
                <div class="num"><?php echo number_format($data_rev['total_rev'] ?? 0); ?> đ</div>
                <div class="sub">
                    <?php echo $data_rev['total_orders']; ?> đơn hàng
                </div>
            </div>
            
            <div class="card-stat border-green">
                <h3>Tiền Về Tài Khoản (VNPay)</h3>
                <div class="num"><?php echo number_format($data_rev['total_vnpay'] ?? 0); ?> đ</div>
                <div class="sub">Chiếm <?php echo ($data_rev['total_rev'] > 0) ? round(($data_rev['total_vnpay']/$data_rev['total_rev'])*100, 1) : 0; ?>%</div>
            </div>

            <div class="card-stat border-orange">
                <h3>Tiền Mặt Thu Ngân</h3>
                <div class="num"><?php echo number_format($data_rev['total_cash'] ?? 0); ?> đ</div>
                <div class="sub">Cần thu về két</div>
            </div>

            <div class="card-stat border-red">
                <h3>Thất Thoát / Lệch Két</h3>
                <?php 
                    $diff = $data_diff['total_diff'];
                    $color = ($diff < 0) ? '#e74c3c' : (($diff > 0) ? '#27ae60' : '#7f8c8d');
                ?>
                <div class="num" style="color: <?php echo $color; ?>"><?php echo number_format($diff ?? 0); ?> đ</div>
                <div class="sub">Tổng hợp từ <?php echo $data_diff['total_shifts']; ?> lần chốt ca</div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-box" style="flex: 2;">
                <h3 style="margin-top:0; color:#555;">📈 Xu Hướng Doanh Thu</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
            
            <div class="chart-box" style="flex: 1;">
                <h3 style="margin-top:0; color:#555;">💳 Tỷ Lệ Thanh Toán</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="chart-box" style="flex: 1;">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🏆 Top 5 Món Bán Chạy</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-box" style="flex: 1;">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">⚠️ Nhật Ký Sự Cố & Ghi Chú</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table class="report-table">
                        <thead><tr><th>Ngày</th><th>Bộ phận</th><th>Nội dung</th></tr></thead>
                        <tbody>
                            <?php 
                            $has_incident = false;
                            if($rs_cashier): while($c = $rs_cashier->fetch_assoc()): 
                                if($c['chenh_lech'] == 0 && empty($c['ghi_chu'])) continue;
                                $has_incident = true;
                            ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($c['ngay_chot'])); ?></td>
                                <td><span style="color:#0984e3; font-weight:bold;">Thu ngân</span></td>
                                <td>
                                    <?php if($c['chenh_lech'] != 0): ?>
                                        <span class="<?php echo ($c['chenh_lech'] < 0) ? 'badge-loss' : 'badge-gain'; ?>">
                                            <?php echo ($c['chenh_lech'] < 0) ? 'Thiếu ' : 'Dư '; echo number_format(abs($c['chenh_lech'] ?? 0)); ?>đ
                                        </span><br>
                                    <?php endif; ?>
                                    <?php echo $c['ghi_chu']; ?>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>

                            <?php if($rs_kitchen): while($k = $rs_kitchen->fetch_assoc()): $has_incident = true; ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($k['ngay_chot'])); ?></td>
                                <td><span style="color:#e67e22; font-weight:bold;">Bếp</span></td>
                                <td><?php echo $k['ghi_chu']; ?></td>
                            </tr>
                            <?php endwhile; endif; ?>
                            
                            <?php if(!$has_incident): ?>
                                <tr><td colspan="3" align="center" style="color:#999; padding:20px;">Hệ thống vận hành ổn định. Không có sự cố.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<div id="revenueModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="margin:0;"><i class="fa fa-list-ul"></i> CHI TIẾT ĐƠN HÀNG</h3>
            <span style="cursor:pointer; font-size:24px;" onclick="document.getElementById('revenueModal').style.display='none'">&times;</span>
        </div>
        <div class="modal-body">
            <table class="report-table" style="margin:0;">
                <thead style="position: sticky; top: 0; background: #eee;">
                    <tr><th>Mã đơn</th><th>Thời gian</th><th>Khách hàng</th><th>Hình thức</th><th style="text-align: right;">Thành tiền</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    if($rs_list_orders && $rs_list_orders->num_rows > 0): 
                        while($ord = $rs_list_orders->fetch_assoc()): 
                            $grand_total += $ord['thanhtien'];
                            $pmethod = ($ord['payment_method'] == 'vnpay') 
                                ? '<span style="color:#36A2EB; font-weight:bold;">VNPay</span>' 
                                : '<span style="color:#FF9F40; font-weight:bold;">Tiền mặt</span>';
                    ?>
                    <tr>
                        <td><b>#<?php echo $ord['id']; ?></b></td>
                        <td><?php echo date('H:i d/m', strtotime($ord['created_at'])); ?></td>
                        <td><?php echo $ord['tenKH'] ?: 'Khách lẻ'; ?></td>
                        <td><?php echo $pmethod; ?></td>
                        <td align="right"><?php echo number_format($ord['thanhtien']); ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" align="center" style="padding:20px;">Không tìm thấy đơn hàng nào trong khoảng thời gian này.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <strong>TỔNG CỘNG: <?php echo number_format($grand_total); ?> đ</strong>
        </div>
    </div>
</div>

<script>
    window.onclick = function(event) {
        let modal = document.getElementById('revenueModal');
        if (event.target == modal) { modal.style.display = "none"; }
    }

    // --- CẤU HÌNH BIỂU ĐỒ ---
    const lineLabels = <?php echo json_encode($labels_line); ?>;
    const lineData   = <?php echo json_encode($data_line); ?>;
    const pieData    = [<?php echo $data_rev['total_vnpay']; ?>, <?php echo $data_rev['total_cash']; ?>];
    const barLabels  = <?php echo json_encode($labels_bar); ?>;
    const barData    = <?php echo json_encode($data_bar_qty); ?>;

    // A. Biểu đồ Đường (Xu hướng)
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: lineData,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // B. Biểu đồ Tròn (Thanh toán) - SỬA MÀU TƯƠNG PHẢN CAO
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: ['VNPay (CK)', 'Tiền Mặt'],
            datasets: [{
                data: pieData,
                backgroundColor: ['#36A2EB', '#FF9F40'] // Xanh Dương Đậm vs Cam
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // C. Biểu đồ Cột (Top Món) - CHUYỂN SANG DỌC (VERTICAL)
    new Chart(document.getElementById('barChart'), {
        type: 'bar', // Mặc định là dọc
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Số lượng bán',
                data: barData,
                backgroundColor: '#FF6384' // Màu Hồng/Đỏ nhạt cho nổi
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include 'inc/footer.php'; ?>