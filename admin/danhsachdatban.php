<?php
ob_start(); // optional

include_once __DIR__ . '/../classes/phong.php';
include_once __DIR__ . '/../classes/ban.php';
include_once __DIR__ . '/../lib/database.php';

$phong = new phong();
$db    = new Database();
$banSv = new BanService($db);

/* ====== FILTER ====== */
$selLoaiPhong = isset($_GET['maloaiphong']) ? (int)$_GET['maloaiphong'] : 0;
$selPhong     = isset($_GET['id_phong'])    ? (int)$_GET['id_phong']    : 0;
$selLoaiBan   = isset($_GET['id_loaiban'])  ? (int)$_GET['id_loaiban']  : 0;

/* ====== POST: redirect sớm ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['bulk_action'] ?? '';
    $ids    = array_filter(array_map('intval', $_POST['tables'] ?? []));
    $ids    = array_values(array_unique($ids));

    // Nếu bấm Order mà chưa chọn bàn -> quay lại kèm msg
    if ($action === 'order') {
        if (empty($ids)) {
            $qs = http_build_query([
                'maloaiphong' => $selLoaiPhong,
                'id_phong'    => $selPhong,
                'id_loaiban'  => $selLoaiBan,
                'msg'         => 'ChuaChonBan'
            ]);
            $to = "danhsachdatban.php?$qs";
        } else {
            $to = 'order.php?ban_ids=' . implode(',', $ids);
        }

        if (!headers_sent()) {
            header("Location: $to");
            ob_end_flush();
            exit;
        }
        // Fallback nếu headers đã gửi
        echo "<meta http-equiv='refresh' content='0;url=".htmlspecialchars($to,ENT_QUOTES,'UTF-8')."'>";
        echo "<script>location.replace(".json_encode($to).");</script>";
        ob_end_flush();
        exit;
    }

    // Các action khác (hold/book/free) nếu có thì xử lý tiếp...
}

/* ====== TỪ ĐÂY MỚI include UI ====== */
include 'inc/header.php';
include 'inc/sidebar.php';

/* ... phần render HTML bên dưới giữ nguyên ... */


/* ====== NẠP DROPDOWN ====== */
$loaiphong_rs = $phong->show_loaiphong();
$loaiban_rs   = $phong->show_loaiban();
$phong_rs     = ($selLoaiPhong>0) ? $phong->show_phong_by_loai($selLoaiPhong) : null;

/* ====== LẤY BÀN ====== */
$ban_rs = null;
if ($selLoaiPhong>0 && $selPhong>0 && $selLoaiBan>0) {
    $ban_rs = $banSv->getBanByPhongLoaiBan($selPhong, $selLoaiBan);
}

