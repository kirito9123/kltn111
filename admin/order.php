<?php
// order.php - Đã sửa lỗi Session và tối ưu giao diện POS

// Bắt đầu Output Buffering để xử lý redirect sau khi đã có HTML (cần thiết nếu header.php output HTML)
if (session_status() == PHP_SESSION_NONE) {
    @ob_start();
}

// 1. DATABASE & CONFIG
require_once __DIR__ . '/../lib/database.php';
$db = new Database();
if (isset($db->link) && $db->link instanceof mysqli) {
    @$db->link->set_charset('utf8mb4');
}

// 2. HELPERS AJAX (Dùng cho Combo JSON)
if (!function_exists('ajax_start_headers')) {
    function ajax_start_headers($type = 'json')
    {
        @ini_set('display_errors', '0');
        @error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header($type === 'json'
            ? 'Content-Type: application/json; charset=utf-8'
            : 'Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}
if (!function_exists('ajax_json')) {
    function ajax_json($arr)
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

require_once __DIR__ . '/../classes/orderservice.php';
$svc = new orderservice($db); // Class tên orderservice (chữ thường theo code gốc)

if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Asia/Ho_Chi_Minh');
}

// --- 3. XỬ LÝ AJAX (API lấy món trong combo) ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'combo_items') {
    ajax_start_headers('json');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) ajax_json(['ok' => false, 'msg' => 'Thiếu id', 'items' => []]);

    try {
        $sql = "SELECT mo.id_mon, mo.name_mon, mo.gia_mon, mct.so_luong AS sl
                FROM menu_chitiet mct
                INNER JOIN monan mo ON mo.id_mon = mct.id_mon
                WHERE mct.ma_menu = {$id} AND mo.xoa = 0
                ORDER BY mct.id ASC";
        $rs = $db->select($sql);
        $items = [];
        if ($rs) while ($r = $rs->fetch_assoc()) {
            $items[] = [
                'id_mon'   => (int)$r['id_mon'],
                'name_mon' => (string)$r['name_mon'],
                'gia_mon'  => (float)$r['gia_mon'],
                'sl'       => max(1, (int)$r['sl'])
            ];
        }
        ajax_json(['ok' => true, 'items' => $items]);
    } catch (Throwable $e) {
        ajax_json(['ok' => false, 'msg' => 'Lỗi server: ' . $e->getMessage(), 'items' => []]);
    }
}

// ==========================================
// 4. INCLUDE GIAO DIỆN CHÍNH (HEADER LOADS SESSION)
// ==========================================
include 'inc/header.php';
include 'inc/sidebar.php';

// --- LOGIC PHP SAU KHI LOAD HEADER (Đã có Session) ---

// A. Xử lý Bàn
$ban_ids = Session::get('ban_ids') ?? [];
if (isset($_GET['ban_ids'])) {
    $tokens = array_values(array_filter(array_map('trim', explode(',', $_GET['ban_ids']))));
    $idNums = [];
    $names = [];
    foreach ($tokens as $tk) {
        if ($tk === '') continue;
        if (ctype_digit($tk)) $idNums[] = (int)$tk;
        else $names[] = $tk;
    }
    if (!empty($names)) {
        $escNames = array_map(function ($s) use ($db) {
            return "'" . $db->link->real_escape_string($s) . "'";
        }, $names);
        $sql = "SELECT id_ban FROM ban WHERE tenban IN (" . implode(',', $escNames) . ")";
        $rs  = $db->select($sql);
        if ($rs) while ($r = $rs->fetch_assoc()) $idNums[] = (int)$r['id_ban'];
    }
    $ban_ids = array_values(array_unique(array_filter(array_map('intval', $idNums))));
    Session::set('ban_ids', $ban_ids);
}
if (!is_array($ban_ids)) $ban_ids = [];

// Lấy thông tin bàn hiển thị
$banList = [];
$ten_ban_str = "Chưa chọn bàn";
if (!empty($ban_ids)) {
    $in = implode(',', array_map('intval', $ban_ids));
    $rs = $db->select("SELECT b.id_ban, b.tenban, b.trangthai FROM ban b WHERE b.id_ban IN ($in) ORDER BY b.tenban ASC");
    $names_arr = [];
    if ($rs) while ($r = $rs->fetch_assoc()) {
        $banList[] = $r;
        $names_arr[] = $r['tenban'];
    }
    if (!empty($names_arr)) $ten_ban_str = implode(', ', $names_arr);
}

// B. Xử lý Submit Form (Lưu đơn)
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parsed = $svc->parseOrderFromPost($_POST);
    $items  = $parsed['items'] ?? [];

    if (empty($items)) {
        echo "<script>alert('Chưa chọn món nào!'); window.location.href='order.php';</script>";
        exit;
    }

    $today = date('Y-m-d');
    $now   = date('H:i');
    $ban_labels = [];
    foreach ($banList as $b) {
        if (!empty($b['tenban'])) $ban_labels[] = $b['tenban'];
    }

    $meta = [
        'loaiphong' => $_GET['loaiphong'] ?? (Session::get('loaiphong') ?? ''),
        'phong'     => $_GET['phong']     ?? (Session::get('phong') ?? ''),
        'vitri_id'  => (int)($_GET['vitri_id'] ?? (Session::get('vitri_id') ?? 0)),
    ];

    $order = [
        'tenKH'      => 'Khách ăn tại quán',
        'so_user'    => '',
        'dates'      => $today,
        'tg'         => $now,
        'note'       => '',
        'ban_ids'    => $ban_ids,
        'ban_labels' => $ban_labels,
        'items'      => $items,
        'meta'       => $meta,
        'status'     => 0,
    ];

    $res = $svc->createContractWithDetails($order);

    if (!empty($res['ok'])) {
        $hopdong_id = (int)$res['id'];
        $msg = 'Đã import hợp đồng #' . $hopdong_id . ' thành công!';
        echo "<script>alert(" . json_encode($msg) . "); window.location.href = 'danhsachdatban.php';</script>";
    } else {
        $msg = $res['msg'] ?? 'Ghi đơn thất bại.';
        echo "<script>alert(" . json_encode($msg) . "); window.location.href = 'order.php';</script>";
    }
}

