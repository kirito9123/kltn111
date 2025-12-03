<?php
include_once '../lib/session.php';

$role         = (int) Session::get('adminlevel'); // 0..4
$current_page = basename($_SERVER['PHP_SELF']);

/* --- GIỮ NGUYÊN LOGIC PHÂN QUYỀN --- */
$permissions = [
    0 => ['all'],
    1 => ['payroll.manage', 'staff.leave.stats', 'reports.create'],
    2 => [
        'booking.manage',
        'orders.manage',
        'invoice.export',
        'customers.register',
        'chat.customer',
        'mail.customer',
        'banquet.modify',
        'shift.close',
    ],
    3 => ['equipment.manage', 'orders.view', 'menu.manage', 'ingredients.manage'],
    4 => ['booking.manage', 'orders.view', 'kitchen.notify'],
];

// o admin - xem báo cáo doanh thu, quản lí khuyến mãi, quản lí bài viết, quản lí chức vụ, nhân viên
// 1 kế toán
// 2 nhân viên quầy - đặt bàn cho khách, thanh toán cho khách, quản lí khách hàng, xuất hóa đơn, chốt ca, nhắn tin tư vấn khách hàng, chỉnh sửa hóa đơn
// 3 nhân viên bếp - xem thông tin của đơn hàng, tạo món mới, tạo combo mới, xem tồn kho, với đơn nhập kho, quản lí trang thiết bị bếp
// 4 nhân viên chạy bàn - xem thông tin những đơn hàng đã xong của bếp, đặt bàn cho khách luôn, thay đổi món theo yêu cầu, thanh toán cho khách luôn

function hasPerm(string $p)
{
    global $permissions, $role;
    $list = $permissions[$role] ?? [];
    return in_array('all', $list, true) || in_array($p, $list, true);
}

// Helper: Thay vì style inline, ta trả về class 'active'
function activeClass($names, $current_page)
{
    $names = (array) $names;
    return in_array($current_page, $names, true) ? 'active-link' : '';
}

// Helper: Kiểm tra xem submenu có nên mở sẵn không
function isSubmenuOpen($names, $current_page)
{
    $names = (array) $names;
    return in_array($current_page, $names, true) ? 'style="display:block;"' : '';
}

// Helper: Thêm class 'open' cho menu cha nếu con đang active
function parentOpenClass($names, $current_page)
{
    $names = (array) $names;
    return in_array($current_page, $names, true) ? 'open' : '';
}
?>

