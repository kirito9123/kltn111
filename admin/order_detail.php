<?php
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/hopdong.php';
require_once '../classes/user.php';
require_once '../helpers/format.php';

$fm = new Format();
$hopdong = new HopDong();
$user = new user();

if (empty($_GET['sesis'])) {
    echo "<script>window.location='admin_order.php'</script>"; exit;
}
$sesis = $_GET['sesis'];

// 1) Thông tin hợp đồng (cha)
$hopdong_info = null;
if ($rs = $hopdong->getOrderInfoBySesis($sesis)) {
    $hopdong_info = $rs->fetch_assoc();
}
if (!$hopdong_info) {
    echo '<div style="padding:16px;color:#b00020;">Không tìm thấy đơn hàng!</div>';
    include 'inc/footer.php'; exit;
}

// 2) Tên khách
$ten_khach = 'Không xác định';
if (!empty($hopdong_info['id_user'])) {
    $u = $user->get_user_by_id($hopdong_info['id_user']);
    if ($u && isset($u['ten'])) $ten_khach = $u['ten'];
}

// 3) Danh sách món (con) + tính tổng
$list_monan = [];
$tongtien = 0;
if ($rs = $hopdong->getOrderDetailsBySesis($sesis)) {
    while ($r = $rs->fetch_assoc()) {
        $r['name_mon']  = $r['name_mon'] ?? ('Món #' . ($r['monan_id'] ?? ''));
        $r['soluong']   = isset($r['soluong']) ? (int)$r['soluong'] : 0;
        $r['gia']       = isset($r['gia']) ? (float)$r['gia'] : 0;
        $r['thanhtien'] = isset($r['thanhtien']) ? (float)$r['thanhtien'] : ($r['soluong'] * $r['gia']);
        $tongtien += $r['thanhtien'];
        $list_monan[] = $r;
    }
}

// 4) Trạng thái thanh toán (enum: pending/completed/failed)
$stt = $hopdong_info['payment_status'] ?? 'pending';
$stt_text  = $stt === 'completed' ? 'Hoàn tất' : ($stt === 'failed' ? 'Thất bại' : 'Chờ thanh toán');
$stt_style = $stt === 'completed' ? 'background:#28a745;color:#fff;'
           : ($stt === 'failed'    ? 'background:#dc3545;color:#fff;' : 'background:#ffc107;color:#333;');
?>

<style>
.contract-wrap{max-width:980px;margin:0 auto}
.meta{display:grid;grid-template-columns:220px 1fr;gap:10px 16px;margin:14px 0 20px}
.meta .val{background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:8px}
.table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee;border-radius:10px;overflow:hidden}
.table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left}
.table tfoot td{font-weight:700}
.badge{display:inline-block;padding:6px 10px;border-radius:8px;font-weight:600}
.backbtn{background:#6c757d;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none}
</style>

<div style="margin-bottom:15px">
  <a class="backbtn" href="http://restaurant.test/admin/admin_orders.php">
    ← Quay lại danh sách đơn hàng
  </a>
</div>


<div class="grid_10">
  <div class="box round first grid">
    <h2 style="text-align:center">Chi tiết đơn hàng</h2>
    <div class="block contract-wrap">

      <!-- Thông tin chung -->
      <div class="meta">
        <div><b>Mã đơn hàng</b></div><div class="val"><?= htmlspecialchars($hopdong_info['sesis']) ?></div>
        <div><b>Tên khách hàng</b></div><div class="val"><?= htmlspecialchars($ten_khach) ?></div>
        <div><b>Số lượng khách</b></div><div class="val"><?= (int)($hopdong_info['so_user'] ?? 0) ?></div>
        <div><b>Số bàn</b></div><div class="val"><?= (int)($hopdong_info['so_ban'] ?? 0) ?></div>
        <div><b>Giờ</b></div><div class="val"><?= htmlspecialchars($hopdong_info['tg'] ?? '') ?></div>
        <div><b>Ngày</b></div><div class="val"><?= htmlspecialchars($hopdong_info['dates'] ?? '') ?></div>
        <div><b>Nội dung</b></div><div class="val"><?= htmlspecialchars($hopdong_info['noidung'] ?? '') ?></div>
        <div><b>Trạng thái thanh toán</b></div>
        <div><span class="badge" style="<?= $stt_style ?>"><?= $stt_text ?></span></div>
      </div>


      <?php
        $tongtien = 0;
        if (!empty($list_monan)) {
            foreach ($list_monan as &$it) {
                $gia       = (float)($it['gia'] ?? 0);
                $soluong   = (int)($it['soluong'] ?? 0);
                $thanhtien = $gia * $soluong;
                $it['thanhtien'] = $thanhtien; // gán lại cho chắc
                $tongtien += $thanhtien;
            }
        }
        ?>

      <!-- Bảng chi tiết món -->


      <table class="table">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Món ăn</th>
            <th style="width:120px">Số lượng</th>
            <th style="width:160px">Đơn giá (đ)</th>
            <th style="width:180px">Thành tiền (đ)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($list_monan)): ?>
            <?php $i=1; foreach ($list_monan as $it): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($it['name_mon']) ?></td>
              <td><?= (int)$it['soluong'] ?></td>
              <td><?= number_format((float)$it['gia'], 0, ',', '.') ?></td>
              <td><?= number_format((float)$it['thanhtien'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" style="color:#666">Không có món nào trong hợp đồng.</td></tr>
          <?php endif; ?>
          <tr>
              <td></td>
              <td></td>
              <td></td>
              <td>Tổng tiền</td>
              <td><?= number_format($tongtien, 0, ',', '.') ?> đ</td>
            </tr>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4" style="text-align:right">Tổng tiền</td>
            <td><?= number_format($tongtien, 0, ',', '.') ?> đ</td>
            
        </tr>
        </tfoot>
      </table>

    </div>
  </div>
</div>

<?php include 'inc/footer.php'; ?>
