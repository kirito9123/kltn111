<?php
/* ================ ADMIN: MÀN HÌNH BẾP (FINAL VERSION) ================ */
require_once '../classes/nhanvienbep.php';
$bep = new nhanvienbep();

/* ==== XỬ LÝ AJAX CẬP NHẬT TRẠNG THÁI (DONE) ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'capnhat_bep') {
    $id = (int)($_POST['id'] ?? 0);
    // Gọi hàm hoan_thanh_don trong class mới đã sửa logic trừ kho món mới
    $ok = ($id > 0) ? $bep->hoan_thanh_don($id) : false;
    echo $ok ? 'success' : 'error';
    exit;
}

/* ==== XỬ LÝ AJAX KIỂM TRA ĐƠN HỦY (POLLING) ==== */
require_once 'inc/header.php';
require_once 'inc/sidebar.php';
require_once '../helpers/format.php';

$fm = new Format();

// 1. LẤY DỮ LIỆU
// [SỬA LẠI] Cập nhật các giá trị lọc mới
$view = $_GET['view'] ?? 'cho_che_bien';
$date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($view == 'bydate') {
    $rsOrders = $bep->get_danh_sach_don('lich_su', $date);
} elseif ($view == 'hom_nay') { // Đã đổi tên từ 'today_all' thành 'hom_nay'
    $rsOrders = $bep->get_danh_sach_don('hom_nay');
} elseif ($view == 'dat_truoc') {
    $rsOrders = $bep->get_danh_sach_don('dat_truoc');
} elseif ($view == 'don_huy') {
    $rsOrders = $bep->get_danh_sach_don('don_huy');
} else {
    $rsOrders = $bep->get_danh_sach_don('cho_che_bien');
}

// 2. CHUẨN BỊ DỮ LIỆU
$orders = [];
if ($rsOrders) {
    while ($row = $rsOrders->fetch_assoc()) {
        $id = $row['id'];
        $deadline = $bep->tinh_deadline($row['dates'], $row['tg']);
        $is_cancelled = ($row['payment_status'] == 'cancelled'); // Cờ kiểm tra đơn hủy

        // --- LẤY MÓN ĂN ---
        $items = [];
        $rsItems = $bep->get_chi_tiet_don($id);

        // Cờ kiểm tra xem có món mới nào không (Để đổi màu nút Hoàn thành)
        $has_new_items = false;

        if ($rsItems) {
            while ($r = $rsItems->fetch_assoc()) {
                $items[] = [
                    'mon'       => htmlspecialchars($r['name_mon']),
                    'sl'        => $r['soluong'],
                    'thanhtien' => $r['thanhtien'],
                    'trangthai' => $r['trangthai'] // Lấy trạng thái từ DB
                ];
                // Chỉ kiểm tra món mới nếu đơn CHƯA bị hủy
                if ($r['trangthai'] == 0 && !$is_cancelled) $has_new_items = true;
            }
        }

        // Tính tổng tiền (Logic cũ của bạn)
        $db_price = (float)($row['thanhtien'] ?? 0);
        $total_calc = 0;
        foreach ($items as $it) $total_calc += $it['thanhtien'];
        $final_total = ($db_price > 0) ? $db_price : $total_calc;

        $tenban = trim($row['tenKH']);
        if ($tenban == '') $tenban = 'Khách lẻ';

        $orders[] = [
            'id'            => $id,
            'tenban'        => $tenban,
            'loaiphong'     => $row['loaiphong'],
            'phong'         => $row['phong'],
            'tg'            => $row['tg'],
            'dates'         => $row['dates'],
            'ghichu'        => $row['ghichu'] ?? '',
            'tong_tien'     => $final_total,
            'status'        => $row['status'],
            'deadline'      => $deadline,
            'items'         => $items,
            'has_new_items' => $has_new_items,
            'is_cancelled'  => $is_cancelled, // THÊM CỜ HỦY
        ];
    }
}

