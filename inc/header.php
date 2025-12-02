<?php
ob_start();
require_once 'session.php';
Session::init();

include __DIR__ . '/../lib/database.php';
include __DIR__ . '/../helpers/format.php';

/* Nạp Composer autoload 1 lần cho toàn site (để dùng PHPMailer, v.v.) */
require_once __DIR__ . '/../vendor/autoload.php';

/* Alias cho code legacy gọi class không namespace (nếu có) */
if (!class_exists('PHPMailer')) {
    class_alias('PHPMailer\PHPMailer\PHPMailer', 'PHPMailer');
}
if (!class_exists('SMTP')) {
    class_alias('PHPMailer\PHPMailer\SMTP', 'SMTP');
}
if (!class_exists('phpmailerException')) {
    class_alias('PHPMailer\PHPMailer\Exception', 'phpmailerException');
}

/* Autoloader CHỈ cho class nội bộ (không bắt class có namespace) */
spl_autoload_register(function ($class) {
    // Bỏ qua class có namespace để tránh include sai kiểu "classes/PHPMailer\PHPMailer\..."
    if (strpos($class, '\\') !== false) return;

    $path = __DIR__ . '/../classes/' . $class . '.php';
    if (is_file($path)) {
        include_once $path;
    }
});

$db      = new Database();
$fm      = new Format();
$ct      = new cart();
$us      = new user();
$loaimon = new loaimon();
$mon     = new mon();

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: max-age=2592000");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>TRisKiet Restaurant</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- CSS mặc định -->
    <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/aos.css">
    <link rel="stylesheet" href="css/ionicons.min.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/icomoon.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- CSS tùy chỉnh -->
    <style>
        /* Base */
        .nav-item.has-submenu {
            position: relative;
        }

        .nav-item.has-submenu>.nav-link::after {
            content: "▾";
            margin-left: 6px;
            font-size: .8em;
            opacity: .8;
        }

        /* Hộp submenu */
        .submenu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            background: #fff;
            list-style: none;
            margin: 0;
            padding: 8px 0;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
            z-index: 2000;
        }

        .submenu li {
            position: relative;
        }

        .submenu li a {
            display: block;
            padding: 10px 14px;
            text-decoration: none;
            color: #333;
            white-space: nowrap;
            transition: .25s ease;
            border-radius: 8px;
            margin: 0 6px;
        }

        .submenu li a:hover {
            background: #d19c65;
            color: #fff;
        }

        /* Submenu cấp 2+ (hiện ngang bên phải) */
        .submenu .has-submenu>.submenu {
            top: 0;
            left: 100%;
            margin-left: 6px;
        }

        .submenu .has-submenu>a::after {
            content: "▸";
            float: right;
            opacity: .7;
        }

        /* Desktop: mở bằng hover */
        @media (min-width: 992px) {
            .nav-item.has-submenu:hover>.submenu {
                display: block;
            }

            .submenu .has-submenu:hover>.submenu {
                display: block;
            }
        }

        /* Mobile: hiển thị theo class .open (JS sẽ toggle) */
        @media (max-width: 991px) {
            .navbar-nav {
                background: #3e3e3e;
                padding: 10px;
                border-radius: 10px;
            }

            .nav-item.has-submenu>.nav-link::after {
                color: #f8f9fa;
            }

            .submenu {
                position: static;
                display: none;
                background: transparent;
                box-shadow: none;
                padding: 4px 0 8px 0;
                border-radius: 0;
            }

            .submenu li a {
                color: #f8f9fa;
                margin: 0;
                padding-left: 22px;
            }

            .submenu li a:hover {
                background: #d19c65;
                color: #fff;
            }

            /* Khi .open thì hiện submenu */
            .has-submenu.open>.submenu {
                display: block;
            }
        }

        /* “Cầu nối” tránh rớt hover (desktop) */
        @media (min-width: 992px) {
            .nav-item.has-submenu::after {
                content: "";
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                height: 10px;
            }
        }
    </style>

    <!-- JS: toggle submenu MOBILE cho TẤT CẢ .has-submenu -->
    <script>
        (function() {
            var BREAKPOINT = 991;
            var items = document.querySelectorAll('.nav-item.has-submenu, .submenu .has-submenu');

            function isMobile() {
                return window.innerWidth <= BREAKPOINT;
            }

            items.forEach(function(item) {
                var link = item.querySelector(':scope > a'); // chỉ anchor trực tiếp
                var submenu = item.querySelector(':scope > .submenu');
                if (!link || !submenu) return;

                link.addEventListener('click', function(e) {
                    if (!isMobile()) return; // desktop: để hover lo
                    if (!item.classList.contains('open')) {
                        e.preventDefault(); // lần 1: mở, KHÔNG điều hướng
                        // đóng các item anh em cùng cấp
                        var siblings = item.parentElement.querySelectorAll(':scope > .has-submenu.open');
                        siblings.forEach(function(sib) {
                            if (sib !== item) sib.classList.remove('open');
                        });
                        item.classList.add('open');
                    } // lần 2: đã open -> cho phép đi link bình thường
                });
            });

            // Click ngoài để đóng (mobile)
            document.addEventListener('click', function(e) {
                if (!isMobile()) return;
                var nav = document.getElementById('ftco-nav');
                if (!nav) return;
                if (!nav.contains(e.target)) {
                    document.querySelectorAll('.has-submenu.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                }
            });

            // Khi resize, bỏ trạng thái open để tránh kẹt lớp
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    document.querySelectorAll('.has-submenu.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                }
            });
        })();
    </script>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo2.png" alt="GS Restaurant" style="max-height: 100px;">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="oi oi-menu"></span> Menu
            </button>

            <div class="collapse navbar-collapse" id="ftco-nav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="index.php" class="nav-link">TRANG CHỦ</a></li>
                    <li class="nav-item"><a href="about.php" class="nav-link">GIỚI THIỆU</a></li>

                    <!-- Menu có dropdown -->
                    <li class="nav-item has-submenu">
                        <a href="menu.php" class="nav-link" tabindex="0">MENU</a>
                        <ul class="submenu">
                            <li><a href="menu.php?id_loai=28">Món khai vị</a></li>
                            <li><a href="menu.php?id_loai=29">Món chính</a></li>
                            <li><a href="menu.php?id_loai=30">Món tráng miệng</a></li>
                            <li><a href="menu.php?id_loai=31">Nước uống</a></li>
                            <li><a href="menu.php?id_loai=32">Đồ uống có cồn</a></li>
                            <li><a href="menu.php?id_loai=33">Dịch Vụ</a></li>
                        </ul>
                    </li>

                    <li class="nav-item has-submenu">
                        <a href="phong.php" class="nav-link" tabindex="0">KHÔNG GIAN</a>
                        <ul class="submenu">
                            <li><a href="phong.php?id=1">Khu Hải Sản Sống</a></li>
                            <li class="has-submenu">
                                <a href="phong.php?id=2">Khu Sảnh Tiệc</a>
                                <ul class="submenu">
                                    <li><a href="phong.php?id=2&hall=ruby">Sảnh Ruby</a></li>
                                    <li><a href="phong.php?id=2&hall=diamond">Sảnh Diamond</a></li>
                                    <li><a href="phong.php?id=2&hall=emerald">Sảnh Emerald</a></li>
                                    <li><a href="phong.php?id=2&hall=sapphire">Sảnh Sapphire</a></li> <!-- mới -->
                                </ul>
                            </li>
                            <li><a href="phong.php?id=3">Khu Sân Vườn</a></li>
                            <li class="has-submenu">
                                <a href="phong.php?id=4">Khu Phòng Vip</a>
                                <ul class="submenu">
                                    <li><a href="phong.php?id=4&vip=1">Phòng VIP 1</a></li>
                                    <li><a href="phong.php?id=4&vip=2">Phòng VIP 2</a></li>
                                    <li><a href="phong.php?id=4&vip=3">Phòng VIP 3</a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item has-submenu">
                        <a href="dichvu.php" class="nav-link" tabindex="0">DỊCH VỤ TIỆC</a>
                        <ul class="submenu">
                            <li><a href="dichvu.php?id=1">Tiệc Đầy Tháng</a></li>
                            <li><a href="dichvu.php?id=2">Tiệc Báo Hỷ</a></li>
                            <li><a href="dichvu.php?id=3">Tiệc Sinh Nhật</a></li>
                            <li><a href="dichvu.php?id=4">Tiệc Liên Hoan</a></li>
                        </ul>
                    </li>

                    <!-- <li class="nav-item"><a href="blog.php" class="nav-link">Tin tức</a></li> -->

                    <?php if (!empty(Session::get('name'))) { ?>
                        <li class="nav-item"><a href="chat.php" class="nav-link">Chat với admin</a></li>
                    <?php } ?>

                    <li class="nav-item cta">
                        <a href="datban.php" class="nav-link">
                            <img src="images/shopping-cart.jpg" height="28" width="38" alt="Cart">
                            <?php if ($ct->check()) {
                                echo $fm->formatMoney(Session::get("sum"));
                            } ?>
                        </a>
                    </li>

                    <li class="nav-item cta">
                        <a class="nav-link" href="https://www.facebook.com/nguyen.minh.tri.114121/">Liên hệ</a>
                    </li>

                    <?php
                    if (isset($_GET['action']) && $_GET['action'] == 'logout') {
                        Session::destroy();
                    }
                    ?>

                    <?php if (!empty(Session::get('name'))) { ?>
                        <li class="nav-item cta">
                            <a class="nav-link" href="userblog.php?id=<?= Session::get('id') ?>">
                                <?= Session::get('name') ?>
                            </a>
                        </li>
                        <li class="nav-item cta">
                            <a class="nav-link" href="?action=logout" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                                Đăng xuất
                            </a>
                        </li>
                    <?php } else { ?>
                        <li class="nav-item cta">
                            <a class="nav-link" href="login.php">Đăng nhập</a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    // ==================== HIỂN THỊ TOAST GÓC PHẢI ====================
    $toastMsg  = Session::get('toast_message');
    $toastType = Session::get('toast_type') ?: 'success';
    if ($toastMsg) {
        Session::set('toast_message', null);
        Session::set('toast_type', null);
    ?>
        <style>
            .app-toast {
                position: fixed;
                right: 20px;
                bottom: 20px;
                background: #16a34a;
                /* xanh lá = success */
                color: #fff;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
                font-size: 14px;
                line-height: 1.4;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity .3s ease, transform .3s ease;
                z-index: 9999;
            }

            .app-toast.error {
                background: #dc2626;
            }

            .app-toast.show {
                opacity: 1;
                transform: translateY(0);
            }

            .app-toast .x {
                margin-left: 10px;
                cursor: pointer;
                opacity: .9;
                font-weight: bold;
            }
        </style>

        <div id="appToast" class="app-toast <?= $toastType ?>">
            <?= htmlspecialchars($toastMsg, ENT_QUOTES, 'UTF-8') ?>
            <span class="x" onclick="hideAppToast()">&times;</span>
        </div>

        <script>
            (function() {
                const el = document.getElementById('appToast');
                if (!el) return;
                // Hiện toast
                requestAnimationFrame(() => el.classList.add('show'));
                // Tự ẩn sau 4 giây
                setTimeout(hideAppToast, 4000);
            })();

            function hideAppToast() {
                const el = document.getElementById('appToast');
                if (!el) return;
                el.classList.remove('show');
                setTimeout(() => el.remove(), 300);
            }
        </script>
    <?php } ?>

    <!-- JS: jQuery + Popper + Bootstrap (nếu bạn đã có ở cuối file khác thì có thể bỏ bớt) -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <!-- JS nhỏ: mở submenu bằng click trên mobile, click lần 2 mới đi trang menu.php -->
    <script>
        (function() {
            var BREAKPOINT = 991; // khớp với CSS @media
            var item = document.querySelector('.nav-item.has-submenu');
            if (!item) return;

            var link = item.querySelector('a.nav-link');
            var submenu = item.querySelector('.submenu');
            if (!link || !submenu) return;

            // Chặn đóng khi rê qua "cầu nối" (desktop đã xử lý bằng CSS)
            // Mobile: toggle open khi click
            link.addEventListener('click', function(e) {
                var isMobile = window.innerWidth <= BREAKPOINT;
                if (!isMobile) return; // desktop để mặc định

                if (!item.classList.contains('open')) {
                    // mở lần 1, không chuyển trang
                    e.preventDefault();
                    item.classList.add('open');
                } else {
                    // đang mở rồi -> để mặc định: chuyển trang menu.php
                }
            });

            // Bấm ngoài để đóng (mobile)
            document.addEventListener('click', function(e) {
                var isMobile = window.innerWidth <= BREAKPOINT;
                if (!isMobile) return;
                if (!item.contains(e.target)) {
                    item.classList.remove('open');
                }
            });
        })();
    </script>