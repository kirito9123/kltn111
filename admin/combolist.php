<?php
/* ================== AJAX: TRẢ VỀ CHI TIẾT COMBO (MENU) ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');

    $ma_menu = isset($_GET['ma_menu']) ? (int)$_GET['ma_menu'] : 0;
    if ($ma_menu <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai mã combo.</div>';
        exit;
    }

    if (!isset($db) || !isset($db->link)) {
        include_once __DIR__ . '/../lib/database.php';
        $db = new Database();
    }

    $sql = "
        SELECT 
            mct.id,
            mct.ma_menu,
            mct.id_mon,
            mct.so_luong,
            mo.name_mon,
            mo.gia_mon,
            (mct.so_luong * mo.gia_mon) AS thanh_tien
        FROM menu_chitiet AS mct
        INNER JOIN monan AS mo ON mo.id_mon = mct.id_mon
        WHERE mct.ma_menu = {$ma_menu}
        ORDER BY mct.id ASC
    ";
    $rs = $db->select($sql);

    if ($rs && $rs->num_rows > 0) {
        $i = 0; $tong = 0;

        // ⚠️ Trả về bảng có class riêng để mình style từ ngoài
        echo '<div class="table-responsive">
                <table class="combo-detail">
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
            $gia = (float)$r['gia_mon'];
            $tt  = (float)$r['thanh_tien'];
            $tong += $tt;

            echo '<tr>
                    <td class="text-center">'.$i.'</td>
                    <td>'.htmlspecialchars($r['name_mon']).'</td>
                    <td class="text-right">'.(int)$r['so_luong'].'</td>
                    <td class="text-right">'.number_format($gia,0,',','.').' VNĐ</td>
                    <td class="text-right">'.number_format($tt ,0,',','.').' VNĐ</td>
                  </tr>';
        }
        echo   '</tbody>
              </table>
              <div class="combo-total">Tổng: '.number_format($tong,0,',','.').' VNĐ</div>
            </div>';
    } else {
        echo '<div class="p-3">Combo này chưa có món nào.</div>';
    }
    exit;
}
/* ================== HẾT PHẦN AJAX ================== */
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../lib/database.php';
include_once __DIR__ . '/../helpers/format.php';

$db = new Database();
$fm = new Format();

$sqlMenu = "
    SELECT id_menu, ten_menu, ghi_chu, trang_thai 
    FROM menu
    ORDER BY id_menu ASC
";
$list_combo = $db->select($sqlMenu);

