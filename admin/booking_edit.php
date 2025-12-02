<?php
// Bật bộ đệm đầu ra
ob_start(); 

include 'inc/header.php';
include 'inc/sidebar.php';
include_once __DIR__ . '/../classes/nhanvienquay.php';

$nv = new nhanvienquay();

// 1. Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'booking_list.php';</script>";
    exit;
}
$id_hd = (int)$_GET['id'];

// =================================================================================
// XỬ LÝ AJAX
// =================================================================================
if (isset($_POST['ajax_action'])) {
    ob_clean(); 
    header('Content-Type: application/json');

    $action = $_POST['ajax_action'];

    try {
        // [CHECK] CHẶN SỬA NẾU ĐÃ XONG
        $check_hd = $nv->get_thong_tin_don_hang($id_hd)->fetch_assoc();
        if ($check_hd && $check_hd['payment_status'] == 'completed') {
            echo json_encode(['status' => 'error', 'msg' => 'Đơn hàng ĐÃ KHÓA, không thể chỉnh sửa!']);
            exit;
        }

        // --- CÁC HÀNH ĐỘNG THAY ĐỔI DỮ LIỆU ---
        if ($action == 'add' && isset($_POST['id_mon'])) {
            $nv->them_mon_vao_don($id_hd, $_POST['id_mon']);
        } 
        elseif ($action == 'delete' && isset($_POST['id_chitiet'])) {
            $id_ct = $_POST['id_chitiet'];

            // --- [MỚI] KIỂM TRA TRẠNG THÁI TRƯỚC KHI XÓA ---
            // Kiểm tra xem món này bếp làm xong chưa (trangthai = 1)
            $db_check = new Database(); 
            $check_query = "SELECT trangthai FROM hopdong_chitiet WHERE id = '$id_ct'";
            $res_check = $db_check->select($check_query);

            if ($res_check) {
                $r_check = $res_check->fetch_assoc();
                if ($r_check['trangthai'] == 1) {
                    // NẾU BẾP ĐÃ LÀM XONG -> CHẶN NGAY
                    echo json_encode(['status' => 'error', 'msg' => 'Món này Bếp đã làm xong (hoặc đang ra), KHÔNG THỂ HỦY!']);
                    exit;
                }
            }
            // ------------------------------------------------

            $nv->xoa_mon_khoi_don($id_ct);
        }
        elseif ($action == 'update_all' && isset($_POST['qty_data'])) {
            $data = json_decode($_POST['qty_data'], true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    $nv->cap_nhat_so_luong($item['id'], $item['val']);
                }
            }
        }
        elseif ($action == 'pay') {
            if (isset($_POST['qty_data'])) {
                $data = json_decode($_POST['qty_data'], true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        $nv->cap_nhat_so_luong($item['id'], $item['val']);
                    }
                }
            }
            $method = isset($_POST['method']) ? $_POST['method'] : 'cash';
            $nv->thanh_toan_hoan_tat($id_hd, $method);
            echo json_encode(['status' => 'completed', 'msg' => 'Thanh toán thành công!']);
            exit;
        }

        // --- VẼ LẠI GIAO DIỆN (Cập nhật hiển thị trạng thái Mới/Xong) ---
        $list_mon = $nv->get_chi_tiet_mon_an($id_hd);
        $html = '';

        if ($list_mon) {
            while ($row = $list_mon->fetch_assoc()) {
                // --- [MỚI] Xử lý hiển thị trạng thái trong AJAX ---
                $status_label = '';
                $btn_delete_html = '';

                if ($row['trangthai'] == 1) {
                    $status_label = '<span style="color:green; font-weight:bold; font-size:11px; background:#e8f5e9; padding:2px 5px; border-radius:4px;">(Đã ra)</span>';
                    // Bếp làm rồi -> Không hiện nút xóa
                    $btn_delete_html = ''; 
                } else {
                    $status_label = '<span style="color:#d35400; font-weight:bold; font-size:11px; background:#fff3e0; padding:2px 5px; border-radius:4px;">(Đợi bếp)</span>';
                    // Chưa làm -> Hiện nút xóa
                    $btn_delete_html = '<button type="button" onclick="deleteItem('.$row['id'].')" class="btn-del-mini">&times;</button>';
                }
                // --------------------------------------------------

                $html .= '
                <tr class="item-row">
                    <td style="padding-left:15px;">
                        <div style="font-weight:600; color:#333; margin-bottom:2px;">
                            '.htmlspecialchars($row['name_mon']).' '.$status_label.'
                        </div>
                        <small style="color:#888;">'.number_format($row['gia'], 0, ',', '.').'</small>
                    </td>
                    <td align="center">
                        <input type="number" 
                               data-id="'.$row['id'].'" 
                               value="'.$row['soluong'].'" 
                               class="qty-input js-qty" 
                               min="1" 
                               oninput="markAsDirty()"> 
                    </td>
                    <td align="right" style="font-weight:600; color:#333;">
                        <span class="js-item-total">'.number_format($row['thanhtien'], 0, ',', '.').'</span>
                    </td>
                    <td align="center">
                        '.$btn_delete_html.'
                    </td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">Chưa có món nào trong đơn</td></tr>';
        }

        $tai_chinh = $nv->lay_chi_tiet_tai_chinh($id_hd);

        echo json_encode([
            'status' => 'success',
            'html' => $html,
            'tong_tien_mon' => $tai_chinh['tong_tien_mon'],
            'da_coc' => $tai_chinh['da_coc'],
            'can_thanh_toan' => $tai_chinh['can_thanh_toan']
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit; 
}
// =================================================================================

// 3. Lấy dữ liệu hiển thị lần đầu
$info_don = $nv->get_thong_tin_don_hang($id_hd)->fetch_assoc();

// [SỬA LỖI] Tạo biến khóa
$is_locked = ($info_don['payment_status'] == 'completed');

$list_mon_trong_don = $nv->get_chi_tiet_mon_an($id_hd);
$tai_chinh_init = $nv->lay_chi_tiet_tai_chinh($id_hd);
$ds_loai = $nv->get_danh_sach_loai_mon();
$current_loai = isset($_GET['loai']) ? (int)$_GET['loai'] : 0;
$keyword = isset($_GET['s']) ? $_GET['s'] : '';
$menu = $nv->get_menu_theo_loai($current_loai, $keyword);

ob_end_flush(); 
?>

<style>
    * { box-sizing: border-box; } 
    body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
    .pos-container { display: flex; gap: 0; height: 80vh; width: 100%; overflow: hidden; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); border: 1px solid #e1e3e6; }
    
    .cat-sidebar { width: 200px; background: #f8f9fa; border-right: 1px solid #e1e3e6; display: flex; flex-direction: column; flex-shrink: 0; }
    .cat-header { padding: 18px 15px; font-weight: 800; text-transform: uppercase; color: #495057; font-size: 14px; border-bottom: 1px solid #e1e3e6; background: #f1f3f5; }
    .cat-list { overflow-y: auto; flex: 1; }
    .cat-item { display: block; padding: 14px 20px; color: #555; text-decoration: none; border-bottom: 1px solid #eee; font-size: 14px; font-weight: 500; transition: all 0.2s; }
    .cat-item:hover { background: #e9ecef; color: #000; padding-left: 25px; }
    .cat-item.active { background: #fff; color: #0d6efd; font-weight: 700; border-left: 4px solid #0d6efd; }

    .menu-area { flex: 1; display: flex; flex-direction: column; border-right: 1px solid #e1e3e6; min-width: 0; background: #fff; position: relative;}
    .search-bar-wrap { padding: 12px 15px; border-bottom: 1px solid #e1e3e6; display: flex; gap: 10px; align-items: center; background: #fff; }
    .btn-back { background: #6c757d; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px; cursor: pointer;}
    .menu-scroll { flex: 1; overflow-y: auto; padding: 20px; background: #fdfdfd; position: relative; }
    #loading-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.8); z-index: 100; display: none; align-items: center; justify-content: center; font-weight: bold; color: #0d6efd; flex-direction: column; gap: 10px; font-size: 16px;}

    .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 20px; }
    .menu-card { background: #fff; border: 1px solid #eee; border-radius: 10px; overflow: hidden; cursor: pointer; position: relative; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
    .menu-card:hover { transform: translateY(-4px); box-shadow: 0 8px 15px rgba(13, 110, 253, 0.15); border-color: #a6cbfd; }
    .menu-card:active { transform: scale(0.98); }
    .menu-img { width: 100%; height: 130px; object-fit: cover; border-bottom: 1px solid #f0f0f0; display: block; }
    .menu-body { padding: 12px; text-align: center; }
    .menu-name { font-size: 14px; font-weight: 600; color: #333; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 6px; line-height: 1.4; }
    .menu-price { font-size: 15px; color: #d63031; font-weight: 800; }
    .btn-add-overlay { position: absolute; inset: 0; background: transparent; width: 100%; height: 100%; cursor: pointer; z-index: 2; border:none;}

    .bill-panel { width: 400px; display: flex; flex-direction: column; background: #fff; flex-shrink: 0; z-index: 10; box-shadow: -5px 0 20px rgba(0,0,0,0.03); }
    .order-header { padding: 20px; background: linear-gradient(135deg, #0d6efd, #0043a8); color: white; }
    .order-header h3 { margin: 0; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .order-list-wrap { flex: 1; overflow-y: auto; padding: 0; background: #fff; position: relative; }
    .table-order { width: 100%; border-collapse: collapse; }
    .table-order th { position: sticky; top: 0; background: #f8f9fa; z-index: 5; padding: 12px 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6c757d; border-bottom: 2px solid #e9ecef; }
    .table-order td { padding: 12px 10px; border-bottom: 1px solid #f1f3f5; vertical-align: middle; font-size: 14px; }
    .qty-input { width: 45px; text-align: center; border: 1px solid #ced4da; border-radius: 6px; padding: 5px; font-weight: 700; }
    .btn-del-mini { color: #dc3545; background: #ffe6e6; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; }
    .btn-del-mini:hover { background: #dc3545; color: white; }

    .order-footer { padding: 20px; background: #fff; border-top: 1px solid #e1e3e6; flex-shrink: 0; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); }
    .pay-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; color: #555; }
    .pay-row.total-mon { font-weight: 600; color: #333; }
    .pay-row.deposit { color: #0d6efd; }
    .pay-final { margin-top: 15px; padding-top: 15px; border-top: 2px dashed #dee2e6; display: flex; justify-content: space-between; align-items: center; }
    .pay-final .label { font-size: 16px; font-weight: 800; text-transform: uppercase; color: #333; }
    .pay-final .value { font-size: 22px; font-weight: 800; }

    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .btn-action { flex: 1; border: none; padding: 15px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; color: white; }
    .btn-update { background: #20c997; box-shadow: 0 4px 10px rgba(32, 201, 151, 0.3); }
    .btn-update:hover { background: #1baa80; }
    .btn-pay { background: #0d6efd; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
    .btn-pay:hover { background: #0b5ed7; }

    .modal-payment-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
    .modal-payment-box { background: #fff; width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center; animation: slideDown 0.3s; }
    .modal-title { font-size: 20px; font-weight: 800; color: #333; margin-bottom: 20px; text-transform: uppercase; }
    .modal-amount { font-size: 32px; font-weight: 800; color: #d63031; margin: 15px 0; display: block; }
    .modal-note { font-size: 14px; color: #666; margin-bottom: 25px; font-style: italic; }
    .payment-options { display: flex; flex-direction: column; gap: 12px; }
    .btn-method { padding: 15px; border: 2px solid #eee; border-radius: 8px; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 600; font-size: 15px; transition: 0.2s; }
    .btn-method:hover { border-color: #0d6efd; background: #f8f9fa; color: #0d6efd; }
    .btn-method i { font-size: 20px; }
    .btn-method.cash i { color: #27ae60; }
    .btn-method.vnpay i { color: #005baa; }
    .btn-close-modal { margin-top: 15px; background: none; border: none; text-decoration: underline; color: #888; cursor: pointer; }
    @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: transparent; box-shadow: none; border:none; padding: 0;">
        <div class="pos-container">
            
            <?php if (!$is_locked): ?>
            <div class="cat-sidebar">
                <div class="cat-header"><i class="fa fa-bars"></i> Danh mục</div>
                <div class="cat-list">
                    <a href="booking_edit.php?id=<?php echo $id_hd; ?>" class="cat-item <?php echo ($current_loai == 0) ? 'active' : ''; ?>">Tất cả</a>
                    <?php 
                    if ($ds_loai) {
                        while ($loai = $ds_loai->fetch_assoc()) {
                            $active = ($current_loai == $loai['id_loai']) ? 'active' : '';
                            echo '<a href="booking_edit.php?id='.$id_hd.'&loai='.$loai['id_loai'].'" class="cat-item '.$active.'">'.htmlspecialchars($loai['name_loai']).'</a>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="menu-area">
                <div class="search-bar-wrap">
                    <div class="btn-back" onclick="goBackCheck()">
                        <i class="fa fa-arrow-left"></i> Thoát
                    </div>
                    
                    <form method="GET" style="flex:1; display:flex; margin:0;">
                        <input type="hidden" name="id" value="<?php echo $id_hd; ?>">
                        <input type="hidden" name="loai" value="<?php echo $current_loai; ?>">
                        <input type="text" name="s" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm tên món ăn..." style="width:100%; padding: 10px 15px; border-radius: 30px; border: 1px solid #e1e3e6; outline: none; font-size: 14px; background: #f8f9fa;">
                    </form>
                </div>

                <div class="menu-scroll">
                    <div id="loading-overlay">
                        <i class="fa fa-circle-o-notch fa-spin fa-2x"></i>
                        <span>Đang xử lý...</span>
                    </div>

                    <div class="menu-grid">
                        <?php 
                        if ($menu && $menu->num_rows > 0) {
                            while ($m = $menu->fetch_assoc()) {
                                $img = !empty($m['images']) ? "../images/food/".$m['images'] : "img/no-food.png";
                                ?>
                                <div class="menu-card" onclick="addToOrder(<?php echo $m['id_mon']; ?>)">
                                    <div class="btn-add-overlay"></div>
                                    <img src="<?php echo $img; ?>" class="menu-img" onerror="this.src='img/no-food.png'">
                                    <div class="menu-body">
                                        <div class="menu-name"><?php echo htmlspecialchars($m['name_mon']); ?></div>
                                        <div class="menu-price"><?php echo number_format($m['gia_mon'], 0, ',', '.'); ?></div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div style="grid-column: 1/-1; text-align:center; color:#999; padding:50px;">Không tìm thấy món nào.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; background:#f8f9fa; border-right:1px solid #ddd;">
                <i class="fa fa-lock" style="font-size: 60px; color:#ccc; margin-bottom:20px;"></i>
                <h2 style="color:#555;">Đơn hàng đã hoàn tất</h2>
                <p style="color:#888;">Chế độ chỉ xem (Read-only)</p>
                <button class="btn-back" style="background:#0d6efd; border:none; padding:10px 20px; color:white; border-radius:5px; cursor:pointer; margin-top:10px;" onclick="window.location.href='booking_list.php'">
                    <i class="fa fa-arrow-left"></i> Quay về danh sách
                </button>
            </div>
            <?php endif; ?>

            <div class="bill-panel">
                <div class="order-header">
                    <h3>#<?php echo $id_hd; ?> - <?php echo htmlspecialchars($info_don['tenKH']); ?></h3>
                    <div style="font-size:13px; margin-top:5px; opacity: 0.9;">
                        <?php 
                            if(!empty($info_don['so_ban'])) echo "<i class='fa fa-table'></i> Bàn: <b>" . $info_don['so_ban'] . "</b>";
                            if(!empty($info_don['phong'])) echo " &bull; <i class='fa fa-home'></i> Phòng: <b>" . $info_don['phong'] . "</b>";
                        ?>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; flex: 1; overflow: hidden; height: 100%;">
                    <div class="order-list-wrap">
                        <table class="table-order">
                            <thead>
                                <tr>
                                    <th style="text-align:left; padding-left:15px;">Món</th>
                                    <th style="text-align:center;">SL</th>
                                    <th style="text-align:right;">Tiền</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <?php
                                if ($list_mon_trong_don) {
                                    while ($row = $list_mon_trong_don->fetch_assoc()) {
                                        // --- [MỚI] Hiển thị trạng thái ban đầu ---
                                        $status_label = '';
                                        $can_delete = true;

                                        if (isset($row['trangthai']) && $row['trangthai'] == 1) {
                                            $status_label = '<span style="color:green; font-weight:bold; font-size:11px; background:#e8f5e9; padding:2px 5px; border-radius:4px;">(Đã ra)</span>';
                                            $can_delete = false; // Bếp làm rồi
                                        } else {
                                            $status_label = '<span style="color:#d35400; font-weight:bold; font-size:11px; background:#fff3e0; padding:2px 5px; border-radius:4px;">(Đợi bếp)</span>';
                                        }
                                        // ----------------------------------------
                                        ?>
                                        <tr class="item-row">
                                            <td style="padding-left:15px;">
                                                <div style="font-weight:600; color:#333; margin-bottom:2px;">
                                                    <?php echo htmlspecialchars($row['name_mon']); ?> <?php echo $status_label; ?>
                                                </div>
                                                <small style="color:#888;"><?php echo number_format($row['gia'], 0, ',', '.'); ?></small>
                                            </td>
                                            <td align="center">
                                                <input type="number" 
                                                       data-id="<?php echo $row['id']; ?>" 
                                                       value="<?php echo $row['soluong']; ?>" 
                                                       class="qty-input js-qty" 
                                                       min="1"
                                                       oninput="markAsDirty()"
                                                       <?php echo $is_locked ? 'disabled style="background:#eee; border:none;"' : ''; ?>>
                                            </td>
                                            <td align="right" style="font-weight:600; color:#333;">
                                                <span class="js-item-total"><?php echo number_format($row['thanhtien'], 0, ',', '.'); ?></span>
                                            </td>
                                            <td align="center">
                                                <?php if(!$is_locked && $can_delete): ?>
                                                    <button type="button" onclick="deleteItem(<?php echo $row['id']; ?>)" class="btn-del-mini">&times;</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="order-footer">
                        <?php
                            $tong_tien_mon = $tai_chinh_init['tong_tien_mon'];
                            $da_coc = $tai_chinh_init['da_coc'];
                            $con_lai = $tai_chinh_init['can_thanh_toan'];
                        ?>
                        <input type="hidden" id="id_hd" value="<?php echo $id_hd; ?>">
                        <input type="hidden" id="deposit_val" value="<?php echo $da_coc; ?>">

                        <div class="pay-row total-mon">
                            <span>Tổng tiền món:</span>
                            <span id="lbl-total-mon"><?php echo number_format($tong_tien_mon, 0, ',', '.'); ?></span>
                        </div>
                        <div class="pay-row deposit">
                            <span>Đã cọc / TT:</span>
                            <span>- <?php echo number_format($da_coc, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="pay-final">
                            <span class="label" id="lbl-final-text"><?php echo ($con_lai >= 0) ? 'CẦN TRẢ:' : 'TIỀN THỪA:'; ?></span>
                            <span class="value" id="lbl-remain" style="color: <?php echo ($con_lai <= 0) ? '#27ae60' : '#d63031'; ?>;">
                                <?php echo ($con_lai >= 0) ? number_format($con_lai, 0, ',', '.') : ('+' . number_format(abs($con_lai), 0, ',', '.')); ?> <small>VNĐ</small>
                            </span>
                        </div>

                        <?php if (!$is_locked): ?>
                        <div class="btn-group">
                            <button type="button" onclick="saveOrder()" class="btn-action btn-update">
                                <i class="fa fa-save"></i> LƯU ĐƠN
                            </button>
                            <button type="button" onclick="openPaymentModal()" class="btn-action btn-pay">
                                <i class="fa fa-check-circle"></i> THANH TOÁN
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="btn-group">
                            <div style="width:100%; text-align:center; padding:15px; background:#d4edda; color:#155724; border-radius:8px; font-weight:bold; border:1px solid #c3e6cb;">
                                <i class="fa fa-check-circle"></i> GIAO DỊCH ĐÃ HOÀN THÀNH
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="paymentModal" class="modal-payment-overlay">
    <div class="modal-payment-box">
        <div class="modal-title">Xác nhận thanh toán</div>
        
        <div>Khách cần trả:</div>
        <span class="modal-amount" id="modal-pay-amount">0 VNĐ</span>
        <div class="modal-note" id="modal-pay-note"></div>

        <div class="payment-options">
            <button class="btn-method cash" onclick="processPayment('cash')">
                <span><i class="fa fa-money"></i> Tiền mặt</span>
                <i class="fa fa-chevron-right" style="font-size:12px; color:#ccc;"></i>
            </button>
            <button class="btn-method vnpay" onclick="processPayment('vnpay')">
                <span><i class="fa fa-qrcode"></i> VNPay / Chuyển khoản</span>
                <i class="fa fa-chevron-right" style="font-size:12px; color:#ccc;"></i>
            </button>
        </div>

        <button class="btn-close-modal" onclick="closePaymentModal()">Quay lại / Đóng</button>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script>
    const id_hd = document.getElementById('id_hd').value;
    let isDirty = false;

    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    function markAsDirty() {
        isDirty = true;
    }

    function goBackCheck() {
        if (isDirty) {
            if (confirm("Bạn có thay đổi số lượng chưa lưu. Bạn có chắc muốn thoát không?\n(Dữ liệu chưa lưu sẽ bị mất)")) {
                window.location.href = 'booking_list.php';
            }
        } else {
            window.location.href = 'booking_list.php';
        }
    }

    window.onbeforeunload = function() {
        if (isDirty) {
            return "Bạn có thay đổi chưa lưu.";
        }
    };

    function showLoading() { document.getElementById('loading-overlay').style.display = 'flex'; }
    function hideLoading() { document.getElementById('loading-overlay').style.display = 'none'; }

    async function sendAjax(data) {
        showLoading();
        let formData = new FormData();
        formData.append('ajax_action', data.action);
        if(data.id_mon) formData.append('id_mon', data.id_mon);
        if(data.id_chitiet) formData.append('id_chitiet', data.id_chitiet);
        if(data.qty_data) formData.append('qty_data', JSON.stringify(data.qty_data));
        
        if(data.method) formData.append('method', data.method);

        try {
            let response = await fetch('booking_edit.php?id=' + id_hd, {
                method: 'POST',
                body: formData
            });
            let text = await response.text();
            try {
                var result = JSON.parse(text);
            } catch (e) {
                console.error("Server Raw Response:", text);
                alert("Lỗi hệ thống! " + text); // In text lỗi ra để xem
                hideLoading();
                return;
            }
            hideLoading();
            return result;
        } catch (error) {
            hideLoading();
            alert('Lỗi kết nối mạng!');
            console.error(error);
        }
    }

    function updateUI(res) {
        if(res && res.status === 'success') {
            document.getElementById('cart-body').innerHTML = res.html;
            
            let total = parseFloat(res.tong_tien_mon);
            let deposit = parseFloat(res.da_coc);
            let remain = parseFloat(res.can_thanh_toan);

            document.getElementById('lbl-total-mon').innerText = formatMoney(total);
            document.getElementById('deposit_val').value = deposit;

            let lblRemain = document.getElementById('lbl-remain');
            let lblText = document.getElementById('lbl-final-text');
            
            if (remain >= 0) {
                lblRemain.innerHTML = formatMoney(remain) + ' <small>VNĐ</small>';
                lblRemain.style.color = '#d63031'; 
                lblText.innerText = "CẦN TRẢ:";
            } else {
                let refund = Math.abs(remain);
                lblRemain.innerHTML = '+' + formatMoney(refund) + ' <small>VNĐ</small>';
                lblRemain.style.color = '#27ae60'; 
                lblText.innerText = "TIỀN THỪA:";
            }
            isDirty = false;
        } else if (res && res.status === 'error') {
            alert(res.msg); // Hiện thông báo nếu Backend chặn sửa
        }
    }

    async function addToOrder(id_mon) {
        let res = await sendAjax({ action: 'add', id_mon: id_mon });
        updateUI(res);
    }

    async function deleteItem(id_chitiet) {
        let res = await sendAjax({ action: 'delete', id_chitiet: id_chitiet });
        updateUI(res);
    }

    async function saveOrder() {
        let qtyData = [];
        document.querySelectorAll('.js-qty').forEach(input => {
            qtyData.push({ id: input.getAttribute('data-id'), val: input.value });
        });

        let res = await sendAjax({ action: 'update_all', qty_data: qtyData });
        if (res && res.status === 'success') {
            updateUI(res);   
            isDirty = false; 
            alert('Đã lưu thành công!');
            window.location.href = 'booking_list.php'; 
        }
    }

    function openPaymentModal() {
        let totalText = document.getElementById('lbl-total-mon').innerText.replace(/\./g, '');
        let total = parseFloat(totalText) || 0;
        let deposit = parseFloat(document.getElementById('deposit_val').value) || 0;
        let remain = total - deposit;

        let modalAmount = document.getElementById('modal-pay-amount');
        let modalNote = document.getElementById('modal-pay-note');

        if (remain >= 0) {
            modalAmount.innerText = formatMoney(remain) + ' VNĐ';
            modalAmount.style.color = '#d63031';
            modalNote.innerText = "Vui lòng thu đủ tiền của khách.";
        } else {
            modalAmount.innerText = "HOÀN: " + formatMoney(Math.abs(remain)) + ' VNĐ';
            modalAmount.style.color = '#27ae60';
            modalNote.innerText = "Lưu ý: Cần trả lại tiền thừa cho khách.";
        }
        document.getElementById('paymentModal').style.display = 'flex';
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    async function processPayment(method) {
        let qtyData = [];
        document.querySelectorAll('.js-qty').forEach(input => {
            qtyData.push({ id: input.getAttribute('data-id'), val: input.value });
        });

        if (method === 'cash') {
            if(!confirm('Xác nhận đã nhận đủ tiền mặt và hoàn tất đơn?')) return;
            let res = await sendAjax({ 
                action: 'pay', 
                method: 'cash',  
                qty_data: qtyData 
            });
            if(res && res.status === 'completed') {
                isDirty = false;
                alert('Thanh toán tiền mặt thành công!');
                window.location.href = 'booking_list.php';
            }
        } else if (method === 'vnpay') {
            let res = await sendAjax({ action: 'update_all', qty_data: qtyData });
            if (res && res.status === 'success') {
                updateUI(res); 
                let remainRaw = parseFloat(res.tong_tien_mon) - parseFloat(res.da_coc);
                if(remainRaw <= 0) {
                    alert("Đơn hàng đã đủ cọc hoặc thừa tiền. Không thể tạo giao dịch VNPay.");
                    return;
                }
                window.location.href = `vnpay_create_payment.php?id_hd=${id_hd}&amount=${remainRaw}`;
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        // Kiểm tra nếu trên URL có chữ open_pay
        if (urlParams.has('open_pay')) {
            // Đợi 0.5 giây cho dữ liệu load xong rồi tự bấm nút Thanh toán
            setTimeout(() => {
                if(typeof openPaymentModal === 'function') {
                    openPaymentModal();
                }
            }, 500); 
        }
    });
</script>