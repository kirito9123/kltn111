<?php
/* ================== AJAX: TRẢ VỀ CHI TIẾT CÔNG THỨC 1 MÓN ================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    header('Content-Type: text/html; charset=utf-8');

    $id_mon = isset($_GET['id_mon']) ? (int)$_GET['id_mon'] : 0;
    if ($id_mon <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai mã món ăn.</div>';
        exit;
    }

    include_once __DIR__ . '/../lib/database.php';
    $db = new Database();

    // === BƯỚC 1: LẤY THÊM CỘT he_so TỪ BẢNG don_vi_tinh ===
    $sql = "
        SELECT cm.so_luong,
               nl.ten_nl,
               nl.so_luong_ton,
               nl.gia_nhap_tb,
               dvt.ten_dvt,
               dvt.he_so           -- <--- LẤY THÊM CÁI NÀY ĐỂ TÍNH TOÁN
        FROM congthuc_mon AS cm
        INNER JOIN nguyen_lieu AS nl ON cm.id_nl = nl.id_nl
        LEFT JOIN don_vi_tinh  AS dvt ON cm.id_dvt = dvt.id_dvt
        WHERE cm.id_mon = $id_mon
        ORDER BY nl.ten_nl ASC
    ";
    
    $rs = $db->select($sql);

    if ($rs && $rs->num_rows > 0) {
        echo "
        <div class='table-responsive'>
            <table class='combo-detail'>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên nguyên liệu</th>
                        <th>Định lượng</th>
                        <th>Đơn vị</th>
                        <th>Tồn kho</th>
                        <th>Giá nhập TB</th>
                    </tr>
                </thead>
                <tbody>
        ";

        $i = 0;
        while ($r = $rs->fetch_assoc()) {
            $i++;

            $ten_dvt = isset($r['ten_dvt']) ? $r['ten_dvt'] : '';
            
            // === BƯỚC 2: TÍNH TOÁN LẠI ĐỊNH LƯỢNG ===
            $so_luong_goc = floatval($r['so_luong']); 
            $he_so        = isset($r['he_so']) && $r['he_so'] > 0 ? floatval($r['he_so']) : 1;
            
            // Lấy số lượng gốc chia cho hệ số (Ví dụ: 50g / 1000 = 0.05kg)
            $so_luong_hien_thi = $so_luong_goc / $he_so;
            
            // Làm gọn số (xóa số 0 vô nghĩa, ví dụ 0.050 -> 0.05)
            $so_luong_final = rtrim(rtrim(number_format($so_luong_hien_thi, 4, '.', ''), '0'), '.');

            // Format các số khác
            $so_luong_ton = floatval($r['so_luong_ton']);
            $gia_nhap     = number_format($r['gia_nhap_tb'], 0, ',', '.');

            echo "
                <tr>
                    <td>{$i}</td>
                    <td>".htmlspecialchars($r['ten_nl'])."</td>
                    <td style='font-weight:bold'>{$so_luong_final}</td> <td style='color:#007bff'>{$ten_dvt}</td>
                    <td>{$so_luong_ton}</td>
                    <td>{$gia_nhap} VNĐ</td>
                </tr>
            ";
        }

        echo "
                </tbody>
            </table>
        </div>";
    } else {
        echo "<div class='p-3'>Món này chưa có công thức hoặc dữ liệu bị lỗi.</div>";
    }
    exit;
}

/* ================== HẾT AJAX ================== */
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<?php
// Lấy danh sách món ăn + giá + loại + số nguyên liệu trong công thức
include_once __DIR__ . '/../lib/database.php';
$db = new Database();

$sql = "
    SELECT  m.id_mon,
            m.name_mon,
            m.gia_mon,
            l.name_loai,
            COUNT(cm.id_nl) AS so_nguyen_lieu
    FROM monan AS m
    LEFT JOIN congthuc_mon AS cm ON m.id_mon = cm.id_mon
    LEFT JOIN loai_mon      AS l  ON m.id_loai = l.id_loai
    WHERE m.xoa = 0
    GROUP BY m.id_mon, m.name_mon, m.gia_mon, l.name_loai
    ORDER BY m.id_mon ASC
";
$list = $db->select($sql);
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

  .combo-detail th { background:#eef; }

  #congthuc_filter {
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
    <h2>Danh sách công thức món ăn</h2>
    <div class="block" id="table-container">
        <table class="table table-bordered text-center display" id="congthuc">
            <thead class="thead-blue">
                <tr>
                    <th>#</th>
                    <th>Tên món</th>
                    <th>Giá</th>
                    <th>Loại</th>
                    <th>Số nguyên liệu</th>
                    <th>Chi tiết</th>
                    <th><center>Tùy chỉnh</center></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($list) {
                    $i = 0;
                    while ($r = $list->fetch_assoc()) {
                        $i++;
                        $id_mon = (int)$r['id_mon'];
                        $so_nl  = (int)$r['so_nguyen_lieu'];

                        $gia_mon  = number_format($r['gia_mon'], 0, ',', '.') . ' VNĐ';
                        $ten_loai = $r['name_loai'] ?: 'Không rõ';

                        echo "
                            <tr data-id='$id_mon'>
                                <td>$i</td>
                                <td>".htmlspecialchars($r['name_mon'])."</td>
                                <td>$gia_mon</td>
                                <td>".htmlspecialchars($ten_loai)."</td>
                                <td><center>$so_nl</center></td>
                                <td>
                                    <button class='btn-same btn-detail' data-id='$id_mon'>Chi tiết</button>
                                </td>
                                <td>
                                    <a class='btn-same btn-edit' href='congthuc_add.php?id_mon=$id_mon'>Sửa</a>
                                    <a class='btn-same btn-del'
                                        href='congthuc_delete.php?id_mon=$id_mon'
                                        onclick='return confirm(\"Xóa toàn bộ công thức của món này?\")'>
                                        Xóa
                                    </a>
                                </td>
                            </tr>
                        ";
                    }
                } else {
                    echo "<tr><td colspan='7'>Chưa có món ăn nào.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
  </div>
</div>

<script>
$(function(){
  const table = $('#congthuc').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
          search: "",
          searchPlaceholder: "Tìm theo tên món...",
          paginate: { previous: "Trang trước", next: "Trang sau" },
          emptyTable: "Không có dữ liệu",
          info: "Hiển thị _START_–_END_ trong _TOTAL_ món"
      },
      order: [[0, 'asc']]
  });

  // Thêm nút "Thêm CT"
  const addBtn = $('<a class="btn-add" href="congthuc_add.php">+ Thêm CT</a>');
  $('#congthuc_filter').append(addBtn);

  // Child row chi tiết công thức
  $('#congthuc').on('click', '.btn-detail', function(){
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
      fetch('congthuc_list.php?ajax=ct&id_mon=' + id)
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