function view_trang_thai_menu($st){
    if ($st == 0) return '<span class="badge badge-success">Hoạt động</span>';
    if ($st == 1) return '<span class="badge badge-secondary">Ngừng</span>';
    return '<span class="badge badge-light">Không rõ</span>';
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  .thead-blue th { background:#0d6efd; color:#fff; text-transform:uppercase; font-size:14px; }
  #combo_filter { display:flex; align-items:center; justify-content:flex-end; }
  #filter-trangthai{
      width:160px; margin-left:12px; border-radius:6px; padding:8px;
      border:1px solid #ccc; background:#f9f9f9; font-size:14px;
  }

  /* ==== Buttons: cùng kích thước ==== */
  .btn-same { 
      display:inline-flex; align-items:center; justify-content:center;
      min-width:85px; height:34px; padding:0 .75rem;
      font-size:.9rem; border-radius:6px; color:#fff; border:none; cursor:pointer;
      font-weight:600;
  }
  .btn-detail { background:#17a2b8; }         /* xanh info */
  .btn-edit   { background:#2ecc71; }          /* xanh lá */
  .btn-del    { background:#e74c3c; }          /* đỏ */
  .btn-same + .btn-same { margin-left:6px; }

  /* ==== Child box ==== */
  .dt-child-box { background:#f9f9f9; padding:12px; border:1px solid #eee; border-radius:6px; }

  /* ==== Bảng chính ==== */
  table#combo td:nth-child(3) { width: 35%; }     /* Ghi chú rộng hơn */
  table#combo td:nth-child(6), table#combo th:nth-child(6) { width: 220px; } /* Tùy chỉnh gọn lại */
  table#combo td, table#combo th { vertical-align: middle; }

  /* ==== Bảng chi tiết combo ==== */
  .combo-detail { width:100%; border-collapse:collapse; }
  .combo-detail th, .combo-detail td { border:1px solid #dee2e6; padding:8px 10px; }
  .combo-detail thead th { background:#f1f3f5; font-weight:600; }
  .combo-detail .col-stt { width:52px; text-align:center; }
  .combo-detail .col-qty { width:100px; }
  .combo-detail .col-money { width:140px; }
  .combo-detail .text-right { text-align:right; }
  .combo-detail .text-center { text-align:center; }
  .combo-total { margin-top:8px; font-weight:700; text-align:right; padding-right:10px; }

  /* Cho hàng tìm kiếm + lọc + nút thêm canh phải và đẹp hơn */
    #combo_filter { 
    display: flex; 
    align-items: center; 
    justify-content: flex-end; 
    gap: 10px;             /* khoảng cách giữa các phần tử */
    }

    #combo_filter label {    /* DataTables đặt ô search trong label */
    margin: 0; 
    }

    .btn-add {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:125px; height:34px; padding:0 .9rem;
    font-size:.9rem; border-radius:6px; color:#fff; border:none; cursor:pointer;
    font-weight:700; background:#0d6efd; text-decoration:none;
    }

    .btn-add:hover { filter: brightness(0.95); }

</style>


<div class="grid_10">
  <div class="box round first grid">
    <h2>Danh sách Combo</h2>
    <div class="block" id="table-container">
      <table class="table table-bordered text-center display" id="combo">
        <thead class="thead-blue">
          <tr>
            <th>#</th>
            <th>Tên Combo</th>
            <th>Ghi chú</th>
            <th>Trạng thái</th>
            <th>Chi tiết</th>
            <th><center>Tùy chỉnh</center></th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($list_combo) {
              $i = 0;
              while ($c = $list_combo->fetch_assoc()) {
                  $i++;
                  $id = (int)$c['id_menu'];
                  ?>
                  <tr data-id="<?= $id ?>">
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($c['ten_menu']) ?></td>
                    <td><?= htmlspecialchars($c['ghi_chu']) ?></td>
                    <td><?= view_trang_thai_menu($c['trang_thai']) ?></td>
                    <td>
                      <button type="button" class="btn-same btn-detail" data-id="<?= $id ?>">Chi tiết</button>
                    </td>
                    <td>
                      <a class="btn-same btn-edit" href="comboedit.php?id=<?= $id ?>">Chỉnh sửa</a>
                      <a class="btn-same btn-del"  href="combodelete.php?id=<?= $id ?>"
                         onclick="return confirm('Bạn có chắc muốn xóa combo này?');">Xóa</a>
                    </td>
                  </tr>
                  <?php
              }
          } else {
              echo "<tr><td colspan='6'>Chưa có combo nào.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(function(){
  const table = $('#combo').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
          search: "",
          searchPlaceholder: "Tìm theo tên combo...",
          paginate: { previous: "Trang trước", next: "Trang sau" },
          info: "Hiển thị _START_–_END_ trong _TOTAL_ combo",
          emptyTable: "Không có dữ liệu",
          infoEmpty: "Không có dữ liệu để hiển thị"
      },
      order: [[0, 'asc']]
  });

  // Bộ lọc trạng thái (cột index 3)
  const stSel = $('<select id="filter-trangthai"><option value="">Tất cả trạng thái</option><option value="Hoạt động">Hoạt động</option><option value="Ngừng">Ngừng</option></select>');
  $('#combo_filter').append(stSel);
  $('#filter-trangthai').on('change', function(){
      const val = $(this).val();
      table.column(3).search(val ? val : '', true, false).draw();
  });

  const addBtn = $('<a class="btn-add" href="comboadd.php">+ Thêm combo</a>');
    $('#combo_filter').append(addBtn);

  // Child row: xem chi tiết combo
  $('#combo').on('click', '.btn-detail', function(){
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
      fetch('combolist.php?ajax=ct&ma_menu=' + encodeURIComponent(id))
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
        .finally(() => {
            btn.prop('disabled', false);
        });
  });

  table.on('page.dt', function () {
      $('html, body').animate({ scrollTop: $('#table-container').offset().top }, 'slow');
  });
});
</script>

<?php include 'inc/footer.php'; ?>
