<?php
/* ================= Helpers cho AJAX: dọn buffer + set header sạch ================= */
if (!function_exists('ajax_start_headers')) {
  function ajax_start_headers($type = 'json') {
    @ini_set('display_errors','0');
    @error_reporting(E_ERROR | E_PARSE);
    while (ob_get_level() > 0) { @ob_end_clean(); }
    header($type === 'json'
      ? 'Content-Type: application/json; charset=utf-8'
      : 'Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  }
}

/* ================== AJAX: TRẢ VỀ CHI TIẾT HỢP ĐỒNG (THEO hopdong_id) ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    ajax_start_headers('html');

    $hopdong_id = isset($_GET['hopdong_id']) ? (int)$_GET['hopdong_id'] : 0;
    if ($hopdong_id <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai ID hợp đồng.</div>';
        exit;
    }

    // Dùng class HopDong để lấy chi tiết
    $filepath = realpath(dirname(__FILE__));
    include_once $filepath . '/../lib/database.php';
    include_once $filepath . '/../helpers/format.php';
    include_once $filepath . '/../classes/hopdong.php';

    $hd = new HopDong();
    $rs = $hd->getOrderDetailsByHopdongId($hopdong_id);

    if ($rs && $rs->num_rows > 0) {
        $i = 0; $tong = 0;
        echo '<div class="table-responsive">
                <table class="order-detail">
                  <thead>
                    <tr>
                      <th class="col-stt">#</th>
                      <th>Món</th>
                      <th class="col-qty">Số lượng</th>
                      <th class="col-money">Giá</th>
                      <th class="col-money">Thành tiền</th>
                    </tr>
                  </thead>
                  <tbody>';
        while ($r = $rs->fetch_assoc()) {
            $i++;
            $gia = (float)$r['gia'];
            $tt  = (float)$r['thanhtien'];
            $tong += $tt;

            echo '<tr>
                    <td class="text-center">'.$i.'</td>
                    <td>'.htmlspecialchars($r['name_mon']).'</td>
                    <td class="text-right">'.(int)$r['soluong'].'</td>
                    <td class="text-right">'.number_format($gia,0,',','.').' VNĐ</td>
                    <td class="text-right">'.number_format($tt ,0,',','.').' VNĐ</td>
                  </tr>';
        }
        echo    '</tbody>
                </table>
                <div class="order-total">Tổng: '.number_format($tong,0,',','.').' VNĐ</div>
              </div>';
    } else {
        echo '<div class="p-3">Hợp đồng này chưa có chi tiết món.</div>';
    }
    exit;
}

/* === AJAX: tổng tiền theo hopdong_id (sạch JSON, có fallback) === */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'total') {
    ajax_start_headers('json');

    try {
        $hopdong_id = isset($_GET['hopdong_id']) ? (int)$_GET['hopdong_id'] : 0;
        if ($hopdong_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Bad id']); exit; }

        $filepath = realpath(dirname(__FILE__));
        include_once $filepath . '/../lib/database.php';

        $db = new Database();

        // Ưu tiên SUM chi tiết; nếu không có dòng nào/NULL → lấy h.thanhtien → cuối cùng 0
        $sql = "
          SELECT COALESCE((
              SELECT SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, 0)))
              FROM hopdong_chitiet c
              WHERE c.hopdong_id = h.id
          ), h.thanhtien, 0) AS total
          FROM hopdong h
          WHERE h.id = {$hopdong_id}
          LIMIT 1
        ";

        $rs = $db->select($sql);

        if ($rs === false) {
            $err = (isset($db->link) && $db->link) ? ($db->link->error ?? 'SQL error') : 'SQL error';
            echo json_encode(['ok'=>false,'msg'=>'Lỗi truy vấn: '.$err]); exit;
        }

        $total = 0;
        if ($rs && $rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            $total = (float)($row['total'] ?? 0);
        }

        echo json_encode(['ok'=>true, 'total'=>$total]); exit;
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'msg'=>'Exception: '.$e->getMessage()]); exit;
    }
}