// C. Lấy Dữ liệu Món & Combo
$id_loai = isset($_GET['id_loai']) ? (int)$_GET['id_loai'] : 0;
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE xoa = 0";
if ($id_loai > 0) $where .= " AND id_loai = " . (int)$id_loai;
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = $db->link->real_escape_string($_GET['q']);
    $where .= " AND name_mon LIKE '%$q%'";
}

// Phân trang
$total_rs  = $db->select("SELECT COUNT(*) AS cnt FROM monan {$where}");
$total_row = $total_rs ? $total_rs->fetch_assoc() : ['cnt' => 0];
$total     = (int)$total_row['cnt'];
$total_pages = max(1, (int)ceil($total / $limit));

// Lấy món - SỬA Ở ĐÂY: ORDER BY id_mon ASC
$queryFood = "SELECT * FROM monan {$where} ORDER BY id_mon ASC LIMIT {$limit} OFFSET {$offset}";
$ds_mon = $db->select($queryFood);

// Lấy loại món
$loai_rs = $db->select("SELECT id_loai, name_loai FROM loai_mon WHERE xoa = 0 ORDER BY name_loai ASC");

// Lấy Combo
$combos = [];
if (!isset($_GET['q']) && $id_loai == 0) {
    $combo_rs = $db->select("SELECT id_menu, ten_menu, ghi_chu, hinhanh FROM menu WHERE trang_thai = 0 ORDER BY id_menu ASC");
    if ($combo_rs) while ($c = $combo_rs->fetch_assoc()) $combos[] = $c;
}

