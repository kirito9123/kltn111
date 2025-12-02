<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<div class="grid_10">
    <div class="box round first grid">
        <h2>Trang quản trị</h2>
        <div class="block">
            <?php
            $name = Session::get('adminname');
            $level = (int) Session::get('adminlevel');

            // Xác định tên vai trò dựa theo level
            switch ($level) {
                case 0:
                    $roleName = "Quản trị viên";
                    break;
                case 1:
                    $roleName = "Kế toán";
                    break;
                case 2:
                    $roleName = "Nhân viên quầy";
                    break;
                case 3:
                    $roleName = "Nhân viên bếp";
                    break;
                case 4:
                    $roleName = "Nhân viên chạy bàn";
                    break;
                default:
                    $roleName = "Nhân viên";
                    break;
            }

            echo "<h3>Xin chào {$roleName} <strong>{$name}</strong>!</h3>";

            // Nếu muốn thêm gợi ý nhỏ cho từng vai trò, có thể thêm:
            switch ($level) {
                case 0:
                    echo "<p>Bạn có toàn quyền truy cập hệ thống.</p>";
                    break;
                case 1:
                    echo "<p>Bạn có thể quản lý bảng lương, thống kê nhân viên nghỉ và tạo báo cáo.</p>";
                    break;
                case 2:
                    echo "<p>Bạn có thể quản lý đặt bàn, đơn hàng, chăm sóc khách hàng và chốt ca.</p>";
                    break;
                case 3:
                    echo "<p>Bạn có thể quản lý trang thiết bị, món ăn và nguyên vật liệu.</p>";
                    break;
                case 4:
                    echo "<p>Bạn có thể theo dõi bàn đặt và nhận món từ bếp.</p>";
                    break;
            }
            ?>
        </div>
    </div>
</div>
<?php include 'inc/footer.php'; ?>