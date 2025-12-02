<?php
/* ========= 1) AJAX: chỉ trả về GRID MÓN cho modal (không include header/sidebar) ========= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'mon_list') {
    header('Content-Type: text/html; charset=utf-8');

    include_once __DIR__ . '/../lib/database.php';
    $db = new Database();

    $id_loai = isset($_GET['id_loai']) ? (int)$_GET['id_loai'] : 0;
    $key     = isset($_GET['key']) ? trim($_GET['key']) : '';
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $limit   = 12; // 3 món x 4 hàng
    $offset  = ($page - 1) * $limit;

    $where = "WHERE xoa = 0";
    if ($id_loai > 0) $where .= " AND id_loai = {$id_loai}";
    if ($key !== '') {
        $k = $db->link->real_escape_string($key);
        $where .= " AND name_mon LIKE '%{$k}%'";
    }

    // total
    $total = 0;
    $rsTotal = $db->select("SELECT COUNT(*) AS cnt FROM monan {$where}");
    if ($rsTotal && ($row = $rsTotal->fetch_assoc())) $total = (int)$row['cnt'];
    $total_pages = max(1, (int)ceil($total / $limit));

    // data
    $sql = "
        SELECT id_mon, name_mon, gia_mon, images
        FROM monan
        {$where}
        ORDER BY id_mon DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $rs = $db->select($sql);

    // toolbar tìm kiếm
    echo '<div class="picker-toolbar">
            <input type="text" id="picker-key" placeholder="Tìm món...">
            <button type="button" class="btn btn-sm btn-primary" onclick="Picker.reload(1)">Tìm</button>
          </div>';

    // GRID linh hoạt
    echo '<div class="menu-grid">';
    if ($rs && $rs->num_rows > 0) {
        while ($m = $rs->fetch_assoc()) {
            $id  = (int)$m['id_mon'];
            $ten = $m['name_mon'];
            $gia = (float)$m['gia_mon'];
            $img = !empty($m['images']) ? '../images/food/'.$m['images'] : '../images/placeholder.png';
            echo '<div class="menu-card" data-id="'.$id.'">
                    <img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($ten).'">
                    <h6>'.htmlspecialchars($ten).'</h6>
                    <div class="price">'.number_format($gia,0,',','.').' đ</div>
                    <div class="pick-actions">
                        <input type="checkbox" class="pick-cb" data-id="'.$id.'">
                        <input type="number" class="pick-qty" data-id="'.$id.'" min="1" value="1" disabled>
                    </div>
                  </div>';
        }
    } else {
        echo '<div class="empty">Không có món phù hợp.</div>';
    }
    echo '</div>';

    // paginate
    echo '<div class="picker-paginate">';
    if ($page > 1) echo '<button type="button" class="btn btn-sm btn-light" onclick="Picker.reload('.($page-1).')">«</button>';
    echo '<span class="pg-info">Trang '.$page.'/'.$total_pages.'</span>';
    if ($page < $total_pages) echo '<button type="button" class="btn btn-sm btn-light" onclick="Picker.reload('.($page+1).')">»</button>';
    echo '</div>';
    exit;
}
/* ============================ HẾT KHỐI AJAX ============================ */
?>

<?php
include 'inc/header.php';
include 'inc/sidebar.php';

// KHÔNG cần include session.php nữa — header hoặc sidebar đã có rồi
if (!Session::get('adminlogin')) { 
    header("Location: login.php"); 
    exit(); 
}

$level = (int) Session::get('adminlevel'); // ép kiểu để tránh lỗi so sánh kiểu string/int

if ($level !== 0 && $level !== 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập! Chỉ quản trị viên hoặc nhân viên bếp mới được phép.');
        window.location.href = 'index.php';
    </script>";
    exit();
}

include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';
include_once __DIR__ . '/../classes/mon.php';

$db  = new Database();
$fm  = new Format();
$mon = new mon();

/* ====== Lấy danh sách loại món cho sidebar modal ====== */
$loai_map = [];
$loai_rs = $db->select("SELECT id_loai, name_loai FROM loai_mon WHERE xoa=0 ORDER BY name_loai ASC");
if ($loai_rs) while ($r = $loai_rs->fetch_assoc()) $loai_map[(int)$r['id_loai']] = $r['name_loai'];

