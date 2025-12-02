<?php
include '../classes/donvitinh.php';
include '../lib/session.php';
Session::checkSession();

// 1. CHECK QUYỀN ADMIN
$level = Session::get('adminlevel');
if ($level != 0 && $level != 3) {
    echo "<script>
        alert('Bạn không có quyền thực hiện thao tác này!');
        window.location.href = 'donvitinh_list.php';
    </script>";
    exit();
}

// 2. KHỞI TẠO CLASS
$dvt = new donvitinh();

// 3. LẤY ID VÀ XÓA (ẨN ĐI)
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'donvitinh_list.php';</script>";
} else {
    $id = (int)$_GET['id'];

    // Gọi hàm delete_don_vi_tinh (Lúc này trong class nó là lệnh UPDATE xoa = 1)
    $delDvt = $dvt->delete_don_vi_tinh($id);

    if ($delDvt) {
        echo "<script>
            alert('Xóa đơn vị tính thành công!');
            window.location = 'donvitinh_list.php';
        </script>";
    } else {
        echo "<script>
            alert('Có lỗi xảy ra, vui lòng thử lại.');
            window.location = 'donvitinh_list.php';
        </script>";
    }
}
?>