<?php
// === PHẦN XỬ LÝ AJAX CHI TIẾT (GIỮ NGUYÊN) ===
if (isset($_GET['ajax']) && $_GET['ajax'] == 'detail') {
    include_once __DIR__ . '/../lib/database.php';
    include_once __DIR__ . '/../helpers/format.php';
    include_once __DIR__ . '/../classes/nguyenvatlieu.php';
    
    $nl = new nguyenvatlieu();
    $id_phieu = $_GET['id'];
    $details = $nl->get_chi_tiet_phieu($id_phieu);

    if ($details) {
        echo '<div class="detail-box">
                <h4 class="detail-title"><i class="fa fa-list-alt"></i> Chi tiết hàng nhập</h4>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Tên Nguyên Liệu</th>
                            <th class="text-center">Số Lượng</th>
                            <th class="text-center">Đơn vị</th>
                            <th class="text-right">Giá Vốn</th>
                            <th class="text-right">Thành Tiền</th>
                        </tr>
                    </thead>
                    <tbody>';
        $total = 0;
        while ($row = $details->fetch_assoc()) {
            $total += $row['thanh_tien'];
            echo '<tr>
                    <td>' . htmlspecialchars($row['ten_nl']) . '</td>
                    <td class="text-center bold-blue">' . floatval($row['so_luong_nhap']) . '</td>
                    <td class="text-center">' . $row['don_vi'] . '</td>
                    <td class="text-right">' . number_format($row['gia_nhap'], 0, ',', '.') . '</td>
                    <td class="text-right bold-dark">' . number_format($row['thanh_tien'], 0, ',', '.') . '</td>
                  </tr>';
        }
        echo '      <tr class="detail-footer">
                        <td colspan="4" class="text-right">TỔNG CỘNG:</td>
                        <td class="text-right bold-red">' . number_format($total, 0, ',', '.') . ' VNĐ</td>
                    </tr>
                    </tbody>
                </table>
              </div>';
    } else { echo '<div class="error-msg">Không tìm thấy chi tiết.</div>'; }
    exit; 
}
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include_once __DIR__ . '/../classes/nguyenvatlieu.php'; ?>

