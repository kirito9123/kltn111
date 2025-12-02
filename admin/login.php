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
        body {
            background: linear-gradient(120deg, #3f51b5 0%, #2196f3 100%);
            font-family: 'Quicksand', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            padding: 44px 36px 36px 36px;
            border-radius: 20px;
            box-shadow: 0 8px 40px 0 rgba(31, 38, 135, 0.12), 0 1.5px 10px 0 rgba(31,38,135,0.08);
            width: 100%;
            max-width: 380px;
            margin: 24px 12px;
            box-sizing: border-box;
            animation: fadein 1s;
        }
        @keyframes fadein { from { opacity: 0; transform: translateY(16px);} to {opacity: 1; transform: none;} }

        .login-container h1 {
            text-align: center;
            font-size: 2rem;
            color: #3f51b5;
            margin-bottom: 30px;
            letter-spacing: 1.5px;
        }

        .login-container .logo {
            display: flex;
            justify-content: center;
            margin-bottom: 18px;
        }
        .login-container .logo img {
            width: 70px;
            border-radius: 50%;
            box-shadow: 0 2px 12px #1976d2;
            background: #fff;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #d6dbef;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: #f6f8fc;
            transition: border 0.2s;
            margin-top: 5px;
        }
        .form-group input:focus {
            border-color: #1976d2;
            background: #fff;
            outline: none;
        }
        .login-btn {
            width: 100%;
            padding: 12px 0;
            border: none;
            background: linear-gradient(90deg, #1976d2, #21cbf3);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 3px 10px rgba(25, 118, 210, 0.1);
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #1565c0, #00bcd4);
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
        }
        @media (max-width: 480px) {
            .login-container {padding: 30px 10px 28px 10px;}
            .login-container h1 {font-size: 1.4rem;}
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/livelogo2.png" alt="Logo">
        </div>
        <h1>Đăng nhập Admin</h1>
        <?php 
            if(isset($login_check) && $login_check) {
                echo '<div class="alert">'.$login_check.'</div>';
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
