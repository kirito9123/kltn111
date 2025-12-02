<?php
require_once __DIR__ . '/classes/user.php';

$u = new user();
$token = $_GET['token'] ?? '';
$msg = $u->activateAccountByToken($token);

// Nếu thành công (hoặc đã kích hoạt rồi) => alert + chuyển trang
$isSuccess = (
    stripos($msg, 'kích hoạt thành công') !== false ||
    stripos($msg, 'đã kích hoạt') !== false
);

if ($isSuccess) {
    // Popup rồi quay về trang đăng nhập
    echo "<script>
            alert('".addslashes($msg)."');
            window.location.href = 'login.php';
          </script>";
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Kích hoạt tài khoản</title>
</head>
<body style="font-family: sans-serif">
  <h3><?= htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
  <p><a href="login.php">Đến trang đăng nhập</a></p>
</body>
</html>
