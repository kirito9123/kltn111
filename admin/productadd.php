<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/loaimon.php';
include '../classes/mon.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Kiểm tra quyền hạn (chỉ cho admin và nhân viên bếp)
$level = (int) Session::get('adminlevel'); // ép kiểu để tránh lỗi so sánh kiểu dữ liệu
if ($level !== 0 && $level !== 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập! Chỉ quản trị viên hoặc nhân viên bếp mới được phép.');
        window.location.href = 'index.php';
    </script>";
    exit();
}
?>


<style>
    * {
    box-sizing: border-box;
}

.form-wrapper {
    max-width: 650px;
    margin: 40px auto;
    padding: 30px 40px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    font-family: 'Segoe UI', sans-serif;
}

.form-wrapper h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 26px;
    color: #2c3e50;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 15px;
}

.form-group input[type="text"],
.form-group input[type="file"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: border 0.2s ease;
    background-color: #fff;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007bff;
    outline: none;
}

.form-group textarea {
    min-height: 100px;
}

/* Fix cho select để hiển thị đều */
.form-group select {
    height: 44px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='gray' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px 18px;
    background-color: #fff;
}

/* Fix file input cho đẹp đều */
.form-group input[type="file"] {
    padding: 10px 14px;
    height: 44px;
    line-height: normal;
    background-color: #fff;
}

.form-actions {
    text-align: center;
    margin-top: 25px;
}

.form-actions input[type="submit"] {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.25s ease;
}

.form-actions input[type="submit"]:hover {
    background-color: #0056b3;
}

.message {
    margin-top: 15px;
    text-align: center;
    color: green;
    font-weight: bold;
    font-size: 16px;
}

</style>


<?php
$monan = new mon();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $insermonan = $monan->insert_mon($_POST, $_FILES);
}
?>

<div class="form-wrapper">
    <h2>Thêm Món Ăn</h2>
    <form action="productadd.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name_mon">Tên món</label>
            <input type="text" name="name_mon" placeholder="Nhập tên món..." required />
        </div>

        <div class="form-group">
            <label for="loaimon">Loại món</label>
            <select name="loaimon" required>
                <option value="">-- Chọn loại món --</option>
                <?php
                $loaimon = new loaimon();
                $listmon = $loaimon->show_loai();
                if ($listmon) {
                    while ($result = $listmon->fetch_assoc()) {
                        echo '<option value="' . $result['id_loai'] . '">' . $result['name_loai'] . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="gia">Giá</label>
            <input type="text" name="gia" placeholder="Nhập giá món ăn..." required />
        </div>

        <div class="form-group">
            <label for="ghichu">Ghi chú</label>
            <textarea name="ghichu" class="tinymce" placeholder="Nhập ghi chú nếu có..."></textarea>
        </div>

        <div class="form-group">
            <label for="image">Tải hình ảnh</label>
            <input type="file" name="image" required />
        </div>

        <div class="form-group">
            <label for="tinhtrang">Trạng thái</label>
            <select name="tinhtrang" required>
                <option value="">-- Chọn trạng thái --</option>
                <option value="1">Phục vụ</option>
                <option value="0">Ngưng phục vụ</option>
            </select>
        </div>

        <div class="form-actions">
            <input type="submit" name="submit" value="Lưu lại" />
        </div>

        <?php if (isset($insermonan)) echo "<div class='message'>$insermonan</div>"; ?>
    </form>
</div>
<?php include 'inc/footer.php'; ?>