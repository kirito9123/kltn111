<?php
/* ================== AJAX: TRẢ VỀ CHI TIẾT ĐƠN HÀNG ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');

    $id_hd = isset($_GET['id_hd']) ? (int)$_GET['id_hd'] : 0;
    if ($id_hd <= 0) {
        echo '<div class="p-3 text-danger">Không tìm thấy mã đơn hàng.</div>';
        exit;
    }

    // ĐƯỜNG DẪN: Thoát ra khỏi admin để vào classes
    if (file_exists(__DIR__ . '/../classes/nhanvienquay.php')) {
        include_once __DIR__ . '/../classes/nhanvienquay.php';
    } else {
        echo "Lỗi: Không tìm thấy file classes/nhanvienquay.php";
        exit;
    }
    
    $nv = new nhanvienquay();

    // 1. Lấy thông tin chung
    $info_rs = $nv->get_thong_tin_don_hang($id_hd);
    $info = ($info_rs) ? $info_rs->fetch_assoc() : null;

    // 2. Lấy danh sách món
    $list_mon = $nv->get_chi_tiet_mon_an($id_hd);

    if ($info) {
        $vitri = "";
        if (!empty($info['so_ban']) && $info['so_ban'] != '0') $vitri .= "Bàn: " . $info['so_ban'];
        if (!empty($info['phong'])) $vitri .= ($vitri ? " - " : "") . "Phòng: " . $info['phong'];
        
        echo "<div class='row'>";
        // Cột trái: Info khách
        echo "<div class='col-md-4' style='border-right:1px solid #ddd;'>
                <h5 style='color:#0d6efd; margin-bottom:15px;'>Thông tin khách hàng</h5>
                <table class='combo-detail' style='width:100%'>
                    <tr><th style='width:35%'>Khách hàng</th><td>".htmlspecialchars($info['tenKH'])."</td></tr>
                    <tr><th>Ngày đặt</th><td>{$info['dates']}</td></tr>
                    <tr><th>Giờ vào</th><td>{$info['tg']}</td></tr>
                    <tr><th>Vị trí</th><td><b>{$vitri}</b></td></tr>
                    <tr><th>Ghi chú</th><td>".nl2br(htmlspecialchars($info['ghichu'] ?? ''))."</td></tr>
                </table>
              </div>";

        // Cột phải: List món
        echo "<div class='col-md-8'>
                <h5 style='color:#e74c3c; margin-bottom:15px;'>Danh sách gọi món</h5>
                <div class='table-responsive'>
                    <table class='combo-detail' style='width:100%'>
                        <thead>
                            <tr style='background:#f1f3f5;'>
                                <th>Món ăn</th>
                                <th class='text-center'>SL</th>
                                <th class='text-end'>Đơn giá</th>
                                <th class='text-end'>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>";
        if ($list_mon) {
            $total_check = 0;
            while ($m = $list_mon->fetch_assoc()) {
                // Kiểm tra tên cột trong bảng hopdong_chitiet (thường là thanhtien)
                // Nếu bảng đó cũng dùng thanhtien thì giữ nguyên, nếu là thanh_tien thì sửa lại ở đây
                $tt_mon = isset($m['thanhtien']) ? $m['thanhtien'] : 0; 
                $total_check += $tt_mon;
                echo "<tr>
                        <td>".htmlspecialchars($m['name_mon'])."</td>
                        <td class='text-center'>{$m['soluong']}</td>
                        <td class='text-end'>".number_format($m['gia'], 0, ',', '.')."</td>
                        <td class='text-end'>".number_format($tt_mon, 0, ',', '.')."</td>
                      </tr>";
            }
            echo "<tr><td colspan='3' class='text-end'><b>Tổng cộng món:</b></td><td class='text-end'><b style='color:red;'>".number_format($total_check, 0, ',', '.')." VNĐ</b></td></tr>";
        } else {
            echo "<tr><td colspan='4' class='text-center'>Chưa gọi món nào</td></tr>";
        }
        echo "</tbody></table></div></div></div>";
    } else {
        echo "<div class='p-3'>Không tìm thấy thông tin đơn hàng #$id_hd.</div>";
    }
    exit;
}
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
// ĐƯỜNG DẪN: Thoát ra khỏi admin để vào classes
include_once __DIR__ . '/../classes/nhanvienquay.php';
$nv = new nhanvienquay();
$list_orders = $nv->show_don_hang_chua_thanh_toan();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  .thead-blue th { background:#0d6efd; color:#fff; text-transform:uppercase; font-size:14px; }
  .btn-same { display:inline-flex; align-items:center; justify-content:center; min-width:80px; height:32px; padding:0 .5rem; font-size:.85rem; border-radius:4px; color:#fff; border:none; cursor:pointer; font-weight:600; text-decoration: none; }
  .btn-detail { background:#17a2b8; }
  .btn-edit   { background:#f39c12; }
  .btn-pay    { background:#2ecc71; }
  .btn-same + .btn-same { margin-left:5px; }
  .dt-child-box { background:#fff; padding:20px; border:2px solid #17a2b8; border-radius:6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
  .combo-detail { width:100%; border-collapse:collapse; }
  .combo-detail th, .combo-detail td { border:1px solid #dee2e6; padding:8px 10px; font-size: 14px;}
  .combo-detail thead th { background:#e9ecef; font-weight:700; text-align: center;}
  .combo-detail th { background:#f8f9fa; color: #333; }
  .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
  .st-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
  .st-deposit { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
  .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
  .col-md-4, .col-md-8 { padding-right: 15px; padding-left: 15px; position: relative; width: 100%; }
  @media (min-width: 768px) {
      .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
      .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
  }
</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Danh sách đơn hàng chờ xử lý</h2>
    <div class="block">
        <table class="table table-bordered text-center display" id="orderTable">
            <thead class="thead-blue">
                <tr>
                    <th>STT</th>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Vị trí</th>
                    <th>Thời gian</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Chi tiết</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php
                if ($list_orders) {
                    $i = 0;
                    while ($r = $list_orders->fetch_assoc()) {
                        $i++;
                        $id = (int)$r['id'];
                        $vitri = "";
                        if (!empty($r['so_ban']) && $r['so_ban'] != '0') $vitri .= "Bàn " . $r['so_ban'];
                        if (!empty($r['phong'])) $vitri .= ($vitri ? " - " : "") . $r['phong'];
                        if (empty($vitri)) $vitri = "Mang về / Chưa xếp";
                        
                        if ($r['payment_status'] == 'completed') {
                            $stt_lbl = '<span class="badge-status" style="background:#28a745; color:#fff;">Hoàn tất</span>';
                        } elseif ($r['payment_status'] == 'pending') {
                            $stt_lbl = '<span class="badge-status st-pending">Chưa TT</span>';
                        } else {
                            // Trường hợp deposit hoặc các trạng thái khác
                            $stt_lbl = '<span class="badge-status st-deposit">Đã cọc</span>';
                        }
                                                
                        // SỬA LỖI Ở ĐÂY: Dùng đúng tên cột 'thanhtien'
                        $tong_tien = isset($r['thanhtien']) ? $r['thanhtien'] : 0;

                       $is_completed = ($r['payment_status'] == 'completed');

                        echo "<tr data-id='$id'>
                                <td>$i</td>
                                <td><b>#$id</b></td>
                                <td style='text-align:left; font-weight:bold;'>" . htmlspecialchars($r['tenKH']) . "</td>
                                <td>$vitri</td>
                                <td>{$r['dates']}<br><small style='color:gray'>{$r['tg']}</small></td>
                                <td style='font-weight:bold; color:#d63031;'>" . number_format($tong_tien, 0, ',', '.') . "</td>
                                <td>$stt_lbl</td>
                                <td><button class='btn-same btn-detail' data-id='$id'>Xem món</button></td>
                                <td>";

                       if ($is_completed) {
                            echo "<div style='display:flex; gap:5px; justify-content:center;'>
                                    <a class='btn-same' style='background:#6c757d;' href='invoice.php?id=$id' target='_blank' title='In hóa đơn'>
                                        <i class='fa fa-print'></i> In Hóa đơn
                                    </a>
                                </div>";
                        } else {
                            // Nếu chưa xong -> Hiện nút Sửa và Thanh toán bình thường
                            echo "<a class='btn-same btn-edit' href='booking_edit.php?id=$id'>Sửa</a>";
                            // Lưu ý: Nút thanh toán trỏ về booking_edit để mở Modal
                            echo "<a class='btn-same btn-pay' href='booking_edit.php?id=$id&open_pay=1'>Thanh toán</a>";
                        }

                        echo "  </td>
                            </tr>";
                    }
                }
            ?>
            </tbody>
        </table>
    </div>
  </div>
</div>

<script>
$(function(){
  const table = $('#orderTable').DataTable({
      pageLength: 10, lengthChange: false,
      language: { search: "", searchPlaceholder: "Tìm khách, mã đơn...", paginate: { previous: "Trước", next: "Sau" }, emptyTable: "Không có đơn chờ", info: "Hiển thị _START_–_END_ trong _TOTAL_ đơn" },
      order: [[0, 'asc']]
  });
  $('#orderTable').on('click', '.btn-detail', function(){
      const btn = $(this), id = btn.data('id'), tr = btn.closest('tr'), row = table.row(tr);
      if (row.child.isShown()) { row.child.hide(); tr.removeClass('shown'); btn.text('Xem món').css('background', '#17a2b8'); return; }
      btn.prop('disabled', true).text('Đang tải...');
      fetch('booking_list.php?ajax=ct&id_hd=' + id).then(r => r.text()).then(html => {
            row.child('<div class="dt-child-box">' + html + '</div>').show(); tr.addClass('shown'); btn.text('Đóng').css('background', '#6c757d');
      }).finally(() => btn.prop('disabled', false));
  });
});
</script>
<?php include 'inc/footer.php'; ?>