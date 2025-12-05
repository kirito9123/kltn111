<?php
include_once '../lib/session.php';
include '../classes/adminlogin.php';
Session::checkLogin();

$class = new adminlogin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminuser = $_POST['adminuser'];
    $adminpass = md5($_POST['adminpass']);
    $login_check = $class->login_admin($adminuser, $adminpass);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng nhập Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts: Quicksand -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset nhỏ để mọi thứ canh cho chuẩn */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: linear-gradient(120deg, #3f51b5 0%, #2196f3 100%);
            font-family: 'Quicksand', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: #fff;
            padding: 40px 32px 32px 32px;
            border-radius: 20px;
            box-shadow: 0 8px 40px 0 rgba(31, 38, 135, 0.12),
                0 1.5px 10px 0 rgba(31, 38, 135, 0.08);
            width: 100%;
            max-width: 380px;
            margin: 24px 12px;
            animation: fadein 0.8s;
        }

        @keyframes fadein {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .login-container .logo {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .login-container .logo img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            box-shadow: 0 2px 12px #1976d2;
            background: #fff;
            object-fit: cover;
        }

        .login-container h1 {
            text-align: center;
            font-size: 1.9rem;
            color: #3f51b5;
            margin-bottom: 26px;
            letter-spacing: 1.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            /* đảm bảo label + input thẳng hàng */
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d6dbef;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: #f6f8fc;
            transition: border 0.2s, background 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            border-color: #1976d2;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 0.16rem rgba(25, 118, 210, 0.25);
        }

        .login-btn {
            width: 100%;
            padding: 12px 0;
            border: none;
            background: linear-gradient(90deg, #1976d2, #21cbf3);
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 4px;
            box-shadow: 0 3px 10px rgba(25, 118, 210, 0.25);
            transition: transform 0.1s, box-shadow 0.1s, background 0.2s;
        }

        .login-btn:hover {
            background: linear-gradient(90deg, #1565c0, #00bcd4);
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(25, 118, 210, 0.35);
        }

        .alert {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            padding: 11px 14px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 28px 16px 24px 16px;
            }

            .login-container h1 {
                font-size: 1.5rem;
            }
        }
    </style>

</head>

<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/livelogo2.png" alt="Logo">
        </div>
        <h1>Đăng nhập Hệ Thống Quản Lý</h1>
        <?php
        if (isset($login_check) && $login_check) {
            echo '<div class="alert">' . $login_check . '</div>';
        }
        ?>
        <form action="login.php" method="post" autocomplete="off">
            <div class="form-group">
                <label for="adminuser">Tên đăng nhập</label>
                <input type="text" id="adminuser" name="adminuser" placeholder="Tên đăng nhập" required>
            </div>
            <div class="form-group">
                <label for="adminpass">Mật khẩu</label>
                <input type="password" id="adminpass" name="adminpass" placeholder="Mật khẩu" required>
            </div>
            <button type="submit" class="login-btn">Đăng nhập</button>
        </form>
    </div>
</body>

</html>