/* ====== XỬ LÝ THÊM MỚI ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $ten_menu   = $db->link->real_escape_string(trim($_POST['ten_menu'] ?? ''));
    $ghi_chu    = $db->link->real_escape_string(trim($_POST['ghi_chu'] ?? ''));
    $trang_thai = (int)($_POST['trang_thai'] ?? 1);

    if ($ten_menu === '') {
        $msg = "<div class='error'>Vui lòng nhập Tên combo.</div>";
    } else {
        // Insert header
        $sqlMenu = "INSERT INTO menu (ten_menu, ghi_chu, trang_thai) VALUES ('{$ten_menu}', '{$ghi_chu}', {$trang_thai})";
        $ok = $db->insert($sqlMenu);
        if ($ok) {
            $id_menu_new = (int)$db->link->insert_id;

            // Lấy chi tiết combo
            $id_mon   = $_POST['id_mon']   ?? [];
            $so_luong = $_POST['so_luong'] ?? [];

            $n = max(count($id_mon), count($so_luong));
            for ($i=0; $i<$n; $i++) {
                $mid = (int)($id_mon[$i] ?? 0);
                $qty = (int)($so_luong[$i] ?? 0);
                if ($mid > 0 && $qty > 0) {
                    $db->insert("INSERT INTO menu_chitiet (ma_menu, id_mon, so_luong) VALUES ({$id_menu_new}, {$mid}, {$qty})");
                }
            }

            echo "<script>alert('✅ Thêm combo mới thành công!'); window.location='combolist.php';</script>";
            exit;
        } else {
            $msg = "<div class='error'>Không thể tạo combo. Vui lòng thử lại.</div>";
        }
    }
}
?>

<style>
/* ===== FORM ===== */
.form-wrapper{max-width:1000px;margin:40px auto;padding:30px 40px;background:#fff;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,.1);font-family:'Segoe UI',sans-serif;}
.form-wrapper h2{text-align:center;margin-bottom:16px;font-size:26px;color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:12px;}
.page-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;}
.page-toolbar-left{display:flex;gap:10px;align-items:center;}
.btn-toolbar{display:inline-flex;align-items:center;justify-content:center;min-width:110px;height:34px;padding:0 .9rem;font-size:.9rem;border-radius:6px;color:#fff;border:none;cursor:pointer;font-weight:700;text-decoration:none}
.btn-back{background:#6c757d;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333;}
.form-group input[type="text"],.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid #bdc3c7;border-radius:8px;font-size:16px;background:#f8f9fa}
.form-actions{text-align:center;margin-top:20px;}
.form-actions input[type="submit"]{padding:12px 30px;background:#0d6efd;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}

/* ===== BẢNG CHI TIẾT ===== */
.table-ct{width:100%;border-collapse:collapse;margin-top:10px}
.table-ct th,.table-ct td{border:1px solid #e1e5ea;padding:10px}
.table-ct thead th{background:#f4f6f8;text-transform:uppercase;font-size:13px}
.table-ct .col-stt{width:60px;text-align:center}
.table-ct .col-qty{width:140px;text-align:center}
.table-ct .col-del{width:120px;text-align:center}
.btn-add-picker{margin-top:10px;background:#17a2b8;color:#fff;border:none;border-radius:6px;padding:9px 12px;cursor:pointer}

/* ===== MODAL: chống tràn + grid linh hoạt ===== */
.modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; z-index:999; }
.modal-box{
  position:fixed; left:50%; top:50%; transform:translate(-50%,-50%);
  width:1000px; max-width:95vw; height:90vh; max-height:90vh;
  background:#fff; border-radius:12px; display:none; z-index:1000;
  box-shadow:0 12px 30px rgba(0,0,0,.25); display:flex; flex-direction:column;
}
.modal-head, .modal-foot{ flex:0 0 auto; padding:12px 16px; border-bottom:1px solid #eee }
.modal-foot{ border-bottom:0; border-top:1px solid #eee }
.modal-body{ flex:1 1 auto; overflow:hidden; display:flex; gap:14px; padding:12px 16px; }
.modal-left{ width:240px; min-width:240px; border-right:1px solid #f1f1f1; overflow:auto }
.modal-right{ flex:1 1 auto; min-width:0; overflow:auto; display:flex; flex-direction:column; }

/* sidebar loại */
.cat-list a{display:block;padding:8px 10px;border-radius:8px;margin-bottom:6px;color:#333;text-decoration:none;border:1px solid #eee}
.cat-list a.active{background:#ffb900;color:#fff;border-color:#ffb900;font-weight:600}

/* grid linh hoạt + scroll nội bộ */
.picker-toolbar{ position:sticky; top:0; z-index:2; background:#fff; padding-bottom:8px; margin-bottom:8px; display:flex; gap:8px }
.picker-toolbar input{flex:1;padding:8px 10px;border:1px solid #ccc;border-radius:8px}

/* >>> thay vì cố định 3 cột, dùng auto-fill để không tràn */
.menu-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
  gap:16px; align-content:start;
  max-height:calc(90vh - 220px); /* chừa chỗ header/footer modal */
  overflow:auto;
}
@media (max-width:520px){ .menu-grid{ grid-template-columns:1fr } }

.menu-card{border:1px solid #eee;border-radius:12px;padding:12px;text-align:center;transition:.2s;background:#fff;cursor:pointer}
.menu-card:hover{transform:scale(1.02);box-shadow:0 3px 8px rgba(0,0,0,.12)}
.menu-card img{width:100%;height:160px;object-fit:cover;border-radius:10px;display:block}
.menu-card h6{margin:10px 0 6px;font-size:15px;word-break:break-word}
.menu-card .price{color:#d19c65;font-weight:600;margin-bottom:6px;white-space:nowrap}
.pick-actions{display:flex;justify-content:center;align-items:center;gap:10px}
.pick-qty{width:68px;text-align:center}

/* buttons */
.btn{border-radius:6px;padding:8px 12px;border:1px solid #ddd;background:#f8f9fa;cursor:pointer}
.btn-primary{background:#0d6efd;color:#fff;border-color:#0d6efd}
.btn-danger{background:#e74c3c;color:#fff;border-color:#e74c3c}
.btn-success{background:#2ecc71;color:#fff;border-color:#2ecc71}
.btn-light{background:#fff}

.error, .success { display:block; padding:12px; margin-bottom:20px; border-radius:6px; font-weight:600; text-align:center; }
.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
</style>

<div class="grid_10">
    <div class="page-toolbar">
        <div class="page-toolbar-left">
          <a class="btn-toolbar btn-back" href="combolist.php">← Quay lại</a>
        </div>
        <div></div>
      </div>
  <div class="box round first grid">
    <div class="form-wrapper">

      <!-- Toolbar trên form -->
      

      <h2>Thêm Combo Mới</h2>

      <?php if (!empty($msg)) echo $msg; ?>

      <form method="post" action="">
        <div class="form-group">
          <label>Tên combo</label>
          <input type="text" name="ten_menu" placeholder="Nhập tên combo..." required>
        </div>
        <div class="form-group">
          <label>Ghi chú</label>
          <textarea name="ghi_chu" rows="3" placeholder="Ghi chú cho combo (tuỳ chọn)"></textarea>
        </div>
        <div class="form-group">
          <label>Trạng thái</label>
          <select name="trang_thai">
            <option value="1" selected>Hoạt động</option>
            <option value="0">Ngừng</option>
          </select>
        </div>

        <h3 style="margin-top:24px;">Chi tiết combo</h3>
        <table class="table-ct" id="ct-table">
          <thead>
            <tr>
              <th class="col-stt">#</th>
              <th>Món</th>
              <th class="col-qty">Số lượng</th>
              <th class="col-del">Xoá</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-center">1</td>
              <td colspan="3">Chưa có món trong combo</td>
            </tr>
          </tbody>
        </table>

        <button type="button" class="btn-add-picker" id="openPicker">+ Thêm món vào combo</button>

        <div class="form-actions">
          <input type="submit" name="submit" value="Lưu combo">
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL PICKER -->
<div class="modal-backdrop" id="pickerBackdrop"></div>
<div class="modal-box" id="pickerModal">
  <div class="modal-head" style="display:flex;justify-content:space-between;align-items:center;">
    <strong>Chọn món thêm vào combo</strong>
    <button type="button" class="btn btn-danger" onclick="Picker.close()">Đóng</button>
  </div>
  <div class="modal-body">
    <aside class="modal-left">
      <div class="cat-list" id="pickerCatList">
        <a href="javascript:void(0)" data-id="0" class="active" onclick="Picker.setCat(0)">Tất cả</a>
        <?php foreach ($loai_map as $lid=>$lname): ?>
          <a href="javascript:void(0)" data-id="<?= (int)$lid ?>" onclick="Picker.setCat(<?= (int)$lid ?>)"><?= htmlspecialchars($lname) ?></a>
        <?php endforeach; ?>
      </div>
    </aside>
    <section class="modal-right">
      <div id="pickerContent">Đang tải...</div>
      <div class="picker-right-actions" style="margin-top:8px;">
        <button type="button" class="btn btn-success" onclick="Picker.commit()">+ Thêm vào combo</button>
      </div>
    </section>
  </div>
  <div class="modal-foot" style="display:flex;gap:8px;justify-content:flex-end;">
    <button type="button" class="btn btn-primary" onclick="Picker.commit()">Lưu chọn</button>
    <button type="button" class="btn" onclick="Picker.close()">Hủy</button>
  </div>
</div>

<script>
/* ===== PICKER ===== */
const Picker = {
  state: { page:1, id_loai:0, key:'' },
  open(){
    document.getElementById('pickerBackdrop').style.display='block';
    document.getElementById('pickerModal').style.display='block';
    document.documentElement.style.overflow = 'hidden';
    this.reload(1);
  },
  close(){
    document.getElementById('pickerBackdrop').style.display='none';
    document.getElementById('pickerModal').style.display='none';
    document.documentElement.style.overflow = '';
  },
  setCat(id){
    document.querySelectorAll('#pickerCatList a').forEach(a=>a.classList.remove('active'));
    const cur = document.querySelector(`#pickerCatList a[data-id='${id}']`);
    if (cur) cur.classList.add('active');
    this.state.id_loai = parseInt(id,10)||0;
    this.reload(1);
  },
  reload(page){
    if (page) this.state.page = page;
    const keyEl = document.getElementById('picker-key');
    const key = keyEl ? keyEl.value.trim() : (this.state.key||'');
    this.state.key = key;

    const qs = new URLSearchParams({
      ajax:'mon_list',
      id_loai:this.state.id_loai,
      key:this.state.key,
      page:this.state.page
    });
    const box = document.getElementById('pickerContent');
    box.innerHTML = 'Đang tải...';
    fetch('comboadd.php?'+qs.toString())
      .then(r=>r.text())
      .then(html=>{
        box.innerHTML = html;

        // Click card => toggle chọn
        box.querySelectorAll('.menu-card').forEach(card=>{
          card.addEventListener('click', e=>{
            if (e.target && (e.target.classList.contains('pick-qty') || e.target.classList.contains('pick-cb'))) return;
            const cb  = card.querySelector('input.pick-cb');
            const qty = card.querySelector('input.pick-qty');
            if (cb){ cb.checked = !cb.checked; }
            if (qty){ qty.disabled = !(cb && cb.checked); }
            card.classList.toggle('selected', cb && cb.checked);
          });
        });

        // Checkbox thay đổi
        box.querySelectorAll('.pick-cb').forEach(cb=>{
          const qty = box.querySelector(`.pick-qty[data-id='${cb.dataset.id}']`);
          if (qty) qty.disabled = !cb.checked;
          cb.addEventListener('change', ()=>{ if (qty) qty.disabled = !cb.checked; });
        });
      })
      .catch(()=>{ box.innerHTML = '<div class="empty">Lỗi tải danh sách.</div>'; });
  },
  commit(){
    const box = document.getElementById('pickerContent');
    const rows = [];
    box.querySelectorAll('.menu-card').forEach(card=>{
      const cb = card.querySelector('.pick-cb');
      const qty = card.querySelector('.pick-qty');
      if (cb && cb.checked) {
        const id = parseInt(cb.dataset.id,10);
        const name = (card.querySelector('h6')||{}).innerText || '';
        const sl = Math.max(1, parseInt(qty.value||'1',10));
        rows.push({id_mon:id, name:name, sl:sl});
      }
    });
    if (!rows.length) { this.close(); return; }

    const tbody = document.querySelector('#ct-table tbody');
    // xoá dòng thông báo nếu có
    if (tbody.children.length===1 && tbody.querySelector('td[colspan]')) tbody.innerHTML = '';

    rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="text-center"></td>
        <td>
          <input type="hidden" name="ct_id[]" value="0">
          <input type="hidden" name="id_mon[]" value="${r.id_mon}">
          ${escapeHtml(r.name)}
        </td>
        <td><input type="number" name="so_luong[]" min="0" value="${r.sl}" style="width:100%;text-align:center"></td>
        <td><button type="button" class="btn btn-light" onclick="removeRow(this)">Xoá</button></td>
      `;
      tbody.appendChild(tr);
    });

    // cập nhật STT
    renumber();
    this.close();
  }
};

(function(){
  const btn = document.getElementById('openPicker');
  if (btn) btn.addEventListener('click', ()=>Picker.open());
})(); // KHÔNG auto-open khi load trang

function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, t=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[t])); }
function renumber(){
  const tbody = document.querySelector('#ct-table tbody');
  [...tbody.querySelectorAll('tr')].forEach((tr, i)=>{
    const first = tr.querySelector('td');
    if (first) first.textContent = i+1;
  });
}
function removeRow(btn){
  const tr = btn.closest('tr');
  if (tr) tr.remove();
  renumber();
}
</script>

<?php include 'inc/footer.php'; ?>