<div class="sidebar-wrapper">
    <ul class="sidebar-menu">

        <?php if (hasPerm('booking.manage')):
            $sub_booking = ['danhsachdatban.php', 'booking_list.php', 'thanhtoanhopdong.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_booking, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">📅</span>
                    Quản Lý đặt bàn
                    <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_booking, $current_page); ?>>
                    <li>
                        <a href="danhsachdatban.php" class="<?php echo activeClass('danhsachdatban.php', $current_page) ?>">
                            <?php echo ($role === 2) ? 'Đặt bàn cho khách' : 'Danh sách đặt bàn'; ?>
                        </a>
                    </li>
                    <?php
                    // THAY ĐỔI TẠI ĐÂY:
                    // Cho phép hiển thị nếu có quyền banquet.modify HOẶC role hiện tại là 4 (Phục vụ)
                    if (hasPerm('banquet.modify') || $role == 4):
                    ?>
                        <li><a href="booking_list.php" class="<?php echo activeClass('booking_list.php', $current_page) ?>">Thanh Toán Hóa Đơn</a></li>
                    <?php endif; ?>
                    <?php if (hasPerm('shift.close')): ?>
                        <li><a href="chotca.php" class="<?php echo activeClass('chotca.php', $current_page) ?>">Chốt ca</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('orders.manage') || hasPerm('orders.view')):
            $sub_orders = ['create_booking.php', 'admin_orders.php', 'xuat_hoa_don.php', 'chotca.php', 'kitchen_notify.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_orders, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">🧾</span>
                    Quản Lý đơn bàn
                    <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_orders, $current_page); ?>>
                    <?php if (hasPerm('orders.manage')): ?>
                    <?php endif; ?>
                    <?php if ($role === 0 || $role === 2 || $role === 3): ?>
                        <li><a href="admin_orders.php" class="<?php echo activeClass('admin_orders.php', $current_page) ?>">Xem danh sách đơn</a></li>
                    <?php endif; ?>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="kitchen_close.php" class="<?php echo activeClass('kitchen_close.php', $current_page) ?>">Chốt ca Bếp</a></li>
                    <?php endif; ?>
                    <?php if (hasPerm('kitchen.notify')): ?>
                        <li><a href="nhanvienphucvu_order.php" class="<?php echo activeClass('nhanvienphucvu_order.php', $current_page) ?>">Thông báo món xong</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('menu.manage')):
            $sub_cat = ['catadd.php', 'catlist.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_cat, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">📂</span>
                    Quản Lý Loại Món
                    <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_cat, $current_page); ?>>
                    <li><a href="catlist.php" class="<?php echo activeClass('catlist.php', $current_page) ?>">Danh sách Loại Món</a></li>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="catadd.php" class="<?php echo activeClass('catadd.php', $current_page) ?>">Thêm Loại Món</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('menu.manage') || $role === 3):
            $sub_prod = ['productadd.php', 'productlist.php', 'productedit.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_prod, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">🍲</span>
                    Quản Lý Món Ăn
                    <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_prod, $current_page); ?>>
                    <li><a href="productlist.php" class="<?php echo activeClass(['productlist.php', 'productedit.php'], $current_page) ?>">Danh sách món</a></li>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="productadd.php" class="<?php echo activeClass('productadd.php', $current_page) ?>">Thêm món mới</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php $sub_combo = ['comboadd.php', 'combolist.php']; ?>
            <li class="has-sub <?php echo parentOpenClass($sub_combo, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">🍱</span>
                    Quản Lý Combo
                    <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_combo, $current_page); ?>>
                    <li><a href="combolist.php" class="<?php echo activeClass('combolist.php', $current_page) ?>">Danh sách Combo</a></li>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="comboadd.php" class="<?php echo activeClass('comboadd.php', $current_page) ?>">Thêm Combo</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('ingredients.manage') || $role === 0):
            $sub_ing = ['nguyenlieu_add.php', 'nguyenlieu_list.php', 'nguyenlieu_edit.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_ing, $current_page); ?>">
                <a class="menu-title"><span class="icon">🥕</span> Quản lý Nguyên Liệu <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_ing, $current_page); ?>>
                    <li><a href="nguyenlieu_list.php" class="<?php echo activeClass('nguyenlieu_list.php', $current_page) ?>">Danh sách Nguyên Liệu</a></li>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="nguyenlieu_add.php" class="<?php echo activeClass('nguyenlieu_add.php', $current_page) ?>">Thêm Nguyên Liệu</a></li>
                        <li><a href="nhapkho.php" class="<?php echo activeClass('nhapkho.php', $current_page) ?>">Nhập Kho</a></li>
                        <li><a href="lichsunhapkho.php" class="<?php echo activeClass('lichsunhapkho.php', $current_page) ?>">Lịch Sử Nhập Kho</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('ingredients.manage') || hasPerm('recipes.manage') || $role === 0):
            $sub_recipe = ['congthuc_list.php', 'congthuc_add.php', 'congthuc_edit.php', 'donvitinh_list.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_recipe, $current_page); ?>">
                <a class="menu-title"><span class="icon">📜</span> Quản lý Công Thức <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_recipe, $current_page); ?>>
                    <li><a href="congthuc_list.php" class="<?php echo activeClass('congthuc_list.php', $current_page) ?>">Danh sách Công Thức</a></li>
                    <?php if ($role === 0 || $role === 3): ?>
                        <li><a href="donvitinh_list.php" class="<?php echo activeClass('donvitinh_list.php', $current_page) ?>">Đơn vị tính</a></li>
                        <li><a href="congthuc_add.php" class="<?php echo activeClass('congthuc_add.php', $current_page) ?>">Thêm Công Thức</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('equipment.manage')):
            $sub_equip = ['equipmentadd.php', 'equipmentlist.php', 'equipmentedit.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_equip, $current_page); ?>">
                <a class="menu-title"><span class="icon">🔧</span> Quản Lý Thiết Bị <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_equip, $current_page); ?>>
                    <?php if ($role === 0): ?>
                        <li><a href="equipmentadd.php" class="<?php echo activeClass('equipmentadd.php', $current_page) ?>">Thêm Thiết Bị</a></li>
                    <?php endif; ?>
                    <?php if ($role === 3): ?>
                        <li><a href="equipmentadd.php" class="<?php echo activeClass('equipmentadd.php', $current_page) ?>">Thêm Thiết Bị</a></li>
                    <?php endif; ?>
                    <li><a href="equipmentlist.php" class="<?php echo activeClass(['equipmentlist.php', 'equipmentedit.php'], $current_page) ?>">Danh sách Thiết Bị</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('customers.register')):
            $sub_cust = ['customeradd.php', 'customerlist.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_cust, $current_page); ?>">
                <a class="menu-title"><span class="icon">👥</span> Quản Lý Khách Hàng <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_cust, $current_page); ?>>
                    <li><a href="customeradd.php" class="<?php echo activeClass('customeradd.php', $current_page) ?>">Đăng kí khách mới</a></li>
                    <li><a href="customerlist.php" class="<?php echo activeClass('customerlist.php', $current_page) ?>">Danh Sách Khách Hàng</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php
        if ($role === 0):
            $sub_report = ['baocao_quanly.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_report, $current_page); ?>">
                <a class="menu-title">
                    <span class="icon">📈</span> Báo Cáo & Thống Kê <span class="arrow"></span>
                </a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_report, $current_page); ?>>
                    <li>
                        <a href="baocao_quanly.php" class="<?php echo activeClass('baocao_quanly.php', $current_page) ?>">
                            Báo Cáo Quản Trị
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (hasPerm('mail.customer') || $role === 1):
            $sub_mail = ['sendmail.php', 'send_history.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_mail, $current_page); ?>">
                <a class="menu-title"><span class="icon">📧</span> Quản lý Mail <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_mail, $current_page); ?>>
                    <li><a href="sendmail.php" class="<?php echo activeClass('sendmail.php', $current_page) ?>">Gửi Mail</a></li>
                    <li><a href="send_history.php" class="<?php echo activeClass('send_history.php', $current_page) ?>">Lịch sử gửi</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($role === 0 || $role === 1):
            $sub_acc = ['quanlyluong.php', 'quanlythuchi.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_acc, $current_page); ?>">
                <a class="menu-title"><span class="icon">💰</span> Tài chính & Lương <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_acc, $current_page); ?>>
                    <li><a href="quanlyluong.php" class="<?php echo activeClass('quanlyluong.php', $current_page) ?>">Quản lý Lương</a></li>
                    <li><a href="quanlythuchi.php" class="<?php echo activeClass('quanlythuchi.php', $current_page) ?>">Quản lý Thu Chi</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($role === 0 || $role === 1):
            $sub_hr = ['themnhansu.php', 'quanlynhansu_list.php', 'quanlynhansu_hidden_list.php', 'chamcong.php', 'theodoi_chamcong.php', 'lichdangkylist.php', 'lichdangky_add.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_hr, $current_page); ?>">
                <a class="menu-title"><span class="icon">👔</span> Quản Lý Nhân Sự <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_hr, $current_page); ?>>
                    <?php if ($role === 0): ?>
                        <li><a href="themnhansu.php" class="<?php echo activeClass('themnhansu.php', $current_page) ?>">Thêm nhân sự</a></li>
                    <?php endif; ?>
                    <li><a href="quanlynhansu_list.php" class="<?php echo activeClass('quanlynhansu_list.php', $current_page) ?>">Danh sách nhân sự</a></li>
                    <li><a href="quanlynhansu_hidden_list.php" class="<?php echo activeClass('quanlynhansu_hidden_list.php', $current_page) ?>">Nhân sự đã ẩn</a></li>

                    <li><a href="chamcong.php" class="<?php echo activeClass('chamcong.php', $current_page) ?>">Chấm công</a></li>
                    <li><a href="theodoi_chamcong.php" class="<?php echo activeClass('theodoi_chamcong.php', $current_page) ?>">Theo dõi chấm công</a></li>

                    <li><a href="lichdangkylist.php" class="<?php echo activeClass('lichdangkylist.php', $current_page) ?>">DS Lịch đăng ký</a></li>
                    <li><a href="lichdangky_add.php" class="<?php echo activeClass('lichdangky_add.php', $current_page) ?>">Đăng ký lịch</a></li>

                    <?php if ($role === 0): ?>
                        <li><a href="http://localhost:5000/them_khuon_mat" target="_blank">Thêm khuôn mặt</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($role === 0 || $role === 1):
            $sub_hr = ['xinnghi_list.php', 'xinnghi_add.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_hr, $current_page); ?>">
                <a class="menu-title"><span class="icon">👔</span> Quản Lý Xin Nghỉ Phép <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_hr, $current_page); ?>>
                    <li><a href="xinnghi_list.php" class="<?php echo activeClass('xinnghi_list.php', $current_page) ?>">DS Xin nghỉ</a></li>
                    <li><a href="xinnghi_add.php" class="<?php echo activeClass('xinnghi_add.php', $current_page) ?>">Đăng ký nghỉ</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($role === 0):
            $sub_post = ['baivietadd.php', 'baivietlist.php', 'baivietedit.php'];
        ?>
            <li class="has-sub <?php echo parentOpenClass($sub_post, $current_page); ?>">
                <a class="menu-title"><span class="icon">📝</span> Quản Lý Bài Viết <span class="arrow"></span></a>
                <ul class="submenu" <?php echo isSubmenuOpen($sub_post, $current_page); ?>>
                    <li><a href="baivietadd.php" class="<?php echo activeClass('baivietadd.php', $current_page) ?>">Thêm Bài Viết</a></li>
                    <li><a href="baivietlist.php" class="<?php echo activeClass(['baivietlist.php', 'baivietedit.php'], $current_page) ?>">Danh sách Bài Viết</a></li>
                </ul>
            </li>
            </li>
        <?php endif; ?>

        <?php if ($role === 2 || $role === 3 || $role === 4): ?>
            <li>
                <a class="menu-title single-link <?php echo activeClass('lichdangky_add.php', $current_page) ?>" href="lichdangky_add.php">
                    <span class="icon">📅</span> Đăng ký lịch làm việc
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 2 || $role === 3 || $role === 4): ?>
            <li>
                <a class="menu-title single-link <?php echo activeClass('lichdangkylist.php', $current_page) ?>" href="lichdangkylist.php">
                    <span class="icon">📅</span> Xem lịch làm việc
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 2 || $role === 3 || $role === 4): ?>
            <li>
                <a class="menu-title single-link <?php echo activeClass('chamcong.php', $current_page) ?>" href="chamcong.php">
                    <span class="icon">📅</span> Chấm công
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 0 || $role === 2): ?>
            <li>
                <a class="menu-title single-link <?php echo activeClass('admin_chat.php', $current_page) ?>" href="admin_chat.php">
                    <span class="icon">💬</span> Chat với KH
                </a>
            </li>
        <?php endif; ?>


    </ul>