function vnd($n)
{
    return number_format((float)$n, 0, ',', '.') . ' đ';
}
?>

<style>
    /* === FIX LAYOUT === */
    .container_12 {
        display: block !important;
        width: 100% !important;
        overflow: hidden !important;
    }

    .grid_2 {
        float: left !important;
        width: 230px !important;
        margin: 0 !important;
    }

    .grid_10 {
        float: left !important;
        width: calc(100% - 230px) !important;
        margin: 0 !important;
        padding: 20px !important;
        box-sizing: border-box !important;
        background: #f4f6f9;
        min-height: 100vh;
    }

    .grid_10 .clear {
        display: none;
    }

    /* === GIAO DIỆN BẾP === */
    .kitchen-title {
        margin-bottom: 20px;
        font-size: 22px;
        font-weight: 800;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .order-board {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .order-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1px solid #eee;
        transition: transform 0.2s;
        position: relative;
        /* Để đặt badge hủy */
    }

    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
    }

    /* [MỚI] Nếu có món mới thì viền đỏ nổi bật */
    .order-card.has-new {
        border: 2px solid #e74c3c;
        box-shadow: 0 0 15px rgba(231, 76, 60, 0.1);
    }

    /* [MỚI] Style cho đơn hủy */
    .order-card.is-cancelled {
        opacity: 0.7;
        border: 2px dashed #dc3545;
        background: #ffebee;
    }

    /* [MỚI] Badge Hủy */
    .cancel-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        font-weight: 800;
        font-size: 14px;
        border-radius: 6px;
        z-index: 10;
        transform: rotate(5deg);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .order-card__header {
        padding: 12px 15px;
        background: #fff;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
    }

    .order-id-badge {
        font-size: 13px;
        font-weight: 800;
        color: #fff;
        background: #e74c3c;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 4px;
    }

    .table-name {
        font-size: 16px;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1.3;
    }

    .room-info {
        font-size: 12px;
        color: #7f8c8d;
    }

    .order-time {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        display: block;
        text-align: right;
    }

    .order-date {
        font-size: 11px;
        color: #999;
        display: block;
        text-align: right;
    }

    .order-countdown {
        padding: 10px;
        text-align: center;
        font-size: 15px;
        font-weight: 700;
        border-bottom: 1px solid #eee;
        letter-spacing: 0.5px;
    }

    .cd-green {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .cd-yellow {
        background: #fff9c4;
        color: #fbc02d;
    }

    .cd-orange {
        background: #fff3e0;
        color: #ef6c00;
    }

    .cd-red {
        background: #ffebee;
        color: #c62828;
        animation: blink 1s infinite;
    }

    @keyframes blink {
        50% {
            opacity: 0.6;
        }
    }

    /* Đơn hủy không cần countdown */
    .is-cancelled .order-countdown {
        background: #f8d7da !important;
        color: #721c24 !important;
    }

    .order-card__body {
        padding: 0;
        flex: 1;
        min-height: 100px;
    }

    .item-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 15px;
        border-bottom: 1px dashed #f1f1f1;
        align-items: center;
    }

    /* [MỚI] Style cho món Mới và món Cũ */
    .order-item.item-new {
        background: #fff8e1;
    }

    .order-item.item-new .item-name {
        color: #d35400;
        font-weight: 700;
        font-size: 15px;
    }

    .order-item.item-old {
        opacity: 0.6;
        background: #f8f9fa;
    }

    .order-item.item-old .item-name {
        text-decoration: line-through;
        color: #7f8c8d;
    }

    .badge-new {
        background: #e74c3c;
        color: white;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 10px;
        margin-left: 5px;
        vertical-align: middle;
        text-decoration: none !important;
        display: inline-block;
    }

    .item-qty {
        font-weight: 800;
        color: #e74c3c;
        font-size: 16px;
        background: #ffe6e6;
        padding: 2px 8px;
        border-radius: 4px;
    }

    .item-old .item-qty {
        background: #eee;
        color: #888;
    }

    .note-box {
        background: #fff3cd;
        color: #856404;
        padding: 8px 15px;
        font-size: 13px;
        font-style: italic;
        border-bottom: 1px solid #f1f1f1;
    }

    .order-card__footer {
        padding: 15px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-weight: 700;
        color: #333;
        font-size: 15px;
    }

    .btn-done {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 6px;
        background: #27ae60;
        color: white;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-done:hover {
        background: #219150;
    }

    /* Nút chỉ active khi có món mới */
    .btn-done.only-view {
        background: #95a5a6;
        cursor: default;
    }

    .status-done {
        text-align: center;
        color: #27ae60;
        font-weight: 700;
        display: block;
        padding: 8px;
        border: 2px solid #27ae60;
        border-radius: 6px;
    }

    /* Đơn hủy: nút hành động bị thay thế */
    .is-cancelled .order-card__footer .btn-done,
    .is-cancelled .order-card__footer .status-done,
    .is-cancelled .order-card__footer .only-view {
        background: #95a5a6 !important;
        cursor: not-allowed !important;
        font-style: italic;
        color: #fff;
    }

    /* Bộ lọc */
    .filter-bar {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
        background: #fff;
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        flex-wrap: wrap;
    }

    .btn-filter {
        text-decoration: none;
        color: #555;
        padding: 8px 15px;
        border-radius: 20px;
        border: 1px solid #ddd;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s;
    }

    .btn-filter:hover {
        background: #f1f1f1;
    }

    .btn-filter.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }

    .input-date {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: transparent; border: none; box-shadow: none;">

        <h2 class="kitchen-title">
            <span style="font-size: 28px;">👨‍🍳</span> MÀN HÌNH BẾP - <?php echo date('d/m/Y'); ?>
        </h2>

        <div class="filter-bar">
            <a href="?view=cho_che_bien" class="btn-filter <?php echo ($view == 'cho_che_bien') ? 'active' : ''; ?>">
                🔥 Đang chờ làm
            </a>
            <a href="?view=dat_truoc" class="btn-filter <?php echo ($view == 'dat_truoc') ? 'active' : ''; ?>">
                📅 Đặt trước
            </a>
            <a href="?view=don_huy" class="btn-filter <?php echo ($view == 'don_huy') ? 'active' : ''; ?>" style="background:#dc3545; color:white; border-color:#dc3545;">
                ❌ Đơn đã hủy
            </a>
            <a href="?view=hom_nay" class="btn-filter <?php echo ($view == 'hom_nay') ? 'active' : ''; ?>">
                📋 Tất cả hôm nay
            </a>

            <form method="GET" style="display:flex; align-items:center; gap:8px; margin-left:auto;">
                <input type="hidden" name="view" value="bydate">
                <span style="font-size:13px; font-weight:600; color:#555;">Xem ngày:</span>
                <input type="date" name="date" class="input-date" value="<?php echo $date; ?>">
                <button type="submit" class="btn-filter" style="background:#6c757d; color:white; border:none;">Lọc</button>
            </form>
        </div>

        <div class="block" style="padding:0;">
            <?php if (empty($orders)): ?>
                <div style="text-align:center; padding:60px; background:#fff; border-radius:8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h3 style="color:#999; margin:0;">Hiện tại bếp đang rảnh, chưa có đơn nào trong mục này!</h3>
                </div>
            <?php else: ?>
                <div class="order-board">
                    <?php foreach ($orders as $o): ?>
                        <div class="order-card 
                            <?php echo $o['is_cancelled'] ? 'is-cancelled' : ''; ?>
                            <?php echo ($o['has_new_items'] && !$o['is_cancelled']) ? 'has-new' : ''; ?>
                        ">

                            <?php if ($o['is_cancelled']): ?>
                                <div class="cancel-badge">ĐÃ HỦY</div>
                            <?php endif; ?>

                            <div class="order-card__header">
                                <div class="header-left">
                                    <span class="order-id-badge">Đơn #<?php echo $o['id']; ?></span>
                                    <div class="table-name"><?php echo $o['tenban']; ?></div>
                                    <span class="room-info"><?php echo ($o['phong'] ? $o['phong'] : 'Sảnh chung'); ?></span>
                                </div>
                                <div class="header-right">
                                    <span class="order-time"><?php echo date('H:i', strtotime($o['tg'])); ?></span>
                                    <span class="order-date"><?php echo date('d/m', strtotime($o['dates'])); ?></span>
                                </div>
                            </div>

                            <?php if ($o['is_cancelled']): ?>
                                <div class="order-countdown" style="background:#f8d7da; color:#721c24;">
                                    ❌ ĐƠN ĐÃ HỦY - NGƯNG LÀM
                                </div>
                            <?php elseif ($o['status'] == 0): ?>
                                <div class="order-countdown" data-deadline="<?php echo $o['deadline']; ?>">
                                    <i class="fa fa-clock-o"></i> Đang tải...
                                </div>
                            <?php else: ?>
                                <div class="order-countdown" style="background:#e8f5e9; color:#2e7d32;">
                                    <i class="fa fa-check"></i> Hoàn tất
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($o['ghichu'])): ?>
                                <div class="note-box"><i class="fa fa-sticky-note-o"></i> <?php echo $o['ghichu']; ?></div>
                            <?php endif; ?>

                            <div class="order-card__body">
                                <?php if (empty($o['items'])): ?>
                                    <div style="padding:20px; text-align:center; color:#999; font-style:italic;">(Chưa có món)</div>
                                <?php else: ?>
                                    <ul class="item-list">
                                        <?php foreach ($o['items'] as $it):
                                            // [MỚI] Kiểm tra trạng thái để gán class
                                            $is_new = ($it['trangthai'] == 0);
                                            $cls_item = $is_new ? 'item-new' : 'item-old';
                                        ?>
                                            <li class="order-item <?php echo $cls_item; ?>">
                                                <span class="item-name">
                                                    <?php echo $it['mon']; ?>
                                                    <?php if ($is_new && !$o['is_cancelled']): ?><span class="badge-new">MỚI</span><?php endif; ?>
                                                    <?php if ($is_new && $o['is_cancelled']): ?><span class="badge-new" style="background:#f39c12;">CHƯA LÀM</span><?php endif; ?>
                                                </span>
                                                <span class="item-qty">x<?php echo $it['sl']; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="order-card__footer">
                                <div class="total-row">
                                    <span>Tổng cộng:</span>
                                    <span><?php echo vnd($o['tong_tien']); ?></span>
                                </div>

                                <?php if ($o['is_cancelled']): ?>
                                    <span class="status-done" style="background:#dc3545; color:white; border-color:#dc3545;">ĐƠN ĐÃ HỦY</span>
                                <?php elseif ($o['has_new_items']): ?>
                                    <button class="btn-done" data-id="<?php echo $o['id']; ?>">
                                        <i class="fa fa-check-circle"></i> XONG CÁC MÓN MỚI
                                    </button>
                                <?php elseif ($o['status'] == 0): ?>
                                    <button class="btn-done only-view" style="opacity:0.6; cursor:default;">
                                        <i class="fa fa-clock-o"></i> Đã ra hết món
                                    </button>
                                <?php else: ?>
                                    <span class="status-done">ĐÃ XONG</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Hàm cập nhật thời gian còn lại (giữ nguyên)
    function updateCountdown() {
        const now = new Date().getTime();
        document.querySelectorAll('.order-countdown').forEach(el => {
            const deadlineStr = el.getAttribute('data-deadline');
            if (!deadlineStr) return;

            const deadline = new Date(deadlineStr).getTime();
            const diff = deadline - now;

            // Không update countdown nếu đơn đã hủy (đã có cảnh báo)
            if (el.closest('.order-card').classList.contains('is-cancelled')) return;

            let totalSec = Math.floor(Math.abs(diff) / 1000);
            let mins = Math.floor(totalSec / 60);
            let secs = totalSec % 60;

            let text = "",
                cls = "";

            if (diff > 0) {
                text = "⏳ Còn: " + mins + "p " + secs + "s";
                if (mins > 10) cls = "cd-green";
                else if (mins > 5) cls = "cd-yellow";
                else cls = "cd-orange";
            } else {
                text = "🔥 TRỄ: " + mins + "p " + secs + "s";
                cls = "cd-red";
            }
            el.innerHTML = text;
            el.className = "order-countdown " + cls;
        });
    }
    setInterval(updateCountdown, 1000);
    updateCountdown();

    // ==========================================================
    // [CẬP NHẬT] HÀM KIỂM TRA ĐƠN HỦY TỰ ĐỘNG (AJAX POLLING)
    // ==========================================================
    function checkCancelledOrders() {
        // 1. Lấy tất cả ID đơn hàng đang hiển thị
        const current_ids = Array.from(document.querySelectorAll('.order-card')).map(card => {
            // Chỉ kiểm tra các đơn chưa bị hủy trên giao diện (để tránh popup liên tục)
            if (!card.classList.contains('is-cancelled')) {
                // Phải tìm nút có data-id, có thể là btn-done hoặc status-done
                const idElement = card.querySelector('.btn-done') || card.querySelector('.status-done');
                if (idElement && idElement.getAttribute('data-id')) {
                    return parseInt(idElement.getAttribute('data-id'));
                }
            }
            return null;
        }).filter(id => id); // Lọc các ID null

        if (current_ids.length === 0) return;

        let formData = new FormData();
        formData.append('action', 'check_cancelled');
        formData.append('order_ids', JSON.stringify(current_ids));

        fetch("admin_orders.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.cancelled_ids && data.cancelled_ids.length > 0) {
                    // 2. Phát hiện đơn hàng đã bị hủy
                    const cancelled_list = data.cancelled_ids.join(', #');

                    // Hiển thị Popup Cảnh báo
                    alert("🚨 CẢNH BÁO KHẨN CẤP!\n\nCÁC ĐƠN HÀNG ĐÃ BỊ HỦY:\n#" + cancelled_list + "\n\nVUI LÒNG NGƯNG CHẾ BIẾN NGAY LẬP TỨC!");

                    // Sau khi bếp xác nhận, tải lại trang để cập nhật giao diện
                    location.reload();
                }
            })
            .catch(err => console.error("Lỗi Polling Server:", err));
    }

    // Đặt bộ đếm thời gian: 10 giây
    setInterval(checkCancelledOrders, 10000);

    // ==========================================================
    // HÀM XỬ LÝ NÚT XONG CÁC MÓN MỚI (giữ nguyên)
    // ==========================================================
    document.querySelectorAll(".btn-done").forEach(btn => {
        btn.addEventListener("click", function() {
            // [MỚI] Chặn click nếu là nút chỉ xem hoặc đơn hủy
            if (this.classList.contains('only-view') || this.closest('.order-card').classList.contains('is-cancelled')) return;

            const id = this.getAttribute("data-id");
            if (!confirm("Xác nhận Bếp đã làm xong các món MỚI của đơn #" + id + "?")) return;

            fetch("admin_orders.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "action=capnhat_bep&id=" + encodeURIComponent(id)
                })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "success") {
                        const card = this.closest('.order-card');
                        card.style.opacity = "0.5";
                        // Tải lại ngay sau khi hoàn thành để cập nhật trạng thái
                        setTimeout(() => location.reload(), 200);
                    } else {
                        alert("Lỗi cập nhật hoặc đơn đã xong!");
                    }
                })
                .catch(err => alert("Lỗi kết nối!"));
        });
    });
</script>

<?php require_once 'inc/footer.php'; ?>