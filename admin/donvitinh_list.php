<?php
include_once '../classes/donvitinh.php';
$dvt = new donvitinh();

/* ================== AJAX: TRẢ VỀ CHI TIẾT ĐƠN VỊ TÍNH ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');

    $id_dvt = isset($_GET['id_dvt']) ? (int)$_GET['id_dvt'] : 0;
    if ($id_dvt <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai mã đơn vị.</div>';
        exit;
    }

    // Dùng hàm trong class thay vì viết SQL rời
    $rs = $dvt->get_don_vi_by_id($id_dvt);

    if ($rs && $r = $rs->fetch_assoc()) {
        echo "
        <div class='table-responsive'>
            <table class='combo-detail'>
                <tr><th>Tên đơn vị</th><td><strong>".htmlspecialchars($r['ten_dvt'])."</strong></td></tr>
                <tr><th>Nhóm quy đổi</th><td>{$r['nhom']}</td></tr>
                <tr><th>Hệ số quy đổi</th><td>{$r['he_so']}</td></tr>
                <tr><th>ID hệ thống</th><td>#{$r['id_dvt']}</td></tr>
            </table>
        </div>";
    } else {
        echo "<div class='p-3'>Không tìm thấy đơn vị tính.</div>";
    }
    exit;
}
/* ================== HẾT AJAX ================== */
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
// Lấy danh sách đơn vị (Hàm này trong class đã có sẵn WHERE xoa = 0)
$list = $dvt->show_don_vi_tinh();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  .thead-blue th { background:#0d6efd; color:#fff; text-transform:uppercase; font-size:13px; vertical-align: middle;}

  /* Nút bấm: Chỉnh nhỏ lại để nằm vừa trên 1 dòng */
  .btn-same { 
      display:inline-flex; align-items:center; justify-content:center;
      min-width:55px; height:30px; 
      padding:0 8px; font-size:.85rem; 
      border-radius:4px; color:#fff; border:none; cursor:pointer;
      font-weight:600; text-decoration: none;
      margin: 0 2px;
  }
  
  .btn-detail { background:#17a2b8; }  
  .btn-edit   { background:#2ecc71; }  
  .btn-del    { background:#e74c3c; }  

  .dt-child-box { background:#f9f9f9; padding:12px; border:1px solid #eee; border-radius:6px; text-align: left;}

  .combo-detail { width:100%; border-collapse:collapse; }
  .combo-detail th, .combo-detail td { border:1px solid #dee2e6; padding:8px 10px; }
  .combo-detail thead th { background:#f1f3f5; font-weight:600; }
  .combo-detail th { width:30%; background:#eef; }

  #donvitinh_filter {
      display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-bottom: 10px;
  }

  .btn-add {
      min-width:120px; height:34px; padding:0 .9rem; border-radius:6px;
      background:#0d6efd; color:#fff; font-weight:700;
      display:inline-flex; align-items:center; justify-content:center; text-decoration:none;
  }
  
  /* Badge nhóm */
  .badge-custom {
      color: #fff; padding: 4px 8px; border-radius: 12px;
      font-size: 11px; font-weight: bold; display: inline-block;
      min-width: 70px; text-transform: uppercase;
  }
  
  table.dataTable tbody td { vertical-align: middle; }
</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Danh sách đơn vị tính</h2>
    <div class="block" id="table-container">
        <table class="table table-bordered text-center display" id="donvitinh">
            <thead class="thead-blue">
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Tên đơn vị</th>
                    <th width="15%">Nhóm</th>
                    <th width="30%">Hệ số chuyển đổi</th>
                    <th width="15%">Chi tiết</th>
                    <th width="20%">Tùy chỉnh</th>
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
                        
                        // --- 1. XỬ LÝ BADGE NHÓM ---
                        $nhom_raw = $r['nhom'];
                        $nhom_hien_thi = $nhom_raw;
                        
                        if ($nhom_raw == 'khoi_luong') {
                            $nhom_hien_thi = '<span class="badge-custom" style="background:#17a2b8">Khối lượng</span>';
                        } elseif ($nhom_raw == 'the_tich') {
                            $nhom_hien_thi = '<span class="badge-custom" style="background:#6f42c1">Thể tích</span>';
                        } elseif ($nhom_raw == 'so_luong') {
                            $nhom_hien_thi = '<span class="badge-custom" style="background:#28a745">Số lượng</span>';
                        }

                        // --- 2. XỬ LÝ LOGIC QUY ĐỔI ---
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
                                <td style='font-weight:bold; color:#0d6efd'>$ten_dvt</td>
                                <td>$nhom_hien_thi</td>
                                <td>$hien_thi_quy_doi</td>
                                <td>
                                    <button class='btn-same btn-detail' data-id='$id'>Chi tiết</button>
                                </td>
                                <td>
                                    <a class='btn-same btn-edit' href='donvitinh_edit.php?id=$id'>Sửa</a>
                                    <a class='btn-same btn-del' href='donvitinh_delete.php?id=$id'
                                       onclick='return confirm(\"Xóa đơn vị tính này? Cảnh báo: Các công thức sử dụng đơn vị này có thể bị lỗi!\")'>Xóa</a>
                                </td>
                            </tr>
                        ";
                    }
                } else {
                    // Không cần else "Chưa có đơn vị" ở đây vì DataTable sẽ tự hiện "No data"
                }
            ?>
            </tbody>
        </table>
    </div>
  </div>
</div>

<script>
$(function(){
  const table = $('#donvitinh').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
          search: "", searchPlaceholder: "Tìm đơn vị...",
          paginate: { previous: "Trang trước", next: "Trang sau" },
          emptyTable: "Không có dữ liệu",
          info: "Hiển thị _START_–_END_ trong _TOTAL_ đơn vị"
      },
      order: [[2, 'asc']]
  });

  // --- THÊM NÚT (Thêm Mới + Thùng Rác) ---
  const container = $('#donvitinh_filter');
  
  const addBtn = $('<a class="btn-add" href="donvitinh_add.php">+ Thêm ĐVT</a>');
  const trashBtn = $('<a href="donvitinh_hidden.php" style="display:inline-flex; align-items:center; justify-content:center; height:34px; padding:0 15px; border-radius:6px; background:#6c757d; color:#fff; font-weight:600; text-decoration:none; margin-left:8px;"> <span style="margin-right:5px">🗑</span> Đã xóa</a>');

  container.append(addBtn).append(trashBtn);

  // AJAX Chi tiết
  $('#donvitinh').on('click', '.btn-detail', function(){
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
      
      fetch('donvitinh_list.php?ajax=ct&id_dvt=' + id)
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