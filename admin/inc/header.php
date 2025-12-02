<?php
    include '../lib/session.php';
    Session::checkSession();

    // --- [SỬA LỖI] ĐƯA ĐOẠN XỬ LÝ ĐĂNG XUẤT LÊN ĐẦU ---
    // Phải xử lý trước khi HTML được vẽ ra
    if(isset($_GET['action']) && $_GET['action']=='logout'){
        Session::destroy();
    }
    // --------------------------------------------------

    header("Content-type: text/html; charset=utf-8");
?>

<?php
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache"); 
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
  header("Cache-Control: max-age=2592000");
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Admin</title>
    <link rel="stylesheet" type="text/css" href="css/reset.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/text.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/grid.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/layout.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/nav.css" media="screen" />
    <link href="css/table/demo_page.css" rel="stylesheet" type="text/css" />
    <script src="js/jquery-1.6.4.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/jquery-ui/jquery.ui.core.min.js"></script>
    <script src="js/jquery-ui/jquery.ui.widget.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui/jquery.ui.accordion.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui/jquery.effects.core.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui/jquery.effects.slide.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui/jquery.ui.mouse.min.js" type="text/javascript"></script>
    <script src="js/jquery-ui/jquery.ui.sortable.min.js" type="text/javascript"></script>
   
    <script src="js/table/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="js/setup.js" type="text/javascript"></script>
     <script type="text/javascript">
        $(document).ready(function () {
            setupLeftMenu();
            setSidebarHeight();
        });
    </script>

    <style>
        /* 1. Cấu hình chung: Xóa viền trắng trình duyệt & set nền */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* 2. Container chính: Dùng Flexbox để chia cột & Ép chiều cao bằng nhau */
        .container_12 {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: -10 !important;
            display: flex !important;       /* Flexbox thần thánh */
            flex-wrap: wrap !important;     /* Cho phép header ngắt dòng */
            align-items: stretch !important;/* Ép các cột (Sidebar & Content) dài bằng nhau */
            min-height: 100vh;              /* Chiều cao tối thiểu full màn hình */
        }

        /* 3. HEADER: Luôn nằm trên cùng, chiếm 100% chiều rộng */
        .grid_12, .header-repeat {
            width: 100% !important;
            flex: 0 0 100%;                 /* Chiếm trọn dòng đầu tiên */
            max-width: 100%;
            margin: 0 !important;
            display: block;
            box-sizing: border-box;
            margin-top: 20px;
        }
        
        /* Header Background */
        .header-repeat {
            background-repeat: repeat-x;
            background-size: cover;
        }

        /* 4. SIDEBAR (Bên trái): Cố định 230px */
        .grid_2 {
            width: 230px !important;
            flex: 0 0 230px !important;     /* Không co giãn, cố định 230px */
            background: #fff;
            border-right: 1px solid #ddd;
            margin: 0 !important;
            padding: 0 !important;
            /* Lưu ý: Không set height cứng, để nó tự dãn theo Flexbox */
        }

        /* 5. NỘI DUNG (Bên phải): Tự dãn lấp đầy khoảng trống */
        .grid_10 { 
            flex: 1 !important; 
            width: auto !important; 
            margin: 0 !important; 
            padding: 20px !important; 
            background: #f4f6f9; 
            /* THÊM DÒNG NÀY VÀO LÀ XONG */
            min-height: 100vh !important; 
        }

        /* 6. Fix Menu Dropdown (Vấn đề thụt lề) */
        ul.sidebar-menu { list-style: none; padding: 0; margin: 0; }
        
        .menu-title { 
            display: flex; align-items: center; padding: 12px 15px; 
            color: #333; font-weight: 600; text-decoration: none; 
            cursor: pointer; border-bottom: 1px solid #f1f1f1; 
            background: #fff; transition: background 0.3s; 
        }
        
        .menu-title:hover { background: #f8f9fa; color: #007bff; }
        .menu-title .icon { margin-right: 10px; width: 20px; text-align: center; }
        
        /* Mũi tên */
        .menu-title .arrow { 
            margin-left: auto; width: 8px; height: 8px; 
            border-right: 2px solid #999; border-bottom: 2px solid #999; 
            transform: rotate(-45deg); transition: transform 0.3s; 
        }
        li.open > .menu-title .arrow { transform: rotate(45deg); border-color: #007bff; }
        li.open > .menu-title { color: #007bff; background: #eef2ff; border-left: 3px solid #007bff; }

        /* --- PHẦN BẠN CẦN SỬA ĐÂY (MENU CON) --- */
        .submenu { 
            display: none; list-style: none; padding: 0; 
            background: #fcfcfc; border-bottom: 1px solid #eee; 
        }
        
        .submenu li a { 
            display: block; 
            padding: 10px 15px 10px 0px; 
            color: #555; text-decoration: none; font-size: 13px; 
        }
        
        .submenu li a:hover { 
            color: #007bff; background: #fff; 
            padding-left: 0px; 
        }
        
        .submenu li a.active-link { 
            font-weight: bold; color: #d63031; background: #fff5f5; 
        }

        /* 7. Các fix lặt vặt khác */
        .clear { display: none; }

        .sidebar-wrapper { width: 100%; }

        #branding { 
            width: 100%; 
            /* Tăng padding trên/dưới lên 25px nữa => tổng cộng cao thêm 50px */
            padding: 25px 15px; 
            box-sizing: border-box; 
            /* Hoặc thích set chiều cao cứng luôn thì dùng dòng dưới (bỏ comment ra) */
            /* min-height: 120px; */
        }
    </style>
</head>
<body>
    <div class="container_12">
        <div class="grid_12 header-repeat">
            <div id="branding">
                <div class="floatleft logo">
                    <img src="img/livelogo2.png" alt="Logo" />
                </div>
                <div class="floatleft middle">
                    <h1>TRK Restaurant</h1>
                    <p></p>
                </div>
                <div class="floatright">
                    <div class="floatleft">
                        <img src="img/img-profile.jpg" alt="Profile Pic" /></div>
                    <div class="floatleft marginleft10">
                        <ul class="inline-ul floatleft">
                            <li>
                                Hello 
                                <a href="profile.php" style="color: #fff; font-weight: bold; text-decoration: none;">
                                    <?php echo Session::get('adminname') ?>
                                </a>
                            </li>
                            
                            <li>
                                <a href="?action=logout"
                                onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                                    Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="clear">
                </div>
            </div>
        </div>
        <div class="clear">
        </div>
        <div class="grid_12">
        <ul class="nav main">
            <li class="ic-dashboard"><a href="index.php"><span>Bảng điều khiển</span></a> </li>
            <li class="ic-typography"><a href="changepassword.php"><span>Đổi mật khẩu</span></a></li>
            <li class="ic-charts"><a href="../index.php"><span>Truy cập website</span></a></li>
        </ul>
        </div>
        <div class="clear">
        </div>