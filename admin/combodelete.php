<?php
// combodelete.php — Soft delete: đổi trang_thai = 1 + popup thông báo
include_once __DIR__ . '/../lib/session.php';
include_once __DIR__ . '/../lib/database.php';

Session::init();

// Kiểm tra đăng nhập và quyền admin
if (!Session::get('adminlogin')) { header("Location: login.php"); exit(); }
if ((int)Session::get('adminlevel') !== 0) {
    echo "<script>alert('Bạn không phải quản trị viên!'); window.location='index.php';</script>";
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "<script>alert('Mã combo không hợp lệ!'); window.location='combolist.php';</script>";
    exit();
}

$db = new Database();

// Cập nhật trạng thái = 1 (tạm xóa)
$sql = "UPDATE menu SET trang_thai = 1 WHERE id_menu = {$id} LIMIT 1";
$ok  = $db->update($sql);

if ($ok) {
    echo "<script>
        alert('✅ Xóa combo thành công!');
        window.location='combolist.php';
    </script>";
} else {
    echo "<script>
        alert('❌ Lỗi khi xóa combo. Vui lòng thử lại!');
        window.location='combolist.php';
    </script>";
}
exit();
