<?php
// ===== AJAX: trả về chi tiết hợp đồng theo hopdong_id =====
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ct') {
    // KHÔNG include header ở đây
    header('Content-Type: text/html; charset=utf-8');

    $hopdong_id = isset($_GET['hopdong_id']) ? (int)$_GET['hopdong_id'] : 0;
    if ($hopdong_id <= 0) {
        echo '<div class="p-3 text-danger">Thiếu hoặc sai ID hợp đồng.</div>';
        exit;
    }

    // đảm bảo có $db
    if (!isset($db) || !isset($db->link)) {
        include_once __DIR__ . '/lib/database.php';
        $db = new Database();
    }

    $sql = "
        SELECT 
            c.id AS ct_id,
            c.hopdong_id,
            c.monan_id,
            m.name_mon,
            c.soluong,
            COALESCE(c.gia, m.gia_mon) AS gia,
            COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) AS thanhtien
        FROM hopdong_chitiet c
        JOIN monan m ON m.id_mon = c.monan_id
        WHERE c.hopdong_id = {$hopdong_id}
        ORDER BY c.id ASC
    ";
    $rs = $db->select($sql);

    if ($rs && $rs->num_rows > 0) {
        $i = 0; $tong = 0;
        echo '<div class="table-responsive"><table class="table table-sm table-bordered mb-2">';
        echo '<thead><tr class="text-center bg-light">
                <th>#</th><th>Món ăn</th><th>Số lượng</th><th>Giá</th><th>Thành tiền</th>
              </tr></thead><tbody>';
        while ($r = $rs->fetch_assoc()) {
            $i++;
            $gia = (float)$r['gia'];
            $tt  = (float)$r['thanhtien'];
            $tong += $tt;
            echo "<tr class='text-center'>
                    <td>{$i}</td>
                    <td>".htmlspecialchars($r['name_mon'])."</td>
                    <td>".(int)$r['soluong']."</td>
                    <td>".number_format($gia,0,',','.')." VNĐ</td>
                    <td>".number_format($tt ,0,',','.')." VNĐ</td>
                  </tr>";
        }
        echo '</tbody></table>
          <div class="d-flex justify-content-between align-items-center px-3 pb-3">
            <div class="font-weight-bold">
              Tổng: ' . number_format($tong, 0, ',', '.') . ' VNĐ
            </div>
            <a href="danhgia.php?hopdong_id=' . $hopdong_id . '" class="btn btn-sm btn-outline-primary">
              💬 Đánh giá
            </a>
          </div>
        </div>';
    } else {
        echo '<div class="p-3">Chưa có chi tiết cho hợp đồng này.</div>';
    }
    exit; // KẾT THÚC RESPONSE AJAX, không in header/footer
}

include 'inc/header.php';
Session::checkSession();

/* =========== AJAX: TRẢ VỀ CHI TIẾT HỢP ĐỒNG THEO hopdong_id =========== */

$uid = $_GET['id'];

