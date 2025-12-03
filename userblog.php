<?php
// =================================================================
// 1. XỬ LÝ AJAX (Server Side)
// =================================================================
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=utf-8');
    if (!isset($db) || !isset($db->link)) {
        include_once __DIR__ . '/lib/database.php';
        $db = new Database();
    }

    // A. AJAX LẤY CHI TIẾT
    if ($_GET['ajax'] === 'ct') {
        $hopdong_id = isset($_GET['hopdong_id']) ? (int)$_GET['hopdong_id'] : 0;
        if ($hopdong_id <= 0) { echo '<div class="p-3 text-danger">Thiếu ID.</div>'; exit; }

        $sql = "SELECT c.*, m.name_mon, m.images, m.gia_mon
                FROM hopdong_chitiet c
                JOIN monan m ON m.id_mon = c.monan_id
                WHERE c.hopdong_id = {$hopdong_id}";
        $rs = $db->select($sql);

        if ($rs && $rs->num_rows > 0) {
            $tong = 0;
            echo '<div class="table-responsive"><table class="table table-sm table-bordered mb-2" style="background:#fff;">';
            echo '<thead class="bg-light text-center"><tr><th>Món</th><th>SL</th><th>Giá</th><th>Thành tiền</th></tr></thead><tbody>';
            while ($r = $rs->fetch_assoc()) {
                $gia = (float)(isset($r['gia']) ? $r['gia'] : $r['gia_mon']);
                $tt  = (float)(isset($r['thanhtien']) ? $r['thanhtien'] : ($r['soluong'] * $gia));
                $tong += $tt;
                echo "<tr>
                        <td>".htmlspecialchars($r['name_mon'])."</td>
                        <td class='text-center'>".(int)$r['soluong']."</td>
                        <td class='text-right'>".number_format($gia,0,',','.')."</td>
                        <td class='text-right font-weight-bold'>".number_format($tt,0,',','.')."</td>
                      </tr>";
            }
            echo '</tbody></table>
              <div class="d-flex justify-content-between px-3 pb-2 border-top pt-2">
                <div class="font-weight-bold text-primary">Tổng cộng: ' . number_format($tong, 0, ',', '.') . ' VNĐ</div>
                <a href="danhgia.php?hopdong_id=' . $hopdong_id . '" class="btn btn-sm btn-outline-primary">Đánh giá món ăn</a>
              </div>
            </div>';
        } else {
            echo '<div class="p-3 text-center text-muted">Chưa có món ăn nào.</div>';
        }
        exit;
    }

    // B. AJAX HỦY ĐƠN (Chỉ dùng cho đơn chưa thanh toán)
    if ($_GET['ajax'] === 'cancel_order') {
        header('Content-Type: application/json; charset=utf-8');
        include_once __DIR__ . '/lib/session.php';
        Session::init();
        
        $id_hd = isset($_POST['hopdong_id']) ? (int)$_POST['hopdong_id'] : 0;
        $u_id  = Session::get('id');

        if ($id_hd <= 0 || !$u_id) { echo json_encode(['success'=>false, 'message'=>'Lỗi dữ liệu']); exit; }

        $check = $db->select("SELECT id, so_ban, payment_status FROM hopdong WHERE id=$id_hd AND id_user=$u_id LIMIT 1");
        if (!$check || $check->num_rows===0) { echo json_encode(['success'=>false, 'message'=>'Lỗi quyền']); exit; }
        $hd = $check->fetch_assoc();

        $st = strtolower($hd['payment_status'] ?? '');
        if ($st === 'completed' || $st === 'confirmed') {
            echo json_encode(['success'=>false, 'message'=>'Đơn đã thanh toán không thể tự hủy.']); exit;
        }

        $db->update("UPDATE hopdong SET payment_status='cancelled', status=0 WHERE id=$id_hd");
        if (!empty($hd['so_ban'])) {
            $db->update("UPDATE ban SET trangthai=0, hopdong_id=NULL WHERE id_ban IN ({$hd['so_ban']})");
        }
        echo json_encode(['success'=>true]);
        exit;
    }
}

// =================================================================
// 2. GIAO DIỆN CHÍNH
// =================================================================
include 'inc/header.php';
Session::checkSession();
$uid = $_GET['id'];

$resultMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = $_POST['ten']??''; $sdt1 = $_POST['sdt1']??''; $email = $_POST['email']??''; $sex = $_POST['gioitinh']??'';
    if ($ten===''||$sdt1===''||$email==='') {
        $resultMsg = "<div class='alert alert-danger'>Thiếu thông tin.</div>";
    } else {
        $us->update_user($ten, $sdt1, $sex, $email, $uid);
        Session::set('name',$ten); Session::set('sdt',$sdt1); Session::set('email',$email);
        $resultMsg = "<div class='alert alert-success'>Cập nhật xong!</div>";
    }
}
?>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Trang cá nhân</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thông tin & Lịch sử</span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section">
  <div class="container-fluid px-4 px-lg-5">
    <div class="row">
      
      <div class="col-lg-3 mb-4">
        <div class="p-4 shadow rounded bg-white">
          <h4 class="font-weight-bold mb-3">Hồ sơ của tôi</h4>
          <?= $resultMsg ?>
          <?php if ($usershow = $us->show_thongtin($uid)->fetch_assoc()): ?>
          <form action="" method="post">
            <div class="form-group"><label>Tên hiển thị</label><input type="text" name="ten" class="form-control" value="<?= htmlspecialchars($usershow['ten']) ?>" required></div>
            <div class="form-group"><label>Số điện thoại</label><input type="text" name="sdt1" class="form-control" value="<?= htmlspecialchars($usershow['sodienthoai']) ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usershow['email']) ?>" required></div>
            <div class="form-group mt-3">
              <label>Giới tính</label><br>
              <label><input type="radio" name="gioitinh" value="1" <?= $usershow['gioitinh']==1?'checked':'' ?>> Nam</label>
              <label class="ml-3"><input type="radio" name="gioitinh" value="0" <?= $usershow['gioitinh']==0?'checked':'' ?>> Nữ</label>
            </div>
            <div class="form-group mt-4">
              <input type="submit" value="Cập nhật" class="btn btn-primary w-100 mb-2">
              <a href="pass.php?id=<?= Session::get('id') ?>" class="btn btn-outline-warning w-100">Đổi mật khẩu</a>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="p-4 shadow rounded bg-white">
          <h4 class="font-weight-bold mb-4">Lịch sử đặt bàn</h4>
          <div class="table-wrapper">
            <table class="table table-bordered text-center align-middle">
              <thead class="text-white" style="background-color: #0d6efd;">
                <tr>
                  <th class="py-3">#</th>
                  <th class="py-3">Mã đơn</th>
                  <th class="py-3">Ngày đặt</th>
                  <th class="py-3">Nội dung</th>
                  <th class="py-3">Tổng tiền</th>
                  <th class="py-3">Trạng thái</th>
                  <th class="py-3" style="min-width: 170px;">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $show = $ct->show_thongtin($uid);
                if ($show) {
                  $i = 0;
                  while ($r = $show->fetch_assoc()) {
                    $i++;
                    // Xử lý biến an toàn
                    $hdId = (int)($r['sesis'] ?? $r['id'] ?? 0);
                    $dates = $r['dates'] ?? '';
                    $time = $r['tg'] ?? ''; 
                    $noidung = $r['noidung'] ?? '';
                    $total = $r['tongtien'] ?? $r['Sum(thanhtien)'] ?? 0;
                    $st = strtolower(trim($r['payment_status'] ?? ''));
                    
                    // Logic Thời Gian
                    $calcTime = $time ? $time : '23:59:59';
                    $bookingTime = strtotime($dates . ' ' . $calcTime);
                    $isPast = time() > $bookingTime; 

                    // Badge Trạng Thái
                    $badge = '';
                    if ($st==='pending') $badge="<span class='badge badge-info'>Chưa thanh toán</span>";
                    elseif ($st==='completed'||$st==='confirmed') $badge="<span class='badge badge-success'>Đã thanh toán</span>";
                    elseif ($st==='cancelled') $badge="<span class='badge badge-secondary'>Đã hủy</span>";
                    else $badge="<span class='badge badge-warning'>Đã cọc</span>";
                    ?>
                    <tr id="row-<?= $hdId ?>">
                      <td class="align-middle"><?= $i ?></td>
                      <td class="align-middle font-weight-bold">#<?= $hdId ?></td>
                      <td class="align-middle">
                          <div class="font-weight-bold"><?= date('d/m/Y', strtotime($dates)) ?></div>
                          <?php if($time): ?><small class="text-muted"><?= $time ?></small><?php endif; ?>
                      </td>
                      <td class="text-left align-middle" style="max-width: 250px; font-size:13px;"><?= htmlspecialchars($noidung) ?></td>
                      <td class="align-middle font-weight-bold"><?= $fm->formatMoney($total, false, ',', '.') ?>đ</td>
                      <td class="align-middle"><?= $badge ?></td>
                      
                      <td class="align-middle">
                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-sm btn-info mr-1 text-white" onclick="toggleDetail(<?= $hdId ?>, this)" style="min-width:65px;">Chi tiết</button>
                            
                            <?php if ($st !== 'cancelled' && !$isPast): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger ml-1" onclick="openCancelModal()" style="min-width:65px;">Hủy đơn</button>
                            <?php elseif ($isPast && $st !== 'cancelled'): ?>
                                <button type="button" class="btn btn-sm btn-light text-muted ml-1" disabled style="min-width:65px; border:1px solid #eee;">Hết hạn</button>
                            <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                    <tr id="detail-<?= $hdId ?>" class="detail-row d-none">
                      <td colspan="7" class="text-left p-0"><div class="p-3 detail-body">Đang tải...</div></td>
                    </tr>
                    <?php
                  }
                } else { echo "<tr><td colspan='7' class='py-4 text-muted'>Bạn chưa có đơn hàng nào.</td></tr>"; }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cancel-modal-overlay" id="cancelModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="cancel-modal" style="background:#fff; width:90%; max-width:550px; padding:0; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow:hidden;">
        
        <div style="background: #dc3545; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin:0; font-weight: bold; color:white;"><i class="fa fa-exclamation-triangle"></i> Yêu cầu hủy đặt bàn</h5>
            <button onclick="closeCancelModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div style="padding: 25px;">
            <p class="text-center mb-3 text-muted">Để đảm bảo quyền lợi, vui lòng liên hệ nhân viên để được hỗ trợ xử lý thủ tục hoàn tiền.</p>
            
            <div style="background:#f8f9fa; border:1px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: left;">
                <h6 class="font-weight-bold text-dark mb-3" style="text-transform: uppercase; font-size:14px; border-bottom:1px solid #ddd; padding-bottom:8px;">
                    <i class="fa fa-file-text-o"></i> Điều khoản Hủy & Hoàn tiền
                </h6>
                <ul style="padding-left: 20px; margin-bottom: 0; font-size: 14px; color: #555; line-height: 1.6;">
                    <li style="margin-bottom: 8px;">
                        <b>Thời gian:</b> Quý khách vui lòng liên hệ hủy trước ít nhất <b class="text-danger">01 ngày</b> so với giờ đặt.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <b>Phí phạt:</b> Mọi trường hợp hủy đơn đã cọc đều sẽ khấu trừ <b class="text-danger">30%</b> phí dịch vụ giữ chỗ (Hoàn lại 70%).
                    </li>
                    <li>
                        <b>Lưu ý:</b> Các yêu cầu hủy sát giờ (trong vòng 24h) sẽ không được hoàn tiền.
                    </li>
                </ul>
            </div>

            <div class="d-flex justify-content-center mt-4" style="gap: 15px;">
                <a href="chat.php" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-comments"></i> Chat với Admin
                </a>
                <a href="tel:0912345678" class="btn btn-success px-4 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-phone"></i> Gọi Hotline
                </a>
            </div>
            
            <div class="text-center mt-3">
                <a href="javascript:void(0)" onclick="closeCancelModal()" class="text-muted small" style="text-decoration: underline;">Tôi muốn giữ lại đơn hàng</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Căn giữa nội dung bảng */
