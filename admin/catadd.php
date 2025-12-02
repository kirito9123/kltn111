<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/loaimon.php';

// Kiểm tra đăng nhập
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

// Lấy quyền hạn (ép kiểu để so sánh chính xác)
$level = (int) Session::get('adminlevel');

// Chỉ cho phép admin (0) và bếp (3) truy cập
if ($level !== 0 && $level !== 3) {
    echo "<script>
        alert('Bạn không có quyền truy cập! Chỉ quản trị viên hoặc nhân viên bếp mới được phép.');
        window.location.href = 'index.php';
    </script>";
    exit();
}
?>



<style>
    .form-container {
        max-width: 600px;
        margin: auto;
        padding: 25px;
        background-color: #f7f7f7;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    .form-group label {
        margin-bottom: 5px;
        font-weight: bold;
        color: #444;
    }

    .form-group input {
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 16px;
    }

    .form-actions {
        text-align: center;
        margin-top: 20px;
    }

    .form-actions input[type="submit"] {
        padding: 10px 30px;
        background-color: #28a745;
        color: white;
        border: none;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #218838;
    }

    .message {
        text-align: center;
        color: #28a745;
        font-weight: bold;
        margin-bottom: 15px;
    }
</style>

<?php
    $loai = new loaimon();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tenloai = $_POST['tenloai'];
        $ghichu = $_POST['ghichu'];
        $insertloai = $loai->insert_loai($tenloai, $ghichu);
    }
?>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>Thêm Loại Món</h2>

            <?php if (isset($insertloai)) : ?>
                <div class="message"><?php echo $insertloai; ?></div>
            <?php endif; ?>

            <form action="catadd.php" method="post">
                <div class="form-group">
                    <label for="tenloai">Tên loại món</label>
                    <input type="text" name="tenloai" id="tenloai" placeholder="Nhập tên loại món" required />
                </div>

                <div class="form-group">
                    <label for="ghichu">Ghi chú</label>
                    <input type="text" name="ghichu" id="ghichu" placeholder="Nhập ghi chú (nếu có)" />
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" value="Lưu lại" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