function page_url($newPage, $extra = [])
{
    $params = $_GET;
    $params['page'] = max(1, (int)$newPage);
    foreach ($extra as $k => $v) {
        if ($v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    return '?' . http_build_query($params);
}
?>

<style>
    /* CSS đã tối ưu cho layout 2 cột POS */
    .grid_10 {
        width: 100% !important;
        margin: 0 !important;
        max-width: 100%;
    }

    .pos-wrap {
        display: flex;
        gap: 15px;
        height: calc(100vh - 60px);
        background: #f4f6f9;
        padding: 10px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
    }

    /* CỘT TRÁI: MENU */
    .pos-left {
        flex: 7;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .filter-row-search,
    .filter-row-cat {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .filter-row-cat {
        margin-top: 10px;
        padding-top: 5px;
        /* Giảm padding cho hàng category */
        padding-bottom: 5px;
    }

    .search-input {
        flex: 1;
        /* Cho input chiếm hết khoảng trống còn lại */
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
    }

    .cat-list {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        /* Giữ khả năng cuộn ngang */
        max-width: 100%;
        padding: 5px 0;
        /* Cho khoảng trống cuộn */
    }

    .cat-item {
        white-space: nowrap;
        padding: 6px 12px;
        border-radius: 15px;
        background: #f1f1f1;
        color: #333;
        text-decoration: none;
        font-size: 13px;
        border: 1px solid transparent;
    }

    .cat-item:hover,
    .cat-item.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    .menu-scroll {
        flex: 1;
        overflow-y: auto;
        padding-right: 5px;
        padding-bottom: 50px;
    }

    .section-head {
        font-weight: bold;
        margin: 15px 0 10px;
        border-left: 4px solid #ffc107;
        padding-left: 10px;
        color: #444;
        text-transform: uppercase;
    }

    .pos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }

    .pos-card {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #eee;
        cursor: pointer;
        transition: 0.2s;
        position: relative;
    }

    .pos-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-color: #0d6efd;
    }

    .card-img {
        height: 100px;
        width: 100%;
        object-fit: cover;
        background: #eee;
    }

    .card-info {
        padding: 10px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-name {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 36px;
    }

    .card-price {
        color: #d63384;
        font-weight: bold;
        font-size: 14px;
    }

    .badge-combo {
        position: absolute;
        top: 6px;
        right: 6px;
        background: #ff5722;
        color: #fff;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* CỘT PHẢI: BILL */
    .pos-right {
        flex: 3;
        background: #fff;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        border: 1px solid #ddd;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
    }

    .bill-head {
        padding: 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .bill-body {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        background: #fff;
    }

    .bill-foot {
        padding: 15px;
        border-top: 1px solid #eee;
        background: #fdfdfd;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px dashed #eee;
        font-size: 13px;
    }

    .c-info {
        flex: 1;
        padding-right: 5px;
    }

    .c-name {
        font-weight: 600;
        display: block;
    }

    .c-qty {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f1f1f1;
        border-radius: 15px;
        padding: 2px;
    }

    .btn-qty {
        width: 22px;
        height: 22px;
        border: none;
        background: #fff;
        border-radius: 50%;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .c-total {
        font-weight: bold;
        min-width: 60px;
        text-align: right;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #d63384;
    }

    .btn-pay {
        width: 100%;
        padding: 12px;
        background: #198754;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        text-transform: uppercase;
    }

    .btn-pay:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* Pagination */
    .pos-pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }

    .p-link {
        padding: 6px 12px;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #0d6efd;
        text-decoration: none;
        border-radius: 4px;
        font-size: 13px;
    }

    .p-link.active {
        background: #0d6efd;
        color: #fff;
    }
</style>

<div class="grid_10">
    <div class="pos-wrap">
        <div class="pos-left">
            <form method="GET" style="margin-bottom: 15px;">
                <div class="filter-row-search">
                    <input type="text" name="q" class="search-input" placeholder="Tìm tên món..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:20px; padding:8px 20px;">Tìm</button>
                </div>

                <div class="filter-row-cat">
                    <div class="cat-list">
                        <a href="order.php" class="cat-item <?php echo $id_loai == 0 ? 'active' : ''; ?>">Tất cả</a>
                        <?php if ($loai_rs) while ($l = $loai_rs->fetch_assoc()): ?>
                            <a href="<?php echo page_url(1, ['id_loai' => $l['id_loai']]); ?>" class="cat-item <?php echo $id_loai == $l['id_loai'] ? 'active' : ''; ?>">
                                <?php echo $l['name_loai']; ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </form>

            <div class="menu-scroll">
                <?php if (!empty($combos)): ?>
                    <div class="section-head"><i class="fas fa-star"></i> Combo Tiết Kiệm</div>
                    <div class="pos-grid">
                        <?php foreach ($combos as $c):
                            $img = !empty($c['hinhanh']) ? '../images/combo/' . $c['hinhanh'] : '../images/placeholder.png';
                        ?>
                            <div class="pos-card" onclick="addCombo(<?php echo $c['id_menu']; ?>, '<?php echo addslashes($c['ten_menu']); ?>')">
                                <img src="<?php echo $img; ?>" class="card-img" onerror="this.src='../images/placeholder.png'">
                                <span class="badge-combo">COMBO</span>
                                <div class="card-info">
                                    <div class="card-name"><?php echo $c['ten_menu']; ?></div>
                                    <div style="font-size:11px; color:#666;">Chọn để thêm</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="section-head"><i class="fas fa-utensils"></i> Thực đơn món ăn</div>
                <div class="pos-grid">
                    <?php if ($ds_mon && $ds_mon->num_rows > 0):
                        while ($m = $ds_mon->fetch_assoc()):
                            $img = !empty($m['images']) ? '../images/food/' . $m['images'] : '../images/placeholder.png';
                    ?>
                            <div class="pos-card" onclick="addToCart(<?php echo $m['id_mon']; ?>, '<?php echo addslashes($m['name_mon']); ?>', <?php echo $m['gia_mon']; ?>)">
                                <img src="<?php echo $img; ?>" class="card-img" onerror="this.src='../images/placeholder.png'">
                                <div class="card-info">
                                    <div class="card-name"><?php echo $m['name_mon']; ?></div>
                                    <div class="card-price"><?php echo number_format($m['gia_mon'], 0, ',', '.'); ?>đ</div>
                                </div>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding:20px; color:#666;">Không tìm thấy món nào.</div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pos-pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="<?php echo page_url($i); ?>" class="p-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="pos-right">
            <div class="bill-head">
                <div>
                    <strong>Đơn hàng</strong><br>
                    <small style="font-weight:normal"><?php echo $ten_ban_str; ?></small>
                </div>
                <button type="button" onclick="clearCart()" style="background:none; border:none; color:#dc3545; cursor:pointer; font-size:12px;">Xóa hết</button>
            </div>

            <div class="bill-body" id="cart-container">
                <div style="text-align:center; margin-top:50px; color:#999;">
                    <i class="fas fa-shopping-basket" style="font-size:30px; opacity:0.5; margin-bottom:10px;"></i><br>
                    Chưa chọn món
                </div>
            </div>

            <form method="POST" class="bill-foot" id="orderForm">
                <div id="hidden-inputs"></div>

                <div class="total-row">
                    <span>Tổng cộng:</span>
                    <span id="total-txt">0 đ</span>
                </div>
                <button type="submit" class="btn-pay" id="btn-submit" disabled>Lưu Đơn / Bếp</button>

                <div style="margin-top: 10px; text-align: center;">
                    <a href="danhsachdatban.php" style="width: 95%; padding: 10px; border-radius: 6px; text-decoration: none; display: inline-block; background: #6c757d; color: white; font-weight: bold;">
                        ← Quay lại Danh sách bàn
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script>
    // --- QUẢN LÝ GIỎ HÀNG (JS) ---
    let cart = {};
    const fmt = n => new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(n);

    const CART_KEY = 'pos_cart';

    // ====== LOAD CART TỪ localStorage KHI VÀO TRANG ======
    (function initCartFromStorage() {
        try {
            const raw = localStorage.getItem(CART_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    cart = parsed;
                }
            }
        } catch (e) {
            console.warn('Không đọc được cart từ localStorage:', e);
        }
        // render lần đầu tiên (kể cả cart rỗng)
        renderCart();
    })();

    // 1. Thêm món lẻ
    function addToCart(id, name, price) {
        if (cart[id]) {
            cart[id].qty++;
        } else {
            cart[id] = {
                name: name,
                price: parseFloat(price),
                qty: 1
            };
        }
        renderCart();
    }

    // 2. Thêm combo (AJAX)
    function addCombo(id, name) {
        document.body.style.cursor = 'wait';
        fetch('order.php?ajax=combo_items&id=' + id)
            .then(r => r.json())
            .then(data => {
                document.body.style.cursor = 'default';
                if (data.ok && data.items.length > 0) {
                    data.items.forEach(i => {
                        // Thêm món với số lượng tương ứng trong combo
                        for (let k = 0; k < i.sl; k++) {
                            addToCart(i.id_mon, i.name_mon, i.gia_mon);
                        }
                    });
                    setTimeout(() => {
                        const box = document.getElementById('cart-container');
                        box.scrollTop = box.scrollHeight;
                    }, 100);
                } else {
                    alert('Lỗi: Combo này chưa có món hoặc dữ liệu sai.');
                }
            })
            .catch(e => {
                document.body.style.cursor = 'default';
                console.error(e);
                alert('Lỗi kết nối server.');
            });
    }

    // 3. Tăng/Giảm số lượng
    function updateQty(id, delta) {
        if (cart[id]) {
            cart[id].qty += delta;
            if (cart[id].qty <= 0) delete cart[id];
            renderCart();
        }
    }

    // 4. Xóa hết
    function clearCart() {
        if (confirm('Xóa hết các món đang chọn?')) {
            cart = {};
            try {
                localStorage.removeItem(CART_KEY);
            } catch (e) {}
            renderCart();
        }
    }

    // 5. Render HTML ra giỏ hàng + LƯU VÀO localStorage
    function renderCart() {
        const box = document.getElementById('cart-container');
        const inputs = document.getElementById('hidden-inputs');
        const totalTxt = document.getElementById('total-txt');
        const btn = document.getElementById('btn-submit');

        box.innerHTML = '';
        inputs.innerHTML = '';
        let total = 0;
        let count = 0;

        for (let id in cart) {
            if (!cart.hasOwnProperty(id)) continue;
            count++;
            let item = cart[id];
            let sub = item.price * item.qty;
            total += sub;

            // HTML hiển thị item
            box.innerHTML += `
                <div class="cart-item">
                    <div class="c-info">
                        <span class="c-name">${item.name}</span>
                        <span class="c-price">${fmt(item.price)}</span>
                    </div>
                    <div class="c-qty">
                        <button type="button" class="btn-qty" onclick="updateQty(${id}, -1)">-</button>
                        <span style="font-weight:bold; font-size:13px; min-width:20px; text-align:center;">${item.qty}</span>
                        <button type="button" class="btn-qty" onclick="updateQty(${id}, 1)">+</button>
                    </div>
                    <div class="c-total">${fmt(sub)}</div>
                </div>
            `;

            // Input Hidden để POST lên PHP (quan trọng)
            inputs.innerHTML += `<input type="hidden" name="chonmon[${id}]" value="${id}">`;
            inputs.innerHTML += `<input type="hidden" name="soluong[${id}]" value="${item.qty}">`;
        }

        totalTxt.innerText = fmt(total);
        btn.disabled = (count === 0);

        if (count === 0) {
            box.innerHTML = `<div style="text-align:center; margin-top:50px; color:#999;">
                                <i class="fas fa-shopping-basket" style="font-size:30px; opacity:0.5; margin-bottom:10px;"></i><br>
                                Chưa chọn món
                            </div>`;
        }

        // Lưu cart xuống localStorage để reload trang không mất
        try {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
        } catch (e) {
            console.warn('Không lưu được cart vào localStorage:', e);
        }
    }

    // Khi submit đơn -> xóa cart trong localStorage để tránh dính đơn cũ
    (function attachSubmitClear() {
        const form = document.getElementById('orderForm');
        if (!form) return;
        form.addEventListener('submit', function() {
            try {
                localStorage.removeItem(CART_KEY);
            } catch (e) {}
        });
    })();
</script>