/* === AJAX: tạo Session cho VNPay (dùng cùng công thức tổng tiền) === */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'make_vnpay_session') {
    ajax_start_headers('json');

    $hopdong_id = isset($_POST['hopdong_id']) ? (int)$_POST['hopdong_id'] : 0;
    $ptype = isset($_POST['type']) && $_POST['type']==='deposit' ? 'deposit' : 'full';

    if ($hopdong_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Bad id']); exit; }

    $filepath = realpath(dirname(__FILE__));
    include_once $filepath . '/../lib/session.php';
    include_once $filepath . '/../lib/database.php';

    Session::init();
    $db = new Database();

    $sql = "
      SELECT COALESCE((
          SELECT SUM(COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, 0)))
          FROM hopdong_chitiet c
          WHERE c.hopdong_id = h.id
      ), h.thanhtien, 0) AS total
      FROM hopdong h
      WHERE h.id = {$hopdong_id}
      LIMIT 1
    ";
    $rs = $db->select($sql);
    if ($rs === false) {
        $err = (isset($db->link) && $db->link) ? ($db->link->error ?? 'SQL error') : 'SQL error';
        echo json_encode(['ok'=>false,'msg'=>'Lỗi truy vấn: '.$err]); exit;
    }
    $row = $rs && $rs->num_rows ? $rs->fetch_assoc() : null;
    $total = (float)($row['total'] ?? 0);

    $amount_to_pay = ($ptype === 'deposit') ? (int)round($total * 0.20) : (int)round($total);
    if ($amount_to_pay <= 0) $amount_to_pay = 30000; // tối thiểu, tương thích VNPay code cũ

    // Set Session để vnpay_cre.php & vnpay_return.php dùng
    // Set Session để vnpay_cre.php & vnpay_return.php dùng
    $info = ($ptype==='deposit')
        ? "Thanh toán đặt cọc đơn đặt bàn #{$hopdong_id}"
        : "Thanh toán toàn bộ đơn đặt bàn #{$hopdong_id}";
    Session::set('order_booking_id',  $hopdong_id);
    Session::set('order_amount',      $amount_to_pay);
    Session::set('order_payment_type',$ptype);
    Session::set('order_payment_info',$info);
    Session::set('order_data_deposit',$amount_to_pay);

    /* THÊM DÒNG NÀY */
    @session_write_close();

    echo json_encode(['ok'=>true,'amount'=>$amount_to_pay,'type'=>$ptype]); exit;

}
/* ================== HẾT PHẦN AJAX ================== */
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$filepath = realpath(dirname(__FILE__));
include_once $filepath . '/../lib/database.php';
include_once $filepath . '/../helpers/format.php';
include_once $filepath . '/../classes/hopdong.php';

$db = new Database();
$fm = new Format();
$hd = new HopDong();

/* ====== LẤY DANH SÁCH HỢP ĐỒNG CHƯA THANH TOÁN XONG (KÈM THÔNG TIN BÀN) ====== */
$filter = $_GET['filter'] ?? 'all'; // mặc định lấy tất cả
$list = $hd->getUnpaidOrdersWithBanSummary($filter);

/* ====== VIEW HELPERS ====== */
function view_status_badge($st) {
    $st = strtolower(trim((string)$st));
    if ($st === 'deposit')   return '<span class="badge badge-info">Đã đặt cọc</span>';
    if ($st === 'completed') return '<span class="badge badge-success">Đã thanh toán</span>';
    if ($st === 'pending' || $st === '' || $st === '0' || $st === 'null' || $st === null)
        return '<span class="badge badge-warning" style="color:#000;">Chưa thanh toán</span>';
    return '<span class="badge badge-light">Không rõ</span>';
}
function money_vn($n) { return number_format((float)$n, 0, ',', '.') . ' VNĐ'; }

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