/* ====== XỬ LÝ UPDATE THÔNG TIN ====== */
$resultMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten   = trim($_POST['ten']   ?? '');
    $sdt1  = trim($_POST['sdt1']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $sex   = $_POST['gioitinh']   ?? '';

    if ($ten === '' || $sdt1 === '' || $email === '' || $sex === '') {
        $resultMsg = "<div class='alert alert-danger'>Vui lòng không để trống bất kỳ trường nào.</div>";
    } elseif (!preg_match("/^0[0-9]{9}$/", $sdt1)) {
        $resultMsg = "<div class='alert alert-danger'>Số điện thoại không hợp lệ.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resultMsg = "<div class='alert alert-danger'>Email không hợp lệ.</div>";
    } else {
        $resultMsg = $us->update_user($ten, $sdt1, $sex, $email, $uid);
        Session::set('name',  $ten);
        Session::set('sdt',   $sdt1);
        Session::set('email', $email);
    }
}
?>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Trang cá nhân</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thông tin cá nhân <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section">
  <!-- container-fluid để rộng hơn, đỡ trống 2 bên -->
  <div class="container-fluid px-4 px-lg-5">
    <div class="row">
      <!-- Cột trái: Thông tin người dùng -->
      <div class="col-lg-3 mb-4">
        <div class="p-4 shadow rounded bg-white">
          <h4 class="font-weight-bold mb-3">Cập nhật thông tin</h4>
          <?= $resultMsg ?>
          <?php
          $usershow = $us->show_thongtin($uid);
          if ($usershow && $user = $usershow->fetch_assoc()):
          ?>
          <form action="" method="post">
            <div class="form-group">
              <label for="ten">Tên</label>
              <input type="text" name="ten" class="form-control" value="<?= htmlspecialchars($user['ten']) ?>" required>
            </div>
            <div class="form-group">
              <label for="sdt1">Số điện thoại</label>
              <input type="text" name="sdt1" class="form-control" value="<?= htmlspecialchars($user['sodienthoai']) ?>" required>
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group mt-3">
              <label>Giới tính</label><br>
              <label><input type="radio" name="gioitinh" value="1" <?= $user['gioitinh']==1?'checked':'' ?>> Nam</label>
              <label class="ml-3"><input type="radio" name="gioitinh" value="0" <?= $user['gioitinh']==0?'checked':'' ?>> Nữ</label>
            </div>
            <div class="form-group mt-4">
              <input type="submit" value="Cập nhật" class="btn btn-primary w-100 mb-2">
              <a href="pass.php?id=<?= Session::get('id') ?>" class="btn btn-outline-warning w-100">Đổi mật khẩu</a>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cột phải: Danh sách hợp đồng -->
      <div class="col-lg-9">
        <div class="p-4 shadow rounded bg-white">
          <h4 class="font-weight-bold mb-4">Danh sách bữa tiệc</h4>

          <div class="table-wrapper">
            <table class="table table-bordered text-center">
              <thead class="thead-blue">
                <tr>
                  <th>#</th>
                  <th>ID Hợp đồng</th>
                  <th>Ngày</th>
                  <!-- <th>Số lượng</th> -->
                  <th>Nội dung</th>
                  <th>Tiền</th>
                  <th>Trạng thái</th>
                  <th>Chi tiết</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $show = $ct->show_thongtin($uid);
                if ($show) {
                  $i = 0;
                  while ($r = $show->fetch_assoc()) {
                    $i++;
                    // Bạn đang hiển thị 'sesis' như ID hợp đồng -> ép int dùng làm hopdong_id
                    $hdId = (int)($r['sesis'] ?? 0);
                    ?>
                    <tr id="row-<?= $hdId ?>">
                      <td><?= $i ?></td>
                      <td><?= htmlspecialchars($r['sesis']) ?></td>
                      <td><?= htmlspecialchars($r['dates']) ?></td>
                  
                      <td><?= htmlspecialchars($r['noidung']) ?></td>
                      <td><?= $fm->formatMoney(($r['tongtien'] ?? $r['Sum(thanhtien)'] ?? 0), false, ',', '.') ?> VNĐ</td>
                      <td>
                        <?php
                        $ps = strtolower(trim($r['payment_status'] ?? ''));
                          if ($ps === 'pending') {
                              echo "<span class='badge badge-info'>Chưa thanh toán</span>";
                          } elseif ($ps === 'completed') {
                              echo "<span class='badge badge-success'>Đã thanh toán</span>";
                          } else {
                              echo "<span class='badge badge-warning'>Đã đặt cọc</span>";
                          }

                        ?>
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-info"
                                onclick="toggleDetail(<?= $hdId ?>, this)">
                        Chi tiết
                        </button>
                      </td>
                    </tr>
                    <tr id="detail-<?= $hdId ?>" class="detail-row d-none">
                      <td colspan="8" class="text-left p-0">
                        <div class="p-3 detail-body">Đang tải...</div>
                      </td>
                    </tr>
                    <?php
                  }
                } else {
                  echo "<tr><td colspan='8'>Không có dữ liệu</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- CSS BỔ SUNG -->
<style>
/* Nới rộng tổng thể (giữ khung gọn gàng) */
@media (min-width: 1200px){
  .container, .container-fluid { max-width: 1400px; }
}
@media (min-width: 1600px){
  .container, .container-fluid { max-width: 1600px; }
}

/* Bảng co giãn hết chiều ngang */
.table { width: 100%; }

/* Header bảng */
.thead-blue th {
  background-color: #0d6efd;
  color: #fff;
  text-transform: uppercase;
  font-size: 14px;
}

/* Badge */
.badge-success{ background:#28a745; }
.badge-warning{ background:#ffc107; color:#000; }

/* Hàng chi tiết */
.detail-row td { background:#f9f9f9; }

/* Nếu navbar sticky/fixed, tránh che nội dung khi cuộn tới anchor */
body { scroll-padding-top: 90px; }

/* ---- USERBLOG: giãn full, giảm khoảng trống 2 bên, bảng rộng hơn ---- */

/* 1) Bỏ giới hạn max-width đang chặn container-fluid ở 1400/1600px */
.ftco-section .container-fluid{
  max-width: 100% !important;
  width: 100% !important;
  padding-left: 8px !important;   /* giảm padding 2 bên */
  padding-right: 8px !important;
}

/* 2) Giảm gutter giữa 2 cột (áp dụng trong section này thôi) */
.ftco-section .row{
  margin-left: -8px;
  margin-right: -8px;
}
.ftco-section .row > [class*="col-"]{
  padding-left: 8px;
  padding-right: 8px;
}

/* 3) Nới rộng cột phải, thu hẹp cột trái (không đổi HTML) */
@media (min-width: 1200px){
  .ftco-section .col-lg-3{  /* form trái */
    flex: 0 0 20%;
    max-width: 20%;
  }
  .ftco-section .col-lg-9{  /* bảng phải */
    flex: 0 0 80%;
    max-width: 80%;
  }
}

/* 4) Đảm bảo bảng căng hết chiều ngang khối phải */
.ftco-section .table-wrapper,
.ftco-section .table-responsive,
.ftco-section .table{
  width: 100%;
}

/* (tuỳ chọn) Nếu muốn căng sát hơn trên màn rất rộng */
/*
@media (min-width: 1600px){
  .ftco-section .container,
  .ftco-section .container-fluid{
    max-width: 100vw !important;
  }
}
*/

</style>

<!-- JS -->
<script>
function toggleDetail(hopdongId, btn){
  const row = document.getElementById('detail-' + hopdongId);
  if(!row) return;

  if(row.classList.contains('d-none')){
    row.classList.remove('d-none');
    const body = row.querySelector('.detail-body');
    body.textContent = 'Đang tải...';
    fetch('userblog.php?ajax=ct&hopdong_id=' + encodeURIComponent(hopdongId))
      .then(r => r.text())
      .then(html => {
        body.innerHTML = html;
        if(btn) btn.textContent = 'Ẩn';
      })
      .catch(() => {
        body.innerHTML = '<div class="p-3 text-danger">Lỗi khi tải chi tiết.</div>';
      });
  } else {
    row.classList.add('d-none');
    if(btn) btn.textContent = 'Chi tiết';
  }
}
</script>




<?php include 'inc/footer.php'; ?>