.table td, .table th { vertical-align: middle !important; }
/* Nút bấm hover */
.btn:hover { transform: translateY(-1px); }
/* Animation Modal */
.cancel-modal { animation: slideDown 0.3s ease-out; }
@keyframes slideDown { from {transform: translateY(-20px); opacity:0;} to {transform: translateY(0); opacity:1;} }
</style>

<script>
function toggleDetail(id, btn){
  const row = document.getElementById('detail-' + id);
  if(!row) return;
  if(row.classList.contains('d-none')){
    row.classList.remove('d-none');
    fetch('userblog.php?ajax=ct&hopdong_id='+id).then(r=>r.text()).then(h=>{
      row.querySelector('.detail-body').innerHTML=h;
      btn.textContent='Ẩn';
      btn.classList.add('btn-secondary'); btn.classList.remove('btn-info');
    });
  } else {
    row.classList.add('d-none');
    btn.textContent='Chi tiết';
    btn.classList.add('btn-info'); btn.classList.remove('btn-secondary');
  }
}
function openCancelModal(){ document.getElementById('cancelModalOverlay').style.display='flex'; }
function closeCancelModal(){ document.getElementById('cancelModalOverlay').style.display='none'; }
document.getElementById('cancelModalOverlay').addEventListener('click', function(e){if(e.target===this)closeCancelModal();});
</script>

<?php include 'inc/footer.php'; ?>