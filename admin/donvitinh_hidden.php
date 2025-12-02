<?php
include_once '../classes/donvitinh.php';
$dvt = new donvitinh();

/* ================== 1. XỬ LÝ KHÔI PHỤC ================== */
if (isset($_GET['restoreid'])) {
    $id = (int)$_GET['restoreid'];
    $restore = $dvt->restore_don_vi_tinh($id); // Hàm này phải có trong class nha
    if ($restore) {
        echo "<script>alert('Khôi phục thành công!'); window.location='donvitinh_hidden.php';</script>";
    } else {
        echo "<script>alert('Khôi phục thất bại!'); window.location='donvitinh_hidden.php';</script>";
    }
}

/* ================== 2. AJAX CHI TIẾT (Giữ nguyên để xem trước khi khôi phục) ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');
    $id_dvt = isset($_GET['id_dvt']) ? (int)$_GET['id_dvt'] : 0;
    if ($id_dvt <= 0) exit;

    $rs = $dvt->get_don_vi_by_id($id_dvt);
    if ($rs && $r = $rs->fetch_assoc()) {
        echo "
        <div class='table-responsive'>
            <table class='combo-detail'>
                <tr><th>Tên đơn vị</th><td><strong>".htmlspecialchars($r['ten_dvt'])."</strong></td></tr>
                <tr><th>Nhóm quy đổi</th><td>{$r['nhom']}</td></tr>
                <tr><th>Hệ số quy đổi</th><td>{$r['he_so']}</td></tr>
                <tr><th>Trạng thái</th><td><span style='color:red; font-weight:bold'>Đã xóa</span></td></tr>
            </table>
        </div>";
    }
    exit;
}
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
$list = $dvt->show_don_vi_tinh_deleted();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  /* Thay màu xanh bằng màu đỏ cho thùng rác */
  .thead-red th { background:#dc3545; color:#fff; text-transform:uppercase; font-size:13px; vertical-align: middle;}

  /* CSS giữ nguyên form mẫu */
  .btn-same { 
      display:inline-flex; align-items:center; justify-content:center;
      min-width:55px; height:30px; 
      padding:0 8px; font-size:.85rem; 
      border-radius:4px; color:#fff; border:none; cursor:pointer;
      font-weight:600; text-decoration: none;
      margin: 0 2px;
  }
  
  .btn-detail { background:#17a2b8; }  
  .btn-restore { background:#28a745; min-width: 90px; } /* Màu xanh lá cho nút khôi phục */
  .btn-restore:hover { background:#218838; }

  .dt-child-box { background:#f9f9f9; padding:12px; border:1px solid #eee; border-radius:6px; text-align: left;}

  .combo-detail { width:100%; border-collapse:collapse; }
  .combo-detail th, .combo-detail td { border:1px solid #dee2e6; padding:8px 10px; }
  .combo-detail thead th { background:#f1f3f5; font-weight:600; }
  .combo-detail th { width:30%; background:#eef; }

  #hidden_filter {
      display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-bottom: 10px;
  }

  /* Nút quay lại */
  .btn-back {
      min-width:120px; height:34px; padding:0 .9rem; border-radius:6px;
      background:#6c757d; color:#fff; font-weight:700;
      display:inline-flex; align-items:center; justify-content:center; text-decoration:none;
  }
  
  .badge-custom {
      color: #fff; padding: 4px 8px; border-radius: 12px;
      font-size: 11px; font-weight: bold; display: inline-block;
      min-width: 70px; text-transform: uppercase;
  }
  
  table.dataTable tbody td { vertical-align: middle; }
</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Thùng rác: Đơn vị tính đã xóa</h2>
    <div class="block" id="table-container">
        <table class="table table-bordered text-center display" id="hidden_table">
            <thead class="thead-red">
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Tên đơn vị</th>
                    <th width="15%">Nhóm</th>
                    <th width="30%">Hệ số chuyển đổi</th>
                    <th width="15%">Chi tiết</th>
                    <th width="20%">Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php
                if ($list) {
                    $i = 0;
                    while ($r = $list->fetch_assoc()) {
                        $i++;
                        $id = (int)$r['id_dvt'];
                        $ten_dvt = htmlspecialchars($r['ten_dvt']);
                        
                        // --- LOGIC HIỂN THỊ (Copy y chang trang list) ---
                        $nhom_raw = $r['nhom'];
                        $nhom_hien_thi = $nhom_raw;
                        
                        if ($nhom_raw == 'khoi_luong') $nhom_hien_thi = '<span class="badge-custom" style="background:#17a2b8">Khối lượng</span>';
                        elseif ($nhom_raw == 'the_tich') $nhom_hien_thi = '<span class="badge-custom" style="background:#6f42c1">Thể tích</span>';
                        elseif ($nhom_raw == 'so_luong') $nhom_hien_thi = '<span class="badge-custom" style="background:#28a745">Số lượng</span>';

                        $he_so = floatval($r['he_so']);
                        $hien_thi_quy_doi = "";
                        $don_vi_chuan = 'cái'; 
                        if ($nhom_raw == 'khoi_luong') $don_vi_chuan = 'g';
                        if ($nhom_raw == 'the_tich')   $don_vi_chuan = 'ml';

                        if ($he_so == 1) {
                            $hien_thi_quy_doi = "<span class='text-muted' style='font-style:italic'>— Đơn vị chuẩn —</span>";
                        } elseif ($he_so > 1) {
                            $hien_thi_quy_doi = "1 <b>$ten_dvt</b> = $he_so $don_vi_chuan";
                        } else {
                            $nghich_dao = round(1 / $he_so, 3);
                            $hien_thi_quy_doi = "1 $don_vi_chuan = $nghich_dao <b>$ten_dvt</b>";
                        }

                        echo "
                            <tr data-id='$id'>
                                <td>$i</td>
                                <td style='font-weight:bold; color:#dc3545; text-decoration:line-through'>$ten_dvt</td>
                                <td>$nhom_hien_thi</td>
                                <td>$hien_thi_quy_doi</td>
                                <td>
                                    <button class='btn-same btn-detail' data-id='$id'>Chi tiết</button>
                                </td>
                                <td>
                                    <a class='btn-same btn-restore' href='?restoreid=$id'
                                       onclick='return confirm(\"Bạn muốn khôi phục đơn vị này?\")'>
                                       ♻️ Khôi phục
                                    </a>
                                </td>
                            </tr>
                        ";
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
  const table = $('#hidden_table').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
          search: "", searchPlaceholder: "Tìm trong thùng rác...",
          paginate: { previous: "Trước", next: "Sau" },
          emptyTable: "Thùng rác trống",
          info: "Hiển thị _START_–_END_ mục"
      },
      order: [[2, 'asc']]
  });

  // --- THÊM NÚT QUAY LẠI ---
  const container = $('#hidden_table_filter');
  const backBtn = $('<a class="btn-back" href="donvitinh_list.php" style="margin-left:10px;"> Quay lại danh sách</a>');
  container.append(backBtn);

  // AJAX Chi tiết (Copy y chang)
  $('#hidden_table').on('click', '.btn-detail', function(){
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
      fetch('donvitinh_hidden.php?ajax=ct&id_dvt=' + id)
        .then(r => r.text())
        .then(html => {
            row.child('<div class="dt-child-box">' + html + '</div>').show();
            tr.addClass('shown');
            btn.text('Ẩn');
        })
        .finally(() => btn.prop('disabled', false));
  });
});
</script>

<?php include 'inc/footer.php'; ?>