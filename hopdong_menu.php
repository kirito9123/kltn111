<?php
/* ===== Helpers bảo đảm AJAX output sạch (GIỮ NGUYÊN CỦA BẠN) ===== */
function ajax_start_headers($type='json'){
    @ini_set('display_errors','0'); @error_reporting(E_ERROR|E_PARSE);
    while (ob_get_level()>0) { @ob_end_clean(); }
    header($type==='json'
        ? 'Content-Type: application/json; charset=utf-8'
        : 'Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}
function ajax_json($arr){ while (ob_get_level()>0){@ob_end_clean();} echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

/* ================== AJAX: JSON CHI TIẾT COMBO (GIỮ NGUYÊN) ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'combo_items') {
    ajax_start_headers('json');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) ajax_json(['ok'=>false,'msg'=>'Thiếu id','items'=>[]]);

    if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
    if (!isset($db) || !($db instanceof Database) || !isset($db->link)) {
        $db = new Database(); if (isset($db->link)) { @$db->link->set_charset('utf8mb4'); }
    }

    try {
        $sql = "SELECT mo.id_mon, mo.name_mon, mo.gia_mon, mct.so_luong AS sl
                FROM menu_chitiet mct INNER JOIN monan mo ON mo.id_mon=mct.id_mon
                WHERE mct.ma_menu={$id} AND mo.xoa=0 ORDER BY mct.id ASC";
        $rs = $db->select($sql); $items=[];
        if ($rs) while($r=$rs->fetch_assoc()){
            $items[] = [
                'id_mon'=>(int)$r['id_mon'],
                'name_mon'=>(string)$r['name_mon'],
                'gia_mon'=>(float)$r['gia_mon'],
                'sl'=>max(1,(int)$r['sl'])
            ];
        }
        ajax_json(['ok'=>true,'items'=>$items]);
    } catch (Throwable $e) {
        ajax_json(['ok'=>false,'msg'=>'Lỗi server: '.$e->getMessage(),'items'=>[]]);
    }
}

/* ================== AJAX: HTML CHI TIẾT COMBO (GIỮ NGUYÊN) ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'combo_detail_html') {
    ajax_start_headers('html');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { echo '<div class="p-3 text-danger">Thiếu hoặc sai id.</div>'; exit; }

    if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
    if (!isset($db) || !($db instanceof Database) || !isset($db->link)) {
        $db = new Database(); if (isset($db->link)) { @$db->link->set_charset('utf8mb4'); }
    }

    try {
        $sql = "SELECT mo.name_mon, mo.gia_mon, mct.so_luong AS sl,
                        (mct.so_luong*mo.gia_mon) AS thanh_tien
                FROM menu_chitiet mct INNER JOIN monan mo ON mo.id_mon=mct.id_mon
                WHERE mct.ma_menu={$id} AND mo.xoa=0 ORDER BY mct.id ASC";
        $rs = $db->select($sql);
        if ($rs && $rs->num_rows>0){
            $i=0;$tong=0;
            echo '<div class="dt-child-box"><table class="combo-detail-table"><thead><tr>
                    <th class="col-stt">#</th><th>Món</th><th class="col-qty">Số lượng</th>
                    <th class="col-money">Giá</th><th class="col-money">Thành tiền</th>
                  </tr></thead><tbody>';
            while($r=$rs->fetch_assoc()){
                $i++; $gia=(float)$r['gia_mon']; $tt=(float)$r['thanh_tien']; $tong+=$tt;
                echo '<tr><td class="text-center">'.$i.'</td><td>'.htmlspecialchars($r['name_mon']).'</td>
                        <td class="text-center">'.(int)$r['sl'].'</td>
                        <td class="text-right">'.number_format($gia,0,',','.').' VNĐ</td>
                        <td class="text-right">'.number_format($tt ,0,',','.').' VNĐ</td></tr>';
            }
            echo '</tbody></table><div class="combo-total">Tổng: '.number_format($tong,0,',','.').' VNĐ</div></div>';
        } else {
            echo '<div class="p-3">Combo này chưa có món nào.</div>';
        }
    } catch (Throwable $e) {
        echo '<div class="p-3 text-danger">Lỗi server: '.htmlspecialchars($e->getMessage()).'</div>';
    }
    exit;
}
?>

<?php
/* ======== PHẦN TRANG CHÍNH ======== */
include 'inc/header.php';
Session::checkSession();

/* Đảm bảo $db có sẵn nhưng KHÔNG khai báo trùng class */
if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
if (!isset($db) || !($db instanceof Database) || !isset($db->link)) {
    $db = new Database();
    if (isset($db->link)) { @$db->link->set_charset('utf8mb4'); }
}

/* ====== LƯU LỰA CHỌN (POST) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chonmon = $_POST['chonmon'] ?? [];
    $soluong = $_POST['soluong'] ?? [];
    $menu_chon = [];
    foreach ($chonmon as $id_mon => $val) {
        $sl = isset($soluong[$id_mon]) ? (int)$soluong[$id_mon] : 0;
        if ($sl > 0) $menu_chon[] = ['id_mon' => (int)$id_mon, 'soluong' => $sl];
    }
    Session::set('menu_chon', $menu_chon);
    header('Location: hopdong.php');
    exit();
}

/* ====== THAM SỐ LỌC & PHÂN TRANG (món lẻ) ====== */
$id_loai = isset($_GET['id_loai']) ? (int)$_GET['id_loai'] : 0;
$limit=15; $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1; $offset = ($page - 1) * $limit;
$where = "WHERE xoa = 0"; if ($id_loai > 0) $where .= " AND id_loai = " . (int)$id_loai;
$total_rs   = $db->select("SELECT COUNT(*) AS cnt FROM monan {$where}");
$total_row = $total_rs ? $total_rs->fetch_assoc() : ['cnt' => 0];
$total     = (int)$total_row['cnt'];
$total_pages = max(1, (int)ceil($total / $limit));
$ds_mon = $db->select("
    SELECT id_mon, name_mon, gia_mon, images
    FROM monan
    {$where}
    ORDER BY id_mon ASC
    LIMIT {$limit} OFFSET {$offset}
");

/* ====== LẤY DANH SÁCH LOẠI MÓN (SIDEBAR TRÁI) ====== */
$loai_map = [];
$loai_rs = $db->select("SELECT id_loai, name_loai FROM loai_mon WHERE xoa = 0 ORDER BY name_loai ASC");
if ($loai_rs) while ($r = $loai_rs->fetch_assoc()) $loai_map[(int)$r['id_loai']] = $r['name_loai'];

/* ====== HELPER: build URL giữ query ====== */
function page_url($newPage, $extra = []) {
    $params = $_GET;
    $params['page'] = max(1, (int)$newPage);
    foreach ($extra as $k => $v) { if ($v === null) unset($params[$k]); else $params[$k] = $v; }
    $base = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES);
    return $base . '?' . http_build_query($params) . '#category-section';
}

/* ====== LẤY DANH SÁCH COMBO HOẠT ĐỘNG (slider) ====== */
$combo_rs = $db->select("
    SELECT id_menu, ten_menu, ghi_chu, hinhanh
    FROM menu
    WHERE trang_thai = 0
    ORDER BY id_menu ASC
");

$combos = [];
if ($combo_rs) while($c = $combo_rs->fetch_assoc()){
    $combos[] = [
        'id_menu'  => (int)$c['id_menu'],
        'ten_menu' => $c['ten_menu'],
        'ghi_chu'  => $c['ghi_chu'],
        'hinhanh'  => !empty($c['hinhanh']) ? 'images/combo/'.$c['hinhanh'] : 'images/placeholder_combo.jpg'
    ];
}

/* ====== MODE ẩn món lẻ khi đang chọn combo ====== */
$isSetMode = (isset($_GET['mode']) && $_GET['mode'] === 'menu');
?>

<style>
/* --- CẤU TRÚC LAYOUT CHÍNH (Sửa lại cho cân đối) --- */
.container-full-width {
    width: 98%;
    max-width: 1400px; /* Giới hạn chiều rộng để không bị quá bè */
    margin: 0 auto;
    padding-bottom: 50px;
}

/* --- GRID & CARD (Giữ nguyên style của bạn) --- */
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:15px} /* Responsive grid */
.menu-card{border:1px solid #eee;border-radius:12px;padding:10px;text-align:center;transition:.2s;cursor:pointer;background:#fff; position: relative;}
.menu-card:hover{transform:scale(1.02);box-shadow:0 3px 8px rgba(0,0,0,.12)}
.menu-card.selected{border: 2px solid #007bff;} /* Viền xanh khi chọn */
/* Dấu tích xanh khi chọn */
.menu-card.selected::after {
    content: '\2713'; position: absolute; top: 5px; right: 5px;
    background: #007bff; color: white; border-radius: 50%; width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center; font-size: 12px;
}
.menu-card img{width:100%;height:140px;object-fit:cover;border-radius:10px}
.menu-price{color:#d19c65;font-weight:600;margin:6px 0}
/* Ẩn các input cũ đi */
.menu-card input[type="checkbox"], .menu-card input[type="number"], .menu-actions, .menu-tt {display:none !important;}

.cat-sidebar .list-group-item.active{background:#ffb900!important;border-color:#ffb900!important;color:#fff!important;font-weight:600}

/* --- BILL PANEL (Cột bên phải mới) --- */
.bill-panel {
    background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;
    position: sticky; top: 20px; /* Dính chặt khi cuộn */
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.bill-header { background: #007bff; color: white; padding: 15px; font-weight: bold; text-transform: uppercase; }
.bill-body { max-height: 500px; overflow-y: auto; }
.table-bill { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-bill th { background: #f8f9fa; padding: 8px; text-align: left; border-bottom: 1px solid #eee; position: sticky; top: 0; }
.table-bill td { padding: 8px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
.bill-qty-input { width: 40px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
.btn-del-mini { border: none; background: #ffebee; color: #d32f2f; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; }
.bill-footer { padding: 15px; background: #f9f9f9; border-top: 1px solid #eee; }
.bill-total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; margin-bottom: 10px; color: #d63031; }
.btn-checkout { width: 100%; background: #28a745; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; text-transform: uppercase; cursor: pointer; }
.btn-checkout:hover { background: #218838; }

/* --- COMBO SLIDER (Sửa lại nút nav) --- */
.combo-slider-wrap { position:relative; margin-bottom:40px; padding: 0 30px; } /* Thêm padding để nút ko đè ảnh */
.combo-slider-viewport { overflow:hidden; }
.combo-slider-track { display:grid; grid-auto-flow:column; grid-auto-columns: calc((100% - 16px)/2); gap:16px; transition: transform .35s ease; }
.combo-card{ border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.05); display:flex;flex-direction:column;}
.combo-thumb{ width:100%;height:180px;object-fit:cover;display:block;background:#f3f4f6; }
.combo-body{ padding:12px 14px; flex:1 1 auto; }
.combo-title{ font-weight:700; margin:0 0 6px; font-size:16px; }
.combo-note{ color:#666; font-size:13px; min-height:36px; }
.combo-actions{ display:flex; gap:8px; padding:12px 14px 16px; }
.combo-actions .btn{ flex:1 1 50%; border-radius:8px; padding:8px 10px; border:1px solid #ddd; background:#f8f9fa; cursor:pointer; }
.combo-actions .btn-primary{ background:#0d6efd; color:#fff; border-color:#0d6efd; }
.combo-actions .btn:hover{ filter: brightness(.98); }

/* Nút Nav Combo Sửa Lại */
.combo-nav-btn {
    position:absolute; top: 50%; transform: translateY(-50%); /* Căn giữa dọc */
    width:40px; height:40px; border-radius:50%; border:1px solid #ddd; background:#fff;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 8px rgba(0,0,0,.15); z-index: 10; font-size: 18px;
}
.combo-nav-btn:hover { background: #007bff; color: white; }
.combo-nav-btn.combo-prev { left: -10px; } /* Nằm bên trái */
.combo-nav-btn.combo-next { right: -10px; } /* Nằm bên phải */
.combo-nav-btn:disabled{ opacity:.5; cursor:not-allowed; }

/* Panel chi tiết */
.combo-detail{ display:none; padding:0 14px 14px; }
.combo-detail.open{ display:block; }
.combo-detail-table { width:100%; border-collapse:collapse; }
.combo-detail-table th, .combo-detail-table td { border:1px solid #eceff1; padding:8px 10px; }
.combo-detail-table thead th { background:#f6f8fa; font-weight:600; }
.combo-total { text-align:left !important; }
.combo-detail .col-qty{ width:90px; text-align:center; }
.combo-detail .col-money{ width:140px; text-align:right; white-space:nowrap; }

@media (max-width: 768px){ .combo-slider-track{ grid-auto-columns: 100%; } .bill-panel { margin-top: 20px; } }

/* Ẩn món lẻ nếu đang ở mode=menu (Giữ nguyên logic của bạn) */
<?php if ($isSetMode): ?>
#setmenu-section ~ .ftco-section .cat-sidebar { display: none !important; }
#setmenu-section ~ .ftco-section .menu-grid   { display: none !important; }
#setmenu-section ~ .ftco-section nav          { display: none !important; }
#setmenu-section ~ .ftco-section .bill-panel  { display: none !important; } /* Ẩn cả bill nếu ở mode menu */
#setmenu-section ~ .ftco-section form .text-center { display: block !important; }
body.show-extra-menu #setmenu-section ~ .ftco-section .cat-sidebar { display: block !important; }
body.show-extra-menu #setmenu-section ~ .ftco-section .menu-grid   { display: grid !important; }
body.show-extra-menu #setmenu-section ~ .ftco-section nav          { display: block !important; }
body.show-extra-menu #setmenu-section ~ .ftco-section .bill-panel  { display: block !important; }
<?php endif; ?>
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate text-center mb-4">
        <h1 class="mb-2 bread">Chọn món cho đơn đặt</h1>
      </div>
    </div>
  </div>
</section>

<section id="setmenu-section" class="ftco-section" style="margin-top:-50px;">
  <div class="container-full-width"> <h3 class="text-center mb-4"><?php echo $isSetMode ? 'Chọn Combo' : 'Gợi ý Combo'; ?></h3>

    <?php if (empty($combos)): ?>
      <div class="alert alert-warning text-center">Chưa có combo hoạt động.</div>
    <?php else: ?>
      <div class="combo-slider-wrap">
        <button type="button" class="combo-nav-btn combo-prev" id="comboPrev">‹</button>
        <button type="button" class="combo-nav-btn combo-next" id="comboNext">›</button>

        <div class="combo-slider-viewport">
          <div class="combo-slider-track" id="comboTrack">
            <?php foreach($combos as $c): ?>
              <div class="combo-card" data-idmenu="<?php echo (int)$c['id_menu']; ?>">
                <img class="combo-thumb" src="<?php echo htmlspecialchars($c['hinhanh']); ?>" alt="<?php echo htmlspecialchars($c['ten_menu']); ?>">
                <div class="combo-body">
                  <h5 class="combo-title"><?php echo htmlspecialchars($c['ten_menu']); ?></h5>
                  <div class="combo-note"><?php echo nl2br(htmlspecialchars($c['ghi_chu'])); ?></div>
                </div>
                <div class="combo-actions">
                  <button type="button" class="btn btn-light btn-xemthem">Xem thêm</button>
                  <button type="button" class="btn btn-primary btn-choncombo">Chọn combo này</button>
                </div>
                <div class="combo-detail">
                  <div class="detail-loading" style="padding:10px;color:#6c757d;">Đang tải chi tiết...</div>
                  <div class="detail-body" style="display:none;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isSetMode): ?>
      <div class="text-center mt-2">
        <button id="btnShowMore" type="button" class="btn btn-sm btn-secondary">➕ Thêm món lẻ</button>
      </div>
      <script>
      (function(){
        var btn = document.getElementById('btnShowMore');
        if (btn) btn.addEventListener('click', function(){
          document.body.classList.add('show-extra-menu');
          var sec = document.getElementById('category-section');
          if (sec && sec.scrollIntoView) sec.scrollIntoView({behavior:'smooth', block:'start'});
        });
      })();
      </script>
    <?php endif; ?>
  </div>
</section>

<section class="ftco-section" style="margin-top:-80px;">
  <div class="container-full-width">
    <div class="row" id="category-section">
      
      <aside class="col-md-2 mb-4">
        <div class="card shadow-sm cat-sidebar">
          <div class="card-header bg-warning"><strong>Loại món</strong></div>
          <div class="list-group list-group-flush">
            <a href="<?php echo page_url(1, ['id_loai' => null]); ?>" class="list-group-item list-group-item-action <?php echo ($id_loai===0?'active':''); ?>">Tất cả</a>
            <?php foreach ($loai_map as $lid => $lname): ?>
              <a href="<?php echo page_url(1, ['id_loai' => (int)$lid]); ?>" class="list-group-item list-group-item-action <?php echo ($id_loai===$lid?'active':''); ?>">
                <?php echo htmlspecialchars($lname); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

      <div class="col-md-6 mb-4">
        <form method="post" id="orderForm">
            <div class="menu-grid">
              <?php
              if ($ds_mon && $total > 0) {
                while ($m = $ds_mon->fetch_assoc()) {
                  $id  = (int)$m['id_mon'];
                  $gia = (float)$m['gia_mon'];
                  $img = !empty($m['images']) ? 'images/food/' . $m['images'] : 'images/placeholder.png';
                  $ten = $m['name_mon'];
              ?>
                <div class="menu-card" data-id="<?php echo $id; ?>" onclick="toggleCardById(<?php echo $id; ?>)">
                  <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($ten); ?>">
                  <h6 style="margin:8px 0 4px"><?php echo htmlspecialchars($ten); ?></h6>
                  <div class="menu-price" id="gia-<?php echo $id; ?>"><?php echo $gia; ?></div>
                  
                  <input type="checkbox" name="chonmon[<?php echo $id; ?>]" class="chon-mon" data-id="<?php echo $id; ?>" data-name="<?php echo htmlspecialchars($ten); ?>" data-price="<?php echo $gia; ?>">
                  <input type="number" min="1" name="soluong[<?php echo $id; ?>]" class="soluong-mon" data-id="<?php echo $id; ?>" value="1" disabled>
                </div>
              <?php
                }
              } else { echo '<div class="col-12 text-center">Không có món ăn phù hợp</div>'; }
              ?>
            </div>

            <?php if ($total_pages > 1): ?>
              <?php $start = max(1, $page - 2); $end = min($total_pages, $page + 2); ?>
              <nav class="mt-3">
                <ul class="pagination justify-content-center">
                  <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?php echo ($page > 1) ? page_url($page-1) : '#'; ?>">«</a>
                  </li>
                  <?php if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo page_url(1); ?>">1</a></li>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                  <?php endif; ?>
                  <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                      <a class="page-link" href="<?php echo page_url($i); ?>"><?php echo $i; ?></a>
                    </li>
                  <?php endfor; ?>
                  <?php if ($end < $total_pages): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <li class="page-item"><a class="page-link" href="<?php echo page_url($total_pages); ?>"><?php echo $total_pages; ?></a></li>
                  <?php endif; ?>
                  <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?php echo ($page < $total_pages) ? page_url($page+1) : '#'; ?>">»</a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
        </form>
      </div>

      <div class="col-md-4">
          <div class="bill-panel">
              <div class="bill-header">DANH SÁCH ĐÃ CHỌN</div>
              <div class="bill-body">
                  <table class="table-bill">
                      <thead>
                          <tr>
                              <th>Tên món</th>
                              <th class="text-center">SL</th>
                              <th class="text-right">Tiền</th>
                              <th></th>
                          </tr>
                      </thead>
                      <tbody id="bill-table-body">
                          <tr><td colspan="4" class="text-center text-muted p-3">Chưa chọn món nào</td></tr>
                      </tbody>
                  </table>
              </div>
              <div class="bill-footer">
                  <div class="bill-total-row">
                      <span>Tổng cộng:</span>
                      <span id="bill-total-display">0 VNĐ</span>
                  </div>
                  <button type="button" onclick="document.getElementById('orderForm').submit()" class="btn-checkout">
                      XÁC NHẬN ĐƠN HÀNG
                  </button>
              </div>
          </div>
      </div>

    </div>
  </div>
</section>

<script>
/* ===== Helper ===== */
function fmtVND(n){ try { return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND'}).format(n||0); } catch(e){ return (n||0).toLocaleString('vi-VN'); } }

/* ===== Slider 2 card/lần ===== */
(function(){
  const track = document.getElementById('comboTrack'); if (!track) return;
  const viewport = track.parentElement, prevBtn = document.getElementById('comboPrev'), nextBtn = document.getElementById('comboNext');
  let index = 0; const getPerView = () => (window.matchMedia('(max-width: 768px)').matches ? 1 : 2);
  const getMaxIndex = () => Math.max(0, track.children.length - getPerView());
  function update(){
    const card = track.children[0]; if (!card) return;
    const gap = 16, perView = getPerView(), viewportWidth = viewport.clientWidth;
    const cardWidth = Math.round((viewportWidth - (perView===2?gap:0)) / perView);
    const x = -(cardWidth + gap) * index; track.style.transform = 'translateX(' + x + 'px)';
    const maxIdx = getMaxIndex(); prevBtn.disabled = (index <= 0); nextBtn.disabled = (index >= maxIdx);
  }
  prevBtn.addEventListener('click', ()=>{ index = Math.max(0, index-1); update(); });
  nextBtn.addEventListener('click', ()=>{ index = Math.min(getMaxIndex(), index+1); update(); });
  window.addEventListener('resize', update); update();
})();

/* ===== Xem thêm & Toggle Chọn/Bỏ combo (AJAX Cũ) ===== */
document.addEventListener('DOMContentLoaded', function(){
  async function fetchComboItemsJSON(id_menu){
    const url = 'hopdong_menu.php?' + new URLSearchParams({ajax:'combo_items', id: id_menu});
    const res = await fetch(url, {cache:'no-store'});
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch(e){ try{ data = JSON.parse(text.replace(/^\uFEFF/,'').trim()); }catch(_){ throw new Error('Phản hồi không phải JSON hợp lệ'); } }
    if (!data.ok) throw new Error(data.msg || 'API trả về lỗi');
    return data.items || [];
  }
  async function fetchComboDetailHTML(id_menu){
    const url  = 'hopdong_menu.php?ajax=combo_detail_html&id=' + encodeURIComponent(id_menu);
    const res  = await fetch(url, {cache:'no-store'});
    if (!res.ok) throw new Error('HTTP '+res.status);
    return await res.text();
  }

  // Áp combo vào form (Giữ logic cũ, thêm renderBillPanel)
  function applyComboToForm(items){
    const form = document.querySelector('#category-section form') || document.querySelector('form'); if (!form) return;
    items.forEach(it=>{
      const id = parseInt(it.id_mon,10), qty = Math.max(1, parseInt(it.sl,10)), price = parseFloat(it.gia_mon)||0;

      let cb = document.querySelector(".chon-mon[data-id='"+id+"']");
      // Thêm data-name và data-price vào input tạo động để JS đọc được
      if (!cb){ cb = document.createElement('input'); cb.type='checkbox'; cb.name='chonmon['+id+']'; cb.className='chon-mon'; cb.dataset.id=id; cb.dataset.name=it.name_mon; cb.dataset.price=it.gia_mon; cb.style.display='none'; form.appendChild(cb); }

      let slEl = document.querySelector(".soluong-mon[data-id='"+id+"']");
      if (!slEl){
        const onCard = document.querySelector(".menu-card[data-id='"+id+"'] .soluong-mon");
        slEl = onCard ? onCard : (function(){ const i=document.createElement('input'); i.type='hidden'; i.name='soluong['+id+']'; i.className='soluong-mon'; i.dataset.id=id; form.appendChild(i); return i; })();
      }

      if (!document.getElementById('gia-'+id)) {
        const giaDiv = document.createElement('div'); giaDiv.id='gia-'+id; giaDiv.style.display='none'; giaDiv.innerText = price; form.appendChild(giaDiv);
      }

      cb.checked = true; slEl.value = qty; slEl.disabled = false;

      const card = document.querySelector(".menu-card[data-id='"+id+"']");
      if (card){ card.classList.add('selected'); const ttEl = document.getElementById("thanhtien-"+id); if (ttEl) ttEl.innerText = fmtVND(price*qty); }
    });

    // GỌI HÀM VẼ BẢNG
    renderBillPanel();
  }

  // Gỡ combo khỏi form
  function removeComboFromForm(items){
    const form = document.querySelector('#category-section form') || document.querySelector('form');
    if (!form) return;
    items.forEach(it=>{
      const id   = parseInt(it.id_mon,10);
      const cb   = document.querySelector(".chon-mon[data-id='"+id+"']");
      const slEl = document.querySelector(".soluong-mon[data-id='"+id+"']");
      const card = document.querySelector(".menu-card[data-id='"+id+"']");
      const ttEl = document.getElementById("thanhtien-"+id);
      if (cb)   cb.checked = false;
      if (slEl) slEl.disabled = true;
      if (ttEl) ttEl.innerText = '0';
      if (card) card.classList.remove('selected');
    });
    renderBillPanel();
  }

  // Nút Xem thêm
  document.querySelectorAll('.btn-xemthem').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const card = this.closest('.combo-card');
      const panel = card.querySelector('.combo-detail');
      const loading = panel.querySelector('.detail-loading');
      const body = panel.querySelector('.detail-body');
      const idmenu = parseInt(card.dataset.idmenu,10);

      if (panel.classList.contains('open')) { panel.classList.remove('open'); return; }
      panel.classList.add('open'); if (panel.dataset.loaded === '1') return;

      try{
        if (loading) loading.textContent = 'Đang tải chi tiết...';
        const html = await fetchComboDetailHTML(idmenu);
        body.innerHTML = html; if (loading) loading.style.display = 'none'; body.style.display = 'block'; panel.dataset.loaded = '1';
        try{ const items = await fetchComboItemsJSON(idmenu); card.dataset.items = JSON.stringify(items); }catch(e){ console.warn('Không cache được JSON items:', e.message); }
      }catch(e){ console.error(e); if (loading) loading.textContent = 'Lỗi khi tải chi tiết.'; }
    });
  });

  // Nút Chọn combo (toggle)
  document.querySelectorAll('.btn-choncombo').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const card = this.closest('.combo-card'); const idmenu = parseInt(card.dataset.idmenu,10);
      let items = [];
      if (card.dataset.items){ try { items = JSON.parse(card.dataset.items); } catch(e){} }
      if (!items || !items.length){
        try{ items = await fetchComboItemsJSON(idmenu); card.dataset.items = JSON.stringify(items); }
        catch(e){ alert('Không tải được chi tiết combo.\n' + (e && e.message ? e.message : '')); return; }
      }
      if (card.dataset.applied === '1'){
        removeComboFromForm(items);
        card.dataset.applied = '0';
        this.textContent = 'Chọn combo này';
      } else {
        applyComboToForm(items);
        card.dataset.applied = '1';
        this.textContent = 'Bỏ combo này';
      }
    });
  });
});

/* ===== LOGIC MỚI: ĐỒNG BỘ BẢNG CHI TIẾT (BILL PANEL) ===== */
document.addEventListener("DOMContentLoaded", function () {
  
  // Hàm vẽ lại bảng bên phải dựa trên checkbox
  window.renderBillPanel = function() {
      const tbody = document.getElementById('bill-table-body');
      const totalEl = document.getElementById('bill-total-display');
      let html = '';
      let total = 0;
      let count = 0;

      document.querySelectorAll('.chon-mon:checked').forEach(cb => {
          count++;
          const id = cb.dataset.id;
          // Lấy thông tin từ data-attr đã thêm trong PHP
          const name = cb.dataset.name || 'Món ' + id; 
          const price = parseFloat(cb.dataset.price) || 0;
          
          // Lấy số lượng từ input tương ứng
          const inputQty = document.querySelector(`.soluong-mon[data-id="${id}"]`);
          const qty = parseInt(inputQty.value) || 1;
          const subtotal = price * qty;
          total += subtotal;

          html += `
              <tr>
                  <td>
                      <div style="font-weight:bold;">${name}</div>
                      <small style="color:#777;">${fmtVND(price)}</small>
                  </td>
                  <td class="text-center">
                      <input type="number" class="bill-qty-input" value="${qty}" min="1" 
                             onchange="updateQtyFromBill(${id}, this.value)">
                  </td>
                  <td class="text-right" style="font-weight:bold;">${fmtVND(subtotal)}</td>
                  <td class="text-center">
                      <button type="button" class="btn-del-mini" onclick="removeItem(${id})">&times;</button>
                  </td>
              </tr>
          `;
      });

      if(count === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted p-3">Chưa chọn món nào</td></tr>';
          totalEl.innerText = '0 VNĐ';
      } else {
          tbody.innerHTML = html;
          totalEl.innerText = fmtVND(total);
      }
  };

  // Hàm Click Card (Gọi renderBillPanel thay vì tinhTong)
  window.toggleCardById = function(id) {
    const card = document.querySelector(`.menu-card[data-id='${id}']`);
    const cb   = document.querySelector(`.chon-mon[data-id='${id}']`);
    const sl   = document.querySelector(`.soluong-mon[data-id='${id}']`);
    if (!card || !cb || !sl) return;
    
    cb.checked = !cb.checked; 
    card.classList.toggle('selected', cb.checked);
    sl.disabled = !cb.checked; 
    if (cb.checked && (!sl.value || parseInt(sl.value,10) <= 0)) sl.value = 1;
    
    renderBillPanel();
  }

  // Hàm Update số lượng từ Bill -> Cập nhật ngược lại Input
  window.updateQtyFromBill = function(id, newQty) {
      if(newQty < 1) newQty = 1;
      const inputHidden = document.querySelector(`.soluong-mon[data-id="${id}"]`);
      if(inputHidden) {
          inputHidden.value = newQty;
          renderBillPanel(); 
      }
  };

  // Hàm Xóa món từ Bill -> Bỏ check Input
  window.removeItem = function(id) {
      const cb = document.querySelector(`.chon-mon[data-id="${id}"]`);
      const card = document.querySelector(`.menu-card[data-id="${id}"]`);
      const sl = document.querySelector(`.soluong-mon[data-id='${id}']`);
      if(cb) {
          cb.checked = false;
          if(card) card.classList.remove('selected');
          if(sl) sl.disabled = true;
          renderBillPanel();
      }
  };

  // Khởi tạo bảng lần đầu (nếu reload trang mà trình duyệt lưu cache form)
  renderBillPanel();
});
</script>

<?php include 'inc/footer.php'; ?>