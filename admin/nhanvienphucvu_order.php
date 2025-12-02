<?php
/* ================ ADMIN: MÀN HÌNH PHỤC VỤ ================ */
require_once '../classes/nhanvienphucvu.php'; 
$phucvu = new nhanvienphucvu();

/* ====== GIAO DIỆN ====== */
require_once 'inc/header.php';
require_once 'inc/sidebar.php';

function vnd($n) { return number_format((float)$n, 0, ',', '.') . ' đ'; }
?>

<style>
    /* === FIX LAYOUT === */
    .container_12 { display: block !important; width: 100% !important; overflow: hidden !important; }
    .grid_2 { float: left !important; width: 230px !important; margin: 0 !important; }
    .grid_10 {
        float: left !important;
        width: calc(100% - 230px) !important;
        margin: 0 !important;
        padding: 20px !important;
        box-sizing: border-box !important;
        background: #f4f6f9;
        min-height: 100vh;
    }
    .grid_10 .clear { display: none; }

    /* === TABS GIAO DIỆN (MỚI) === */
    .tab-container { display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
    .tab-btn {
        padding: 12px 25px; border: none; background: #e0e0e0; border-radius: 8px;
        font-size: 16px; font-weight: 700; color: #555; cursor: pointer; transition: 0.3s;
        display: flex; align-items: center; gap: 10px;
    }
    .tab-btn.active { background: #2980b9; color: white; box-shadow: 0 4px 10px rgba(41, 128, 185, 0.4); }
    .tab-btn:hover:not(.active) { background: #dcdcdc; }
    
    .badge { 
        background: #e74c3c; color: white; padding: 2px 8px; 
        border-radius: 12px; font-size: 13px; min-width: 20px; text-align: center;
    }
    .badge-gray { background: #7f8c8d; }

    /* === DANH SÁCH ĐƠN === */
    .service-board { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
    
    .service-card {
        background: #fff; border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;
        border: 2px solid #2980b9; 
    }
    /* Thẻ lịch sử sẽ có màu xám để phân biệt */
    .service-card.history-card { border-color: #bdc3c7; opacity: 0.95; }
    .service-card.history-card .service-card__header { background: #7f8c8d; }

    .service-card__header { padding: 15px; background: #2980b9; color: white; display: flex; justify-content: space-between; align-items: flex-start; }
    .card-title { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
    .card-id { font-size: 13px; opacity: 0.9; font-weight: normal; display: block; }
    .card-time { font-size: 20px; font-weight: 800; }

    .service-card__body { padding: 15px; background: #ecf0f1; }
    .item-list-container { max-height: 250px; overflow-y: auto; margin-bottom: 10px; padding-right: 5px; }
    .item-list { list-style: none; margin: 0; padding: 0; }
    .order-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #bdc3c7; align-items: center; }
    .item-name { font-weight: 600; color: #333; font-size: 15px; flex: 1; padding-right: 10px; }
    .item-qty { font-weight: 900; color: #e74c3c; font-size: 18px; }
    
    .note-box { background: #f1c40f; color: #333; padding: 10px; font-size: 14px; font-weight: 600; border-radius: 6px; margin-top: 10px; }

    .service-card__footer { padding: 15px; background: #fff; text-align: center; }
    
    /* Nút Đã Giao */
    .btn-served { width: 100%; padding: 15px; border: none; border-radius: 8px; background: #27ae60; color: white; font-weight: 800; cursor: pointer; font-size: 16px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-served:hover { background: #219150; }
    
    /* Trạng thái Lịch sử */
    .history-status { color: #27ae60; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; }
</style>

<div class="grid_10">
    <div class="box round first grid" style="background: transparent; border: none; box-shadow: none;">
        
        <h2 class="service-title" style="margin-bottom: 10px;">
            <span style="font-size: 32px;">🔔</span> MÀN HÌNH PHỤC VỤ
        </h2>

        <div class="tab-container">
            <button class="tab-btn active" onclick="switchTab('wait')">
                <i class="fa fa-clock-o"></i> CHỜ GIAO MÓN 
                <span id="badge-wait" class="badge">0</span>
            </button>
            <button class="tab-btn" onclick="switchTab('history')">
                <i class="fa fa-history"></i> LỊCH SỬ HÔM NAY 
                <span id="badge-history" class="badge badge-gray">0</span>
            </button>
        </div>

        <div class="block" style="padding:0;">
            <div id="wait-orders-container" class="service-board">
                <div style="grid-column: 1 / -1; text-align:center; padding:50px; color:#7f8c8d; font-size:16px;">
                    Đang tải danh sách chờ...
                </div>
            </div>

            <div id="history-orders-container" class="service-board" style="display: none;">
                <div style="grid-column: 1 / -1; text-align:center; padding:50px; color:#7f8c8d; font-size:16px;">
                    Chưa có đơn nào được giao trong hôm nay.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const API_URL = "api_service.php"; 
    const NOTIFICATION_INTERVAL = 3000; 
    let lastOrderCount = 0;
    let currentTab = 'wait'; // Mặc định là tab chờ

    function playNotificationSound() {
        try {
            // new Audio('path/to/bell.mp3').play(); 
        } catch(e) { console.warn("Lỗi phát âm thanh:", e); }
    }

    // Hàm chuyển Tab
    function switchTab(tab) {
        currentTab = tab;
        
        // Đổi class Active cho nút bấm
        const btns = document.querySelectorAll('.tab-btn');
        btns[0].className = tab === 'wait' ? 'tab-btn active' : 'tab-btn';
        btns[1].className = tab === 'history' ? 'tab-btn active' : 'tab-btn';

        // Ẩn/Hiện container tương ứng
        document.getElementById('wait-orders-container').style.display = tab === 'wait' ? 'grid' : 'none';
        document.getElementById('history-orders-container').style.display = tab === 'history' ? 'grid' : 'none';
    }

    // Hàm tạo thẻ đơn hàng (Dùng chung cho cả Chờ và Lịch sử)
    function createOrderCard(order, isHistory = false) {
        let itemsHtml = order.items.map(it => `
            <li class="order-item">
                <span class="item-name">${it.mon}</span>
                <span class="item-qty">x${it.sl}</span>
            </li>
        `).join('');

        let noteBox = order.ghichu ? `<div class="note-box">Ghi chú: ${order.ghichu}</div>` : '';

        // Xử lý Footer (Nút bấm hoặc Trạng thái)
        let footerHtml = '';
        if (!isHistory) {
            // Tab Chờ: Hiện nút bấm
            footerHtml = `
                <button class="btn-served" data-id="${order.id}">
                    <i class="fa fa-check-circle"></i> ĐÃ GIAO MÓN CHO KHÁCH
                </button>`;
        } else {
            // Tab Lịch sử: Hiện giờ xong
            let timeDone = order.updated_at ? order.updated_at.substring(11, 16) : '--:--';
            footerHtml = `
                <div class="history-status">
                    <i class="fa fa-check-square-o"></i> Đã giao lúc ${timeDone}
                </div>`;
        }

        const card = document.createElement('div');
        // Thêm class history-card nếu là lịch sử để đổi màu
        card.className = isHistory ? 'service-card history-card' : 'service-card';
        if(!isHistory) card.setAttribute('data-id', order.id);
        
        card.innerHTML = `
            <div class="service-card__header">
                <div>
                    <div class="card-title">Bàn: ${order.tenban}</div>
                    
                    <span class="card-id">
                        Phòng: ${order.phong} &nbsp;|&nbsp; Đơn #${order.id}
                    </span>
                </div>
                <span class="card-time">${order.tg.substring(0, 5)}</span>
            </div>
            <div class="service-card__body">
                <p style="font-size:12px; color:#555;">${isHistory ? 'DANH SÁCH MÓN ĐÃ GIAO:' : 'MÓN BẾP ĐÃ LÀM XONG CẦN GIAO:'}</p>
                <div class="item-list-container">
                    <ul class="item-list">${itemsHtml}</ul>
                </div>
                ${noteBox}
            </div>
            <div class="service-card__footer">
                ${footerHtml}
            </div>
        `;
        return card;
    }

    // Hàm Polling chính
    function fetchNewOrders() {
        const waitContainer = document.getElementById('wait-orders-container');
        const historyContainer = document.getElementById('history-orders-container');
        
        fetch(API_URL)
            .then(res => {
                if (!res.ok) { throw new Error(`Lỗi HTTP: ${res.status}`); }
                return res.json();
            })
            .then(data => {
                if (!data) return;

                // 1. Cập nhật số lượng Badge trên Tab
                document.getElementById('badge-wait').innerText = data.count || 0;
                document.getElementById('badge-history').innerText = data.history ? data.history.length : 0;

                // 2. Phát âm thanh nếu có đơn chờ mới
                if (data.count > lastOrderCount && data.count > 0) {
                    playNotificationSound();
                }
                lastOrderCount = data.count;

                // 3. Render Tab Chờ Giao
                waitContainer.innerHTML = '';
                if (data.count > 0) {
                    data.orders.forEach(order => {
                        waitContainer.appendChild(createOrderCard(order, false));
                    });
                    document.title = `(${data.count}) Đơn Mới!`;
                } else {
                    waitContainer.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align:center; padding:60px; background:#fff; border-radius:8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            <h3 style="color:#27ae60; margin:0;"><i class="fa fa-coffee"></i> Tuyệt vời! Không có đơn nào chờ giao.</h3>
                        </div>`;
                    document.title = "Màn hình Phục Vụ";
                }

                // 4. Render Tab Lịch Sử
                historyContainer.innerHTML = '';
                if (data.history && data.history.length > 0) {
                    data.history.forEach(order => {
                        historyContainer.appendChild(createOrderCard(order, true));
                    });
                } else {
                    historyContainer.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align:center; padding:60px; color:#999;">
                            Chưa có đơn nào được giao trong hôm nay.
                        </div>`;
                }
            })
            .catch(err => {
                console.error("Lỗi Polling:", err);
                // Chỉ hiện lỗi ở tab đang xem
                if(currentTab === 'wait') 
                    waitContainer.innerHTML = `<div style="grid-column: 1 / -1; text-align:center; padding:50px; color:red;">Lỗi kết nối hoặc tải dữ liệu!</div>`;
            });
    }

    // Xử lý nút "ĐÃ GIAO MÓN"
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-served')) {
            const id = e.target.getAttribute('data-id');
            const card = e.target.closest('.service-card');

            if (!confirm("Xác nhận đã giao món cho đơn #" + id + "?")) return;

            fetch(API_URL, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=mark_served&id=" + encodeURIComponent(id)
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    // Hiệu ứng biến mất
                    card.style.transition = "all 0.5s";
                    card.style.opacity = "0";
                    card.style.transform = "scale(0.9)";
                    setTimeout(() => {
                        card.remove();
                        fetchNewOrders(); // Cập nhật lại ngay để đơn đó bay sang tab Lịch sử
                    }, 500);
                } else {
                    alert("Lỗi cập nhật trạng thái phục vụ!");
                }
            })
            .catch(err => alert("Lỗi kết nối tới server!"));
        }
    });

    // Bắt đầu Polling khi trang tải xong
    setInterval(fetchNewOrders, NOTIFICATION_INTERVAL);
    fetchNewOrders();
</script>

<?php require_once 'inc/footer.php'; ?>