/* ====== FLASH ====== */
$flash = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<style>
.box .block { padding: 16px; }
.filter-bar { display:grid; grid-template-columns:repeat(4,minmax(200px,1fr)) auto; gap:10px; align-items:end; }
.filter-bar .form-group { display:flex; flex-direction:column; gap:6px; }
.filter-bar label { font-weight:600; }
.filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:6px; }
.btn { padding:8px 12px; border-radius:6px; border:1px solid transparent; cursor:pointer; }
.btn-primary { background:#0d6efd; color:#fff; border-color:#0d6efd; }
.btn-outline { background:#fff; color:#0d6efd; border-color:#0d6efd; }
.btn-danger { background:#dc3545; color:#fff; border-color:#dc3545; }
.btn:disabled { opacity:.6; cursor:not-allowed; }

.legend { display:flex; align-items:center; gap:14px; margin: 12px 0 8px; }
.legend .dot { width:14px; height:14px; border-radius:4px; display:inline-block; }
.dot-free { background:#28a745; } .dot-busy { background:#dc3545; }
.dot-hold { background:#ffc107; } .dot-unknown { background:#6c757d; }

.bulk-bar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:8px 0 12px; }
.bulk-bar .count { font-weight:600; }
.bulk-bar .spacer { flex:1; }

/* ====== GRID BÀN ====== */
.table-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); }
.table-item {
  border:1px solid #eee; border-radius:10px; padding:10px;
  display:block; text-align:center; background:#fff; min-height:96px;
  cursor:pointer; position:relative; user-select:none;
  transition: border-color .15s ease, box-shadow .15s ease, transform .05s ease, background-color .15s ease;
}
.table-item .table-icon   { font-size:22px; margin-bottom:6px; display:block; }
.table-item .table-number { font-weight:600; margin-bottom:2px; }
.table-item .table-status { font-size:12px; opacity:.8; }

/* trạng thái */
.table-item.free  { border-color:#28a745; }
.table-item.free .table-icon { color:#28a745; }
.table-item.busy  { border-color:#dc3545; opacity:.92; background:#fff7f7; pointer-events:none; }
.table-item.busy .table-icon { color:#dc3545; }
.table-item.hold  { border-color:#ffc107; background:#fffdf2; }
.table-item.hold .table-icon { color:#ffc107; }

/* Hover/Active */
.table-item:hover  { box-shadow:0 3px 10px rgba(0,0,0,.08); }
.table-item:active { transform:translateY(0); }

/* Ẩn checkbox */
.table-item input[type="checkbox"]{ display:none; }

/* Khi được chọn */
.table-item.chosen{
  border-color:#0d6efd !important;
  box-shadow:0 0 0 3px rgba(13,110,253,.25), 0 6px 16px rgba(13,110,253,.18);
  transform:translateY(-1px); background:#f4f8ff;
}
.table-item .table-check{
  content:''; position:absolute; top:6px; right:6px;
  width:22px; height:22px; border-radius:50%;
  background:#0d6efd; box-shadow:0 2px 6px rgba(13,110,253,.35);
  color:#fff; font-weight:700; font-size:14px; line-height:22px; text-align:center;
  opacity:0; transform:scale(.85);
  transition:opacity .15s ease, transform .15s ease;
}
.table-item.chosen .table-check{ opacity:1; transform:scale(1); }
.table-item.chosen .table-check::before{ content:'✓'; }

/* ====== POPUP GIỮ CHỖ ====== */
.hold-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:9999; }
.hold-modal{ width:min(560px, 92vw); background:#fff; border-radius:12px; padding:16px 16px 18px; box-shadow:0 10px 30px rgba(0,0,0,.25); }
.hold-modal h3{ margin:0 0 10px; font-size:18px; }
.hold-form .row{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.hold-form .form-group{ display:flex; flex-direction:column; gap:6px; margin-top:8px; }
.hold-form label{ font-weight:600; font-size:13px; }
.hold-form input, .hold-form textarea{ padding:8px 10px; border:1px solid #ddd; border-radius:8px; font-size:14px; }
.hold-form textarea{ min-height:80px; resize:vertical; }
.hold-actions{ display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }
.hold-actions .btn{ padding:8px 12px; border-radius:8px; cursor:pointer; border:1px solid transparent; }
.hold-actions .btn-secondary{ background:#f3f4f6; border-color:#e5e7eb; }
.hold-actions .btn-primary{ background:#0d6efd; color:#fff; border-color:#0d6efd; }
@media (max-width:640px){ .hold-form .row{ grid-template-columns:1fr; } }

/* ====== POPUP CHỌN THANH TOÁN ====== */
.pay-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:10000; }
.pay-modal{ width:min(520px, 92vw); background:#fff; border-radius:12px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,.25); }
.pay-grid{ display:grid; gap:10px; grid-template-columns:1fr 1fr; }
.pay-card{ border:1px solid #eee; border-radius:10px; padding:12px; cursor:pointer; text-align:left; }
.pay-card:hover{ box-shadow:0 3px 12px rgba(0,0,0,.08); }
.pay-actions{ margin-top:10px; text-align:right; }

/* ====== POPUP XÁC NHẬN TIỀN MẶT ====== */
.cash-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:10001; }
.cash-modal{ width:min(420px, 92vw); background:#fff; border-radius:12px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,.25); }
.cash-actions{ display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }

/* ====== POPUP THÔNG BÁO KẾT QUẢ ====== */
.result-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:11000; }
.result-modal{ width:min(460px, 92vw); background:#fff; border-radius:12px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,.25); }
.result-modal h3{ margin:0 0 8px; font-size:18px; }
.result-modal p{ margin:6px 0 0; }
.result-actions{ margin-top:12px; text-align:right; }
</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Danh Sách Bàn</h2>

    <div class="block">
      <?php if (!empty($flash)): ?>
        <div id="flashAlert" class="alert <?php echo (stripos($flash,'lỗi')!==false ? 'alert-error' : 'alert-success'); ?>">
          <?php echo htmlspecialchars($flash); ?>
        </div>
      <?php endif; ?>

      <!-- Bộ lọc -->
      <form method="get" class="filter-bar" style="margin-bottom:8px;">
        <div class="form-group">
          <label>Loại phòng</label>
          <select name="maloaiphong" onchange="this.form.submit()">
            <option value="">-- Chọn loại phòng --</option>
            <?php if ($loaiphong_rs) { while ($lp = $loaiphong_rs->fetch_assoc()) { ?>
              <option value="<?php echo (int)$lp['maloaiphong']; ?>" <?php echo $selLoaiPhong==(int)$lp['maloaiphong']?'selected':''; ?>>
                <?php echo htmlspecialchars($lp['tenloaiphong']); ?>
              </option>
            <?php }} ?>
          </select>
        </div>

        <div class="form-group">
          <label>Phòng</label>
          <select name="id_phong" <?php echo ($selLoaiPhong>0?'':'disabled'); ?> onchange="this.form.submit()">
            <option value="">-- Chọn phòng --</option>
            <?php if ($phong_rs) { while ($p = $phong_rs->fetch_assoc()) { ?>
              <option value="<?php echo (int)$p['id_phong']; ?>" <?php echo $selPhong==(int)$p['id_phong']?'selected':''; ?>>
                <?php echo htmlspecialchars($p['tenphong']); ?>
              </option>
            <?php }} ?>
          </select>
        </div>

        <div class="form-group">
          <label>Loại bàn</label>
          <select name="id_loaiban" <?php echo ($selPhong>0?'':'disabled'); ?> onchange="this.form.submit()">
            <option value="">-- Chọn loại bàn --</option>
            <?php if ($loaiban_rs) { while ($lb = $loaiban_rs->fetch_assoc()) { ?>
              <option value="<?php echo (int)$lb['id_loaiban']; ?>" <?php echo $selLoaiBan==(int)$lb['id_loaiban']?'selected':''; ?>>
                <?php echo htmlspecialchars($lb['tenloaiban']); ?>
              </option>
            <?php }} ?>
          </select>
        </div>

        <div class="form-group">
          <a href="danhsachdatban.php" class="btn btn-outline">Làm mới</a>
        </div>
      </form>

      <!-- Legend -->
      <div class="legend">
        <span><i class="dot dot-free"></i> Trống</span>
        <span><i class="dot dot-busy"></i> Đã đặt</span>
        <span><i class="dot dot-hold"></i> Giữ chỗ</span>
        <span><i class="dot dot-unknown"></i> Không rõ</span>
      </div>

      <?php if ($selLoaiPhong>0 && $selPhong>0 && $selLoaiBan>0): ?>
        <!-- Hành động -->
        <form method="post" id="bulkForm">
          <input type="hidden" name="maloaiphong" value="<?php echo (int)$selLoaiPhong; ?>">
          <input type="hidden" name="id_phong"    value="<?php echo (int)$selPhong; ?>">
          <input type="hidden" name="id_loaiban"  value="<?php echo (int)$selLoaiBan; ?>">
          <input type="hidden" name="bulk_action" id="bulkAction" value="hold">
          <input type="hidden" name="payment_method" id="paymentMethod" value="cash"><!-- default -->

          <div class="bulk-bar">
            <div class="count">Đã chọn: <span id="pickedCount">0</span> bàn</div>
            <div class="spacer"></div>

            <button type="button" id="openHoldForm" class="btn btn-primary" onclick="setAction('hold')">Giữ chỗ</button>
            <button type="submit" class="btn btn-primary" onclick="setAction('book')">Đánh dấu đã đặt</button>
            <button type="submit" class="btn btn-danger"  onclick="setAction('free')">Trả bàn</button>
            <button type="submit" class="btn btn-success" onclick="setAction('order')">Order</button>
          </div>

          <div class="table-grid" id="banGrid">
          <?php
          if ($ban_rs && $ban_rs->num_rows>0) {
            while ($b = $ban_rs->fetch_assoc()) {
              $cls  = BanService::statusClass((int)$b['trangthai']);
              $txt  = BanService::statusText($cls);
              $bid  = (int)$b['id_ban'];
              $dis  = ($cls === 'busy') ? 'disabled' : ''; // KHÓA nếu đã đặt

              echo '<label class="table-item '.$cls.'" data-id="'.$bid.'" aria-disabled="'.($dis?'true':'false').'">';
              echo '  <input type="checkbox" name="tables[]" value="'.$bid.'" '.$dis.'>';
              echo '  <div class="table-icon"><i class="fas fa-table"></i></div>';
              echo '  <div class="table-number">'.htmlspecialchars($b['tenban']).'</div>';
              echo '  <div class="table-status">'.$txt.'</div>';
              echo '  <span class="table-check" aria-hidden="true"></span>';
              echo '</label>';
            }
          } else {
            echo '<div class="info-box" style="grid-column:1/-1;"><i class="fas fa-info-circle"></i><span>Không có bàn phù hợp cho bộ lọc hiện tại.</span></div>';
          }
          ?>
          </div>
        </form>
      <?php else: ?>
        <div class="info-box" style="margin-top:10px%;">
          <i class="fas fa-info-circle"></i>
          <span>Chọn <b>Loại phòng → Phòng → Loại bàn</b> để xem & chọn bàn.</span>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- POPUP FORM GIỮ CHỖ -->
<div class="hold-modal-backdrop" id="holdBackdrop" aria-hidden="true">
  <div class="hold-modal" role="dialog" aria-modal="true" aria-labelledby="holdTitle">
    <h3 id="holdTitle">Thông tin giữ chỗ</h3>
    <div class="hold-form">
      <div class="row">
        <div class="form-group">
          <label>Họ và tên</label>
          <input type="text" id="hold_name" placeholder="Nhập họ tên">
        </div>
        <div class="form-group">
          <label>Số điện thoại</label>
          <input type="text" id="hold_phone" placeholder="Nhập số điện thoại">
        </div>
      </div>

      <div class="row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="hold_email" placeholder="Nhập email (nếu có)">
        </div>
        <div class="form-group">
          <label>Ngày đến</label>
          <input type="date" id="hold_date">
        </div>
      </div>

      <div class="row">
        <div class="form-group">
          <label>Giờ đến</label>
          <input type="time" id="hold_time" value="19:00">
        </div>
        <div class="form-group"><!-- chừa trống --></div>
      </div>

      <div class="form-group">
        <label>Ghi chú</label>
        <textarea id="hold_note" placeholder="Yêu cầu thêm (nếu có)"></textarea>
      </div>

      <div class="hold-actions">
        <button type="button" class="btn btn-secondary" id="holdCancel">Huỷ</button>
        <button type="button" class="btn btn-primary" id="holdConfirm">Xác nhận giữ chỗ</button>
      </div>
    </div>
  </div>
</div>

<!-- POPUP CHỌN THÔNG TIN THANH TOÁN -->
<div class="pay-modal-backdrop" id="payBackdrop" aria-hidden="true">
  <div class="pay-modal" role="dialog" aria-modal="true" aria-labelledby="payTitle">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <h3 id="payTitle" style="margin:0;font-size:18px;">Chọn phương thức thanh toán</h3>
      <button type="button" id="payClose" style="border:0;background:transparent;font-size:22px;cursor:pointer;line-height:1">×</button>
    </div>
    <div class="pay-grid">
      <button type="button" class="pay-card" id="payCashBtn">
        <div style="font-size:22px">💵</div>
        <div style="font-weight:600;margin-top:6px">Thanh toán tiền mặt</div>
        <div style="opacity:.8">Xác nhận tại quầy</div>
      </button>
      <button type="button" class="pay-card" id="payQRBtn">
        <div style="font-size:22px">📱</div>
        <div style="font-weight:600;margin-top:6px">Thanh toán bằng QR</div>
        <div style="opacity:.8">Chuyển sang VNPAY</div>
      </button>
    </div>
    <div class="pay-actions">
      <button type="button" class="btn btn-secondary" id="payCancel">Đóng</button>
    </div>
  </div>
</div>

<!-- POPUP XÁC NHẬN TIỀN MẶT -->
<div class="cash-modal-backdrop" id="cashBackdrop" aria-hidden="true">
  <div class="cash-modal" role="dialog" aria-modal="true" aria-labelledby="cashTitle">
    <h3 id="cashTitle" style="margin:0 0 8px;">Xác nhận thanh toán tiền mặt</h3>
    <div style="opacity:.9">Bạn chắc chắn xác nhận giữ chỗ và thanh toán tiền mặt tại quầy?</div>
    <div class="cash-actions">
      <button type="button" class="btn btn-secondary" id="cashCancel">Huỷ</button>
      <button type="button" class="btn btn-primary" id="cashConfirm">Xác nhận</button>
    </div>
  </div>
</div>

<!-- POPUP THÔNG BÁO KẾT QUẢ -->
<div class="result-modal-backdrop" id="resultBackdrop" aria-hidden="true">
  <div class="result-modal" role="dialog" aria-modal="true" aria-labelledby="resultTitle">
    <h3 id="resultTitle">Thông báo</h3>
    <p id="resultMsg"></p>
    <div class="result-actions">
      <button type="button" class="btn btn-primary" id="resultClose">OK</button>
    </div>
  </div>
</div>

<script>
function setAction(a){ document.getElementById('bulkAction').value = a; }

/* Toggle chọn bàn */
(() => {
  const grid = document.getElementById('banGrid');
  const countEl = document.getElementById('pickedCount');
  if (!grid) return;

  const updateCount = () => {
    if (countEl) countEl.textContent = grid.querySelectorAll('input[type="checkbox"]:checked').length;
  };

  grid.addEventListener('change', (e) => {
    if (!e.target.matches('input[type="checkbox"]')) return;
    const item = e.target.closest('.table-item');
    if (item) item.classList.toggle('chosen', e.target.checked);
    updateCount();
  });

  grid.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    const item = cb.closest('.table-item');
    if (item) item.classList.toggle('chosen', cb.checked);
  });
  updateCount();
})();

/* ====== Popup giữ chỗ + thanh toán ====== */
(function(){
  const openBtn    = document.getElementById('openHoldForm');
  const backdrop   = document.getElementById('holdBackdrop');
  const cancelBtn  = document.getElementById('holdCancel');
  const confirmBtn = document.getElementById('holdConfirm');
  const bulkForm   = document.getElementById('bulkForm');
  const banGrid    = document.getElementById('banGrid');

  const payBackdrop  = document.getElementById('payBackdrop');
  const payClose     = document.getElementById('payClose');
  const payCancel    = document.getElementById('payCancel');
  const payCashBtn   = document.getElementById('payCashBtn');
  const payQRBtn     = document.getElementById('payQRBtn');
  const paymentField = document.getElementById('paymentMethod');

  const cashBackdrop = document.getElementById('cashBackdrop');
  const cashCancel   = document.getElementById('cashCancel');
  const cashConfirm  = document.getElementById('cashConfirm');

  function openModal(){
    if (!banGrid) return;
    const picked = banGrid.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
    if (picked.length === 0){
      alert('Vui lòng chọn ít nhất 1 bàn trống trước khi giữ chỗ.');
      return;
    }
    const d = document.getElementById('hold_date');
    if (d){
      const today = new Date(); today.setHours(0,0,0,0);
      d.min = today.toISOString().split('T')[0];
    }
    backdrop.style.display = 'flex';
  }
  function closeModal(){ backdrop.style.display = 'none'; }

  if (openBtn)   openBtn.addEventListener('click', openModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
  backdrop.addEventListener('click', (e)=>{ if (e.target === backdrop) closeModal(); });

  // Bước 1: xác nhận thông tin KH -> mở chọn thanh toán
  if (confirmBtn) confirmBtn.addEventListener('click', function(){
    const name  = document.getElementById('hold_name').value.trim();
    const phone = document.getElementById('hold_phone').value.trim();
    const date  = document.getElementById('hold_date').value;
    const time  = document.getElementById('hold_time').value;

    if (!name || !phone || !date || !time){
      alert('Vui lòng nhập đầy đủ: Họ tên, SĐT, Ngày đến, Giờ đến.');
      return;
    }

    const payload = {
      hold_name: name,
      hold_phone: phone,
      hold_email: document.getElementById('hold_email').value.trim(),
      hold_date: date,
      hold_time: time,
      hold_note: document.getElementById('hold_note').value.trim()
    };
    Object.entries(payload).forEach(([k,v])=>{
      let hid = bulkForm.querySelector('input[name="'+k+'"]');
      if (!hid){
        hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = k;
        bulkForm.appendChild(hid);
      }
      hid.value = v;
    });

    closeModal();
    openPay();
  });

  // Popup chọn thanh toán
  function openPay(){ payBackdrop.style.display = 'flex'; }
  function closePay(){ payBackdrop.style.display = 'none'; }
  if (payClose)  payClose.addEventListener('click', closePay);
  if (payCancel) payCancel.addEventListener('click', closePay);
  payBackdrop.addEventListener('click', (e)=>{ if (e.target === payBackdrop) closePay(); });

  // Tiền mặt -> mở confirm nhỏ
  if (payCashBtn) payCashBtn.addEventListener('click', () => {
    paymentField.value = 'cash';
    cashBackdrop.style.display = 'flex';
  });

  // QR -> submit với payment_method=qr (server sẽ lưu & redirect sang VNPAY)
  if (payQRBtn) payQRBtn.addEventListener('click', () => {
    paymentField.value = 'qr';
    closePay();
    bulkForm.submit();
  });

  // Popup confirm tiền mặt
  function closeCash(){ cashBackdrop.style.display = 'none'; }
  if (cashCancel)  cashCancel.addEventListener('click', closeCash);
  cashBackdrop.addEventListener('click', (e)=>{ if (e.target === cashBackdrop) closeCash(); });
  if (cashConfirm) cashConfirm.addEventListener('click', () => {
    closeCash();
    closePay();
    bulkForm.submit(); // submit với payment_method=cash
  });
})();

/* ====== Bật popup kết quả khi có msg ====== */
(function(){
  const msgFromServer = <?php echo json_encode($flash); ?>;
  if (!msgFromServer) return;

  const bd  = document.getElementById('resultBackdrop');
  const msg = document.getElementById('resultMsg');
  const btn = document.getElementById('resultClose');
  const alertDiv = document.getElementById('flashAlert');

  if (alertDiv) alertDiv.style.display = 'none'; // ẩn alert cũ nếu có
  if (bd && msg) {
    msg.textContent = msgFromServer;
    bd.style.display = 'flex';
    bd.addEventListener('click', (e)=>{ if (e.target === bd) bd.style.display = 'none'; });
    if (btn) btn.addEventListener('click', ()=> bd.style.display = 'none');
  }
})();

(function () {
  var url = new URL(window.location.href);
  var keys = ['maloaiphong','id_phong','id_loaiban','msg'];
  if (keys.some(function(k){ return url.searchParams.has(k); })) {
    // Ẩn toàn bộ phần ?... trên thanh địa chỉ
    history.replaceState(null, '', url.pathname);
  }
})();
</script>

<?php include 'inc/footer.php'; ?>