function render_ban_cell(?string $tenban): string {
    $t = trim((string)$tenban);
    if ($t === '') return '-';

    // tách theo dấu phẩy, bỏ khoảng trắng dư
    $arr = array_values(array_filter(array_map(function($x){
        $x = trim($x);
        return $x === '' ? null : $x;
    }, preg_split('/\s*,\s*/', $t))));

    if (!$arr) return '-';

    // tạo badge
    $badges = array_map(fn($name) => '<span class="tag-ban" title="'.h($name).'">'.h($name).'</span>', $arr);

    // chỉ 1 bàn -> không hiện mũi tên
    if (count($arr) === 1) return $badges[0];

    // nhiều bàn -> hiện mũi tên và dropdown
    $first = $badges[0];
    $all   = implode(' ', $badges);
    $more  = count($arr) - 1;

    return '
    <div class="ban-wrap">
      <span class="ban-summary">'.$first.' <span class="ban-more">+'. $more .'</span>
        <button type="button" class="ban-toggle" aria-label="Xem tất cả bàn" aria-expanded="false">▾</button>
      </span>
      <div class="ban-dropdown" hidden>'.$all.'</div>
    </div>';
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .thead-blue th { background:#0d6efd; color:#fff; text-transform:uppercase; font-size:14px; }
    #orders_filter { display:flex; align-items:center; justify-content:flex-end; gap:10px; }

    /* Buttons cùng kích thước */
    .btn-same { 
        display:inline-flex; align-items:center; justify-content:center;
        min-width:95px; height:34px; padding:0 .75rem;
        font-size:.9rem; border-radius:6px; color:#fff; border:none; cursor:pointer; font-weight:600;
    }
    .btn-detail { background:#17a2b8; }
    .btn-pay    { background:#2ecc71; }
    .btn-same + .btn-same { margin-left:6px; }

    /* Child detail box */
    .dt-child-box { background:#f9f9f9; padding:12px; border:1px solid #eee; border-radius:6px; }

    /* Bảng chính */
    table#orders td, table#orders th { vertical-align: middle; }
    table#orders th:nth-child(2), table#orders td:nth-child(2) { width: 130px; }
    table#orders th:nth-child(3), table#orders td:nth-child(3) { width: 180px; }
    table#orders th:nth-child(4), table#orders td:nth-child(4) { width: 120px; }
    table#orders th:nth-child(6), table#orders td:nth-child(6) { width: 150px; }
    table#orders th:nth-child(7), table#orders td:nth-child(7) { width: 150px; }
    table#orders th:nth-child(8), table#orders td:nth-child(8) { width: 230px; }

    /* Bảng chi tiết trong child */
    .order-detail { width:100%; border-collapse:collapse; }
    .order-detail th, .order-detail td { border:1px solid #dee2e6; padding:8px 10px; }
    .order-detail thead th { background:#f1f3f5; font-weight:600; }
    .order-detail .col-stt { width:52px; text-align:center; }
    .order-detail .col-qty { width:100px; }
    .order-detail .col-money { width:140px; }
    .order-detail .text-right { text-align:right; }
    .order-detail .text-center { text-align:center; }
    .order-total { margin-top:8px; font-weight:700; text-align:right; padding-right:10px; }

    /* Filter trạng thái */
    #filter-status{ width:180px; border-radius:6px; padding:8px; border:1px solid #ccc; background:#f9f9f9; font-size:14px; }
    #filter { background:#f8f9fa; font-size: 14px; cursor: pointer; }
    #filter:hover { background:#e9ecef; }

    #filter-time { padding:6px 10px; border-radius:6px; border:1px solid #ccc; background:#f8f9fa; font-size:14px; cursor:pointer; }
    #filter-time:hover { background:#e9ecef; }

    .tag-ban{
      display:inline-block; padding:4px 8px; margin:2px 4px 0 0;
      border-radius:6px; background:#eef2ff; color:#1e40af; font-weight:600;
      font-size:12px; line-height:1;
    }
    .ban-wrap{ position:relative; display:inline-block; }
    .ban-toggle{
      margin-left:6px; border:0; background:transparent; cursor:pointer;
      font-size:12px; line-height:1; padding:0 4px; color:#333;
    }
    .ban-more{ font-size:12px; color:#6b7280; font-weight:600; }
    .ban-dropdown{
      position:absolute; top:100%; left:0; z-index:1000;
      background:#fff; border:1px solid #e5e7eb; border-radius:8px;
      padding:8px; box-shadow:0 10px 20px rgba(0,0,0,.12); min-width:220px;
      max-width:360px; max-height:220px; overflow:auto; white-space:normal; text-align:left;
    }

</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Thanh toán hợp đồng (chưa thanh toán xong)</h2>

    <div class="block" id="table-container">
      <table class="table table-bordered text-center display" id="orders">
        <thead class="thead-blue">
          <tr>
            <th>#</th>
            <th>ID Hợp đồng</th>
            <th>Bàn</th>
            <th>Ngày</th>
            <th>Giờ</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th><center>Tùy chọn</center></th>
          </tr>
        </thead>
        <tbody>
          <?php
            if ($list) {
                $i = 0;
                while ($r = $list->fetch_assoc()) {
                    $i++;
                    $id        = (int)$r['hopdong_id'];
                    $tenban    = trim($r['tenban'] ?? '');
                    $dates     = htmlspecialchars($r['dates'] ?? '');
                    $tg        = htmlspecialchars($r['tg'] ?? '');
                    $sum       = (float)($r['tongtien'] ?? 0);
                    $pstat     = $r['payment_status'] ?? '';

                    echo '<tr data-id="'.$id.'">
                            <td>'.$i.'</td>
                            <td>'.$id.'</td>
                            <td>'.render_ban_cell($tenban).'</td>
                            <td>'.$dates.'</td>
                            <td>'.$tg.'</td>
                            <td>'.money_vn($sum).'</td>
                            <td>'.view_status_badge($pstat).'</td>
                            <td>
                              <button type="button" class="btn-same btn-detail" data-id="'.$id.'">Chi tiết</button>
                              <button type="button" class="btn-same btn-pay"    data-id="'.$id.'">Thanh toán</button>
                            </td>
                          </tr>';
                }
            } else {
                echo "<tr><td colspan='8'>Không có hợp đồng cần thanh toán.</td></tr>";
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(function(){
  const table = $('#orders').DataTable({
    pageLength: 10,
    lengthChange: false,
    language: {
      search: "",
      searchPlaceholder: "Tìm theo ID, bàn, ngày...",
      paginate: { previous: "Trang trước", next: "Trang sau" },
      info: "Hiển thị _START_–_END_ trong _TOTAL_ hợp đồng",
      emptyTable: "Không có dữ liệu",
      infoEmpty: "Không có dữ liệu để hiển thị"
    },
    order: [[0, 'asc']]
  });

  // Bộ lọc trạng thái (cột index 6)
  const stSel = $(
    '<select id="filter-status">' +
      '<option value="">Tất cả trạng thái</option>' +
      '<option value="Chưa thanh toán">Chưa thanh toán</option>' +
      '<option value="Đã đặt cọc">Đã đặt cọc</option>' +
    '</select>'
  );
  $('#orders_filter').append(stSel);
  $('#filter-status').on('change', function(){
    const val = $(this).val();
    table.column(6).search(val ? val : '', true, false).draw();
  });

  // Dropdown lọc theo thời gian (thêm trên thanh filter)
  const currentFilter = <?= json_encode($filter) ?>;
  const timeSel = $(
    '<select id="filter-time">' +
      '<option value="all">📅 Lọc theo thời gian</option>' +
      '<option value="today">Hôm nay</option>' +
      '<option value="week">Tuần này</option>' +
      '<option value="month">Tháng này</option>' +
    '</select>'
  );
  timeSel.val(currentFilter && currentFilter !== '' ? currentFilter : 'all');
  $('#orders_filter').prepend(timeSel);
  $(document).on('change', '#filter-time', function(){
    const val = $(this).val();
    const u = new URL(window.location.href);
    u.searchParams.set('filter', val);
    window.location.href = u.toString();
  });

  // Child row: xem chi tiết hợp đồng
  $('#orders').on('click', '.btn-detail', function(){
    const btn = $(this);
    const id  = btn.data('id');
    const tr  = btn.closest('tr');
    const row = table.row(tr);

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      btn.text('Chi tiết');
      return;
    }
    btn.prop('disabled', true).text('Đang tải...');
    fetch('thanhtoanhopdong.php?ajax=ct&hopdong_id=' + encodeURIComponent(id))
      .then(r => r.text())
      .then(html => {
        const box = '<div class="dt-child-box">' + html + '</div>';
        row.child(box).show();
        tr.addClass('shown');
        btn.text('Ẩn');
      })
      .catch(() => {
        row.child('<div class="dt-child-box text-danger p-3">Lỗi khi tải chi tiết.</div>').show();
        tr.addClass('shown');
        btn.text('Ẩn');
      })
      .finally(() => { btn.prop('disabled', false); });
  });

  // Scroll lên đầu bảng khi đổi trang
  table.on('page.dt', function () {
    $('html, body').animate({ scrollTop: $('#table-container').offset().top }, 'slow');
  });
});

// ====== THANH TOÁN (delegated) ======
let PM_ID = 0;      // hopdong_id đang thanh toán
let PM_DUE = 0;     // số phải thu (server trả)
function vn(n){ try{return (n||0).toLocaleString('vi-VN');}catch(_){return n;} }

function openPayModal(hopdong_id){
  PM_ID = hopdong_id;
  $('#pm_hd').text(hopdong_id);
  $('#pm_paid').val(''); $('#pm_change').text('0'); $('#pm_cash_box').hide();
  $('#pm_due').text('...');

  // Lấy tổng tiền từ server (robust)
  fetch('thanhtoanhopdong.php?ajax=total&hopdong_id=' + encodeURIComponent(hopdong_id), {
    method: 'GET',
    headers: { 'Accept': 'application/json' },
  })
  .then(async (r) => {
    let text = await r.text();
    if (!r.ok) throw new Error('HTTP ' + r.status + ': ' + text);
    text = text.replace(/^\uFEFF/, '').trim(); // loại BOM + khoảng trắng
    try { return JSON.parse(text); }
    catch (e){ throw new Error('Phản hồi không phải JSON: ' + text); }
  })
  .then((j) => {
    if (j && j.ok) {
      PM_DUE = Math.round(j.total || 0);
      $('#pm_due').text(vn(PM_DUE));
    } else {
      const msg = (j && j.msg) ? j.msg : 'Không lấy được tổng tiền.';
      Swal.fire({icon:'error', title:'Lỗi lấy tổng tiền', text: msg});
      PM_DUE = 0; $('#pm_due').text('0');
    }
  })
  .catch((err) => {
    Swal.fire({icon:'error', title:'Lỗi lấy tổng tiền', text: String(err)});
    console.error(err);
    PM_DUE = 0; $('#pm_due').text('0');
  });

  $('#payModal').fadeIn(120);
}

// Mở modal khi bấm nút "Thanh toán"
$(document).on('click', '.btn-pay', function(e){
  e.preventDefault();
  const id = parseInt($(this).data('id')||0);
  if(!id) return;
  openPayModal(id);
});

// Đóng modal
$(document).on('click', '#pm_close', ()=> $('#payModal').fadeOut(120));

// Chọn TIỀN MẶT: mở khung nhập
$(document).on('click', '#pm_cash',  ()=> { $('#pm_cash_box').slideDown(120); });

// Tính tiền thối realtime
$(document).on('input', '#pm_paid', function(){
  const paid = parseFloat($(this).val()||0);
  const change = Math.max(0, paid - PM_DUE);
  $('#pm_change').text(vn(change));
});

// XÁC NHẬN TIỀN MẶT → pay_cash.php (dùng fetch + SweetAlert2)
$(document).on('click', '#pm_cash_confirm', function(){
  const paid = parseFloat($('#pm_paid').val()||0);
  if (PM_DUE <= 0){
    Swal.fire({icon:'warning', title:'Số tiền phải thu không hợp lệ'});
    return;
  }
  if (paid < PM_DUE){
    Swal.fire({icon:'warning', title:'Khách đưa chưa đủ tiền', text:'Vui lòng nhập số tiền ≥ số phải thu'});
    return;
  }

  const body = new URLSearchParams({
    hopdong_id: String(PM_ID),
    amount_due: String(PM_DUE),
    amount_paid: String(paid)
  }).toString();

  fetch('pay_cash.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      'Accept': 'application/json'
    },
    body
  })
  .then(r => r.text())
  .then(text => {
    text = (text || '').replace(/^\uFEFF/, '').trim();
    let res;
    try { res = JSON.parse(text); }
    catch {
      Swal.fire({icon:'error', title:'Phản hồi không phải JSON', text: text});
      return;
    }

    if (res.ok) {
      Swal.fire({
        icon: 'success',
        title: 'Thanh toán thành công!',
        text: 'Đơn #' + PM_ID + ' đã được cập nhật trạng thái.',
        timer: 1800,
        timerProgressBar: true,
        confirmButtonText: 'OK'
      }).then(() => location.reload());
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Lỗi khi thanh toán',
        text: res.message ? res.message : 'Có lỗi khi thanh toán tiền mặt.'
      });
    }
  })
  .catch(err => {
    Swal.fire({icon:'error', title:'Lỗi khi gọi pay_cash.php', text: err.message});
    console.error(err);
  });
});

// Chọn QR (VNPay) → tạo Session trước, rồi redirect sang vnpay_cre.php
$(document).on('click', '#pm_qr', function(){
  const body = new URLSearchParams({ hopdong_id: String(PM_ID), type: 'full' }).toString();

  fetch('thanhtoanhopdong.php?ajax=make_vnpay_session', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      'Accept': 'application/json'
    },
    credentials: 'same-origin', // <-- THÊM DÒNG NÀY
    body
  })
  .then(r => r.text())
  .then(text => {
    const clean = (text || '').replace(/^\uFEFF/, '').trim();
    let res; try { res = JSON.parse(clean); } catch {
      throw new Error('Phản hồi không phải JSON: ' + clean);
    }
    if (res && res.ok) {
      // Nếu vnpay_cre.php nằm trong /admin/ thì để nguyên đường dẫn dưới.
      // Nếu nằm thư mục khác, đổi path tương ứng.
      window.location.href = 'vnpay_cre.php?hopdong_id=' + PM_ID + '&type=full';
    } else {
      Swal.fire({icon:'error', title:'Không tạo được phiên thanh toán', text: (res && res.msg) ? res.msg : 'Lỗi không xác định'});
    }
  })
  .catch(err => {
    Swal.fire({icon:'error', title:'Không tạo được phiên thanh toán', text: String(err)});
  });
});



// Toggle danh sách bàn trong ô "Bàn"
$(document).on('click', '.ban-toggle', function (e) {
  e.stopPropagation();
  const btn  = $(this);
  const wrap = btn.closest('.ban-wrap');
  const box  = wrap.find('.ban-dropdown');

  const expanded = btn.attr('aria-expanded') === 'true';
  // đóng mọi dropdown khác trước
  $('.ban-dropdown').attr('hidden', true);
  $('.ban-toggle').attr('aria-expanded', 'false');

  if (!expanded) {
    box.attr('hidden', false);
    btn.attr('aria-expanded', 'true');
  }
});

// click ra ngoài -> đóng
$(document).on('click', function () {
  $('.ban-dropdown').attr('hidden', true);
  $('.ban-toggle').attr('aria-expanded', 'false');
});

</script>

<!-- Modal thanh toán -->
<div id="payModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:9999;">
  <div style="max-width:520px; margin:40px auto; background:#fff; border-radius:12px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,.2)">
    <h3 style="margin:0 0 10px">Thanh toán hợp đồng #<span id="pm_hd"></span></h3>
    <div style="margin:6px 0 10px">
      <div><b>Số tiền phải thu:</b> <span id="pm_due" class="text-danger">0</span> VNĐ</div>
    </div>

    <div style="display:flex; gap:10px; margin:12px 0">
      <button id="pm_cash"  class="btn-same" style="background:#b07b33">Tiền mặt</button>
      <button id="pm_qr"    class="btn-same" style="background:#355cde">QR (VNPay)</button>
      <button id="pm_close" class="btn-same" style="background:#6c757d">Đóng</button>
    </div>

    <!-- Khung xác nhận tiền mặt -->
    <div id="pm_cash_box" style="display:none; margin-top:6px;">
      <div style="margin-bottom:8px">
        <label>Khách đưa (VNĐ):</label>
        <input id="pm_paid" type="number" min="0" step="1000" style="width:220px; padding:6px">
      </div>
      <div><b>Tiền thối:</b> <span id="pm_change">0</span> VNĐ</div>
      <div style="margin-top:10px">
        <button id="pm_cash_confirm" class="btn-same" style="background:#2ecc71">Xác nhận tiền mặt</button>
      </div>
    </div>
  </div>
</div>

<?php include 'inc/footer.php'; ?>
