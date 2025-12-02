<?php
include_once "../lib/session.php";
Session::init();   // ❗ BẮT BUỘC — nếu thiếu => bị đá về index

include "../classes/nguyenvatlieu.php";

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// quyền: admin (0) + bếp (3)
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền xóa nguyên liệu!');
        window.location='nguyenlieu_list.php';
    </script>";
    exit();
}

// kiểm tra ID
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location='nguyenlieu_list.php'</script>";
    exit();
}

$id = (int)$_GET['id'];

$nl = new nguyenvatlieu();
$result = $nl->delete_nguyen_lieu($id);

// trả kết quả
if ($result) {
    echo "<script>
        alert('Đã chuyển nguyên liệu vào thùng rác!');
        window.location='nguyenlieu_list.php';
    </script>";
} else {
    echo "<script>
        alert('Xóa thất bại!');
        window.location='nguyenlieu_list.php';
    </script>";
}

exit();