</div>

<style>
    /* Reset cơ bản cho menu */
    .sidebar-wrapper {
        width: 230px;
        float: left;
        background: #fff;
        border-right: 1px solid #ddd;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px;
        min-height: 100vh;
        box-sizing: border-box;
        margin-left: -30px;
    }

    ul.sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    /* Tiêu đề menu cha */
    .menu-title {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #333;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
        background: #fff;
        transition: background 0.3s;
        position: relative;
    }

    .menu-title:hover {
        background: #f8f9fa;
        color: #007bff;
    }

    /* Icon bên trái */
    .menu-title .icon {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    /* Mũi tên bên phải */
    .menu-title .arrow {
        margin-left: auto;
        width: 8px;
        height: 8px;
        border-right: 2px solid #999;
        border-bottom: 2px solid #999;
        transform: rotate(-45deg);
        transition: transform 0.3s;
    }

    /* Khi menu mở thì mũi tên xoay xuống */
    li.open>.menu-title .arrow {
        transform: rotate(45deg);
        border-color: #007bff;
    }

    /* Highlight menu cha khi đang mở */
    li.open>.menu-title {
        color: #007bff;
        background: #eef2ff;
        border-left: 3px solid #007bff;
        /* Điểm nhấn bên trái */
    }

    /* Submenu (Menu con) */
    .submenu {
        display: none;
        /* Ẩn mặc định */
        list-style: none;
        padding: 0;
        background: #fcfcfc;
        border-bottom: 1px solid #eee;
        margin-left: -30px;
    }

    .submenu li a {
        display: block;
        /* SỬA SỐ NÀY: Để 15px là nó thẳng tắp với lề trái của menu cha */
        padding: 10px 15px 10px 20px !important;
        color: #555;
        text-decoration: none;
        font-size: 13px;
        border-bottom: 1px dashed #eee;
        /* Thêm dòng kẻ mờ ngăn cách cho dễ nhìn */
    }

    /* Khi di chuột vào thì vẫn đẩy nhẹ vô 1 chút cho đẹp */
    .submenu li a:hover {
        color: #007bff;
        background: #fff;
        padding-left: 20px !important;
        /* Đẩy nhẹ 5px thôi */
    }

    /* Link đang Active (Trang hiện tại) */
    .submenu li a.active-link,
    .menu-title.single-link.active-link {
        font-weight: bold;
        color: #d63031;
        /* Màu đỏ nổi bật */
        background: #fff5f5;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Sự kiện click vào menu cha
        $(".menu-title").on("click", function(e) {
            // Nếu là link đơn (không có submenu) thì chuyển trang bình thường
            if ($(this).hasClass('single-link')) return;

            e.preventDefault(); // Chặn chuyển trang nếu là dropdown

            var parentLi = $(this).parent('li');
            var submenu = $(this).next('.submenu');

            // Slide Toggle
            submenu.slideToggle(300);

            // Toggle class open để xoay mũi tên
            parentLi.toggleClass('open');

            // (Tuỳ chọn) Đóng các menu khác khi mở menu này
            // $(".has-sub").not(parentLi).removeClass("open").find(".submenu").slideUp();
        });
    });
</script>