<?php
$nl = new nguyenvatlieu();
$tungay = isset($_GET['tungay']) ? $_GET['tungay'] : '';
$denngay = isset($_GET['denngay']) ? $_GET['denngay'] : '';
$list_phieu = $nl->get_all_phieu_nhap($tungay, $denngay);
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    /* --- GIAO DIỆN CHUNG --- */
    .box.round { border-radius: 8px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    h2 { padding: 15px 20px; margin: 0; border-bottom: 1px solid #eee; background: #fff; border-radius: 8px 8px 0 0; color: #444; font-size: 18px; text-transform: uppercase; }
    .block { padding: 20px; background: #fff; border-radius: 0 0 8px 8px; }

    /* --- THANH BỘ LỌC (ĐẸP HƠN) --- */
    .filter-bar {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;
        background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;
        margin-bottom: 20px; gap: 15px;
    }
    
    .quick-filters { display: flex; gap: 5px; }
    .btn-quick {
        background: #fff; border: 1px solid #ccc; color: #555; padding: 6px 12px;
        border-radius: 5px; cursor: pointer; font-size: 13px; transition: 0.2s;
        font-weight: 500;
    }
    .btn-quick:hover { background: #e2e6ea; color: #333; }
    .btn-quick.active { background: #0d6efd; color: white; border-color: #0d6efd; }

    .date-filters { display: flex; align-items: center; gap: 10px; }
    .date-group { display: flex; align-items: center; gap: 5px; font-size: 14px; font-weight: 500; color: #555; }
    .filter-input {
        padding: 6px 10px; border: 1px solid #ced4da; border-radius: 4px; outline: none; font-size: 13px;
    }
    .filter-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); }

    .btn-filter {
        background: #0d6efd; color: white; border: none; padding: 7px 20px;
        border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;
        transition: 0.2s;
    }
    .btn-filter:hover { background: #0b5ed7; transform: translateY(-1px); }

    /* --- BẢNG DỮ LIỆU --- */
    table.dataTable thead th { background-color: #343a40; color: white; font-weight: 600; padding: 12px; border-bottom: none; }
    table.dataTable tbody td { padding: 12px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    table.dataTable.no-footer { border-bottom: 1px solid #eee; }
    
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .bold-blue { font-weight: bold; color: #0d6efd; }
    .bold-red { font-weight: bold; color: #dc3545; }
    .text-muted { color: #6c757d; font-style: italic; }

    /* --- NÚT HÀNH ĐỘNG --- */
    .action-group { display: flex; gap: 5px; justify-content: center; }
    .btn-action {
        border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;
        font-size: 13px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        transition: 0.2s;
    }
    .btn-view { background: #17a2b8; color: white; }
    .btn-view:hover { background: #138496; color: white;}
    
    .btn-print { background: #ffc107; color: #333; }
    .btn-print:hover { background: #e0a800; color: #000; }

    /* --- CHI TIẾT SỔ XUỐNG --- */
    .detail-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin: 10px 0; box-shadow: inset 0 0 10px rgba(0,0,0,0.03); }
    .detail-title { margin-top: 0; color: #495057; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; margin-bottom: 15px; font-size: 16px; font-weight: 700; }
    .detail-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 4px; overflow: hidden; border: 1px solid #eee; }
    .detail-table th { background: #e9ecef; color: #495057; padding: 10px; font-weight: 600; text-align: left; }
    .detail-table td { padding: 10px; border-bottom: 1px solid #f1f1f1; }
    .detail-footer { background: #fff3cd; font-weight: bold; }
    .bold-dark { font-weight: bold; color: #333; }
    .error-msg { color: red; padding: 10px; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2><i class="fa fa-history"></i> Lịch Sử Nhập Kho</h2>
        <div class="block">
            
            <form method="GET" action="" class="filter-bar">
                <div class="quick-filters">
                    <button type="button" class="btn-quick" onclick="setDateRange('today')">Hôm nay</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('week')">Tuần này</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('month')">Tháng này</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('all')">Tất cả</button>
                </div>

                <div class="date-filters">
                    <div class="date-group">
                        <i class="fa fa-calendar-alt"></i> Từ: 
                        <input type="date" name="tungay" id="tungay" value="<?php echo $tungay; ?>" class="filter-input">
                    </div>
                    <div class="date-group">
                        <i class="fa fa-arrow-right"></i> Đến: 
                        <input type="date" name="denngay" id="denngay" value="<?php echo $denngay; ?>" class="filter-input">
                    </div>
                    <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Lọc dữ liệu</button>
                </div>
            </form>

            <table class="data display" id="historyTable">
                <thead>
                    <tr>
                        <th width="10%">Mã Phiếu</th>
                        <th width="15%">Ngày Nhập</th>
                        <th width="15%">Người Nhập</th>
                        <th width="25%">Ghi Chú</th>
                        <th width="15%" class="text-right">Tổng Tiền</th>
                        <th width="15%" class="text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($list_phieu) {
                        $tong_tien_tat_ca = 0;
                        while ($row = $list_phieu->fetch_assoc()) {
                            $date = date('d/m/Y H:i', strtotime($row['ngay_nhap']));
                            $tong_tien_tat_ca += $row['tong_tien'];
                    ?>
                        <tr data-id="<?php echo $row['id_phieu']; ?>">
                            <td class="bold-blue"><?php echo $row['ma_phieu']; ?></td>
                            <td><?php echo $date; ?></td>
                            <td><i class="fa fa-user-circle text-muted"></i> <?php echo htmlspecialchars($row['nhan_vien']); ?></td>
                            <td class="text-muted"><?php echo $row['ghi_chu']; ?></td>
                            <td class="text-right bold-red">
                                <?php echo number_format($row['tong_tien'], 0, ',', '.'); ?> 
                            </td>
                            <td class="text-center">
                                <div class="action-group">
                                    <button class="btn-action btn-view" onclick="toggleDetail(this, <?php echo $row['id_phieu']; ?>)">
                                        <i class="fa fa-eye"></i> Xem
                                    </button>
                                    <a href="in_phieunhap.php?id=<?php echo $row['id_phieu']; ?>" target="_blank" class="btn-action btn-print">
                                        <i class="fa fa-print"></i> In
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    }
                    ?>
                </tbody>
                <?php if(isset($tong_tien_tat_ca)): ?>
                <tfoot>
                    <tr style="background:#e8f5e9; font-weight:bold; font-size:15px;">
                        <td colspan="4" class="text-right">TỔNG TIỀN GIAI ĐOẠN NÀY:</td>
                        <td class="text-right" style="color:#198754; font-size:18px;"><?php echo number_format($tong_tien_tat_ca, 0, ',', '.'); ?> VNĐ</td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>

        </div>
    </div>
</div>

<script>
    var table;
    $(document).ready(function() {
        table = $('#historyTable').DataTable({
            "order": [], 
            "language": { "search": "Tìm nhanh:", "paginate": { "next": "Sau", "previous": "Trước" }, "info": "Hiện _START_ - _END_ trong _TOTAL_ phiếu" }
        });
    });

    function setDateRange(type) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;

        let start = '', end = todayStr;

        // Xử lý active class cho đẹp
        $('.btn-quick').removeClass('active');
        
        if (type === 'today') {
            start = todayStr;
        } else if (type === 'week') {
            const day = today.getDay() || 7; 
            if (day !== 1) today.setHours(-24 * (day - 1));
            start = today.toISOString().split('T')[0];
        } else if (type === 'month') {
            start = `${yyyy}-${mm}-01`;
        } else if (type === 'all') {
            start = ''; end = '';
        }

        document.getElementById('tungay').value = start;
        document.getElementById('denngay').value = end;
        document.querySelector('.filter-bar').submit();
    }

    function toggleDetail(btn, id) {
        var tr = $(btn).closest('tr');
        var row = table.row(tr);
        if (row.child.isShown()) {
            row.child.hide(); tr.removeClass('shown'); 
            $(btn).html('<i class="fa fa-eye"></i> Xem').css('background', '#17a2b8');
        } else {
            $(btn).html('<i class="fa fa-spinner fa-spin"></i>').css('opacity', '0.7');
            $.get('lichsunhapkho.php?ajax=detail&id=' + id, function(data) {
                row.child(data).show(); tr.addClass('shown'); 
                $(btn).html('<i class="fa fa-minus"></i> Ẩn').css({'background': '#6c757d', 'opacity': '1'});
            });
        }
    }
</script>

<?php include 'inc/footer.php'; ?>