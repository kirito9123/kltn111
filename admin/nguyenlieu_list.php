<?php
/* ================== AJAX: TRẢ VỀ CHI TIẾT NGUYÊN LIỆU ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');

    $id_nl = isset($_GET['id_nl']) ? (int)$_GET['id_nl'] : 0;
    if ($id_nl <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai mã nguyên liệu.</div>';
        exit;
    }

    include_once __DIR__ . '/../lib/database.php';
    $db = new Database();

    $sql = "SELECT * FROM nguyen_lieu WHERE id_nl = $id_nl LIMIT 1";
    $rs = $db->select($sql);

    if ($rs && $r = $rs->fetch_assoc()) {
        echo "
        <div class='table-responsive'>
            <table class='combo-detail'>
                <tr><th>Tên nguyên liệu</th><td>".htmlspecialchars($r['ten_nl'])."</td></tr>
                <tr><th>Đơn vị</th><td>{$r['don_vi']}</td></tr>
                <tr><th>Số lượng tồn</th><td>{$r['so_luong_ton']}</td></tr>
                <tr><th>Trạng thái</th><td>".($r['xoa']==0?'Đang dùng':'Đã xóa')."</td></tr>
            </table>
        </div>";
    } else {
        echo "<div class='p-3'>Không tìm thấy nguyên liệu.</div>";
    }
    exit;
}
/* ================== HẾT AJAX ================== */
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
include_once __DIR__ . '/../classes/nguyenvatlieu.php';
$nl = new nguyenvatlieu();

$list = $nl->show_nguyen_lieu(); // danh sách nguyên liệu đang dùng
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  .thead-blue th { background:#0d6efd; color:#fff; text-transform:uppercase; font-size:14px; }

  .btn-same { 
      display:inline-flex; align-items:center; justify-content:center;
      min-width:85px; height:34px; padding:0 .75rem;
      font-size:.9rem; border-radius:6px; color:#fff; border:none; cursor:pointer;
      font-weight:600;
  }
  .btn-detail { background:#17a2b8; }  /* xanh info */
  .btn-edit   { background:#2ecc71; }  /* xanh lá */
  .btn-del    { background:#e74c3c; }  /* đỏ */

  .btn-same + .btn-same { margin-left:6px; }

  .dt-child-box { background:#f9f9f9; padding:12px; border:1px solid #eee; border-radius:6px; }

  .combo-detail { width:100%; border-collapse:collapse; }
  .combo-detail th, .combo-detail td { border:1px solid #dee2e6; padding:8px 10px; }
  .combo-detail thead th { background:#f1f3f5; font-weight:600; }

  .combo-detail th { width:25%; background:#eef; }

  #nguyenlieu_filter {
      display:flex; justify-content:flex-end; align-items:center; gap:12px;
  }

  .btn-add {
      min-width:130px;
      height:34px;
      padding:0 .9rem;
      border-radius:6px;
      background:#0d6efd;
      color:#fff;
      font-weight:700;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      text-decoration:none;
  }
</style>

<div class="grid_10">
  <div class="box round first grid">
    <h2>Danh sách nguyên vật liệu</h2>
    <div class="block" id="table-container">
        <table class="table table-bordered text-center display" id="nguyenlieu">
            <thead class="thead-blue">
                <tr>
                    <th>#</th>
                    <th>Tên NL</th>
                    <th>Đơn vị</th>
                    <th>Tồn kho</th>
                    <th>Giá nhập</th>
                    <th>Ghi chú</th>
                    <th>Chi tiết</th>
                    <th>Tùy chỉnh</th>
                </tr>
            </thead>
        <tbody>
            <?php
                if ($list) {
                    $i = 0;
                    while ($r = $list->fetch_assoc()) {
                        $i++;
                        $id = (int)$r['id_nl'];

                        echo "
                            <tr data-id='$id'>
                                <td>$i</td>
                                <td>" . htmlspecialchars($r['ten_nl']) . "</td>
                                <td>{$r['don_vi']}</td>
                                <td>{$r['so_luong_ton']}</td>
                                <td>" . number_format($r['gia_nhap_tb'], 0, ',', '.') . " VNĐ</td>
                                <td>" . htmlspecialchars($r['ghichu'] ?? '') . "</td>
                                <td>
                                    <button class='btn-same btn-detail' data-id='$id'>Chi tiết</button>
                                </td>

                                <td>
                                    <a class='btn-same btn-edit' href='nguyenlieu_edit.php?id=$id'>Sửa</a>
                                    <a class='btn-same btn-del' href='nguyenlieu_delete.php?id=$id'
                                    onclick='return confirm(\"Xóa nguyên liệu này?\")'>Xóa</a>
                                </td>
                            </tr>
                        ";
                    }
                } else {
                    echo "<tr><td colspan='8'>Chưa có nguyên liệu nào.</td></tr>";
                }
            ?>
        </tbody>
    </table>
    <div style="margin-bottom: 15px;">
        <a href="nguyenlieu_hidden.php" class="btn-add"
        style="background:#6c757d; min-width:150px;">
            Xem nguyên liệu đã xóa
        </a>
    </div>

    </div>
    </div>
</div>

<script>
$(function(){
  const table = $('#nguyenlieu').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
          search: "",
          searchPlaceholder: "Tìm theo tên nguyên liệu...",
          paginate: { previous: "Trang trước", next: "Trang sau" },
          emptyTable: "Không có dữ liệu",
          info: "Hiển thị _START_–_END_ trong _TOTAL_ nguyên liệu"
      },
      order: [[0, 'asc']]
  });

  // Thêm nút "Thêm NL"
  
  const addBtn = $('<a class="btn-add" href="nguyenlieu_add.php">+ Thêm NL</a>');
  $('#nguyenlieu_filter').append(addBtn);

  // Child row chi tiết
  $('#nguyenlieu').on('click', '.btn-detail', function(){
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
      fetch('nguyenlieu_list.php?ajax=ct&id_nl=' + id)
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
