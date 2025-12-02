<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/nguyenvatlieu.php'; ?>

<?php
$nl = new nguyenvatlieu();

// Lấy id nguyên liệu
if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = 'nguyenlieu_list.php'</script>";
    exit();
} else {
    $id = (int)$_GET['id'];
}

// Xử lý submit form
$updateSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $ten_nl       = $_POST['ten_nl']       ?? '';
    $don_vi       = $_POST['don_vi']       ?? '';
    $so_luong_ton = $_POST['so_luong_ton'] ?? 0;
    $gia_nhap_tb  = $_POST['gia_nhap_tb']  ?? 0;
    $ghichu       = $_POST['ghichu']       ?? '';

    $updateMsg = $nl->update_nguyen_lieu($id, $ten_nl, $don_vi, $so_luong_ton, $gia_nhap_tb, $ghichu);

    if ($updateMsg) {
        $updateSuccess = true;
    }
}
?>

<style>
    .form-wrapper {
        max-width: 750px;
        margin: 40px auto;
        padding: 30px 40px;
        background-color: #fff;
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
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    .form-group input, .form-group textarea {
        width: 100%; padding: 10px 14px;
        border-radius: 8px; border: 1px solid #ccc;
        font-size: 15px; background-color: #fff;
    }
    .form-actions { text-align: center; margin-top: 25px; }
    .btn-main {
        background-color: #007bff; color: white;
        padding: 10px 30px; font-size: 16px;
        border: none; border-radius: 8px;
        cursor: pointer;
        margin-right: 12px;
    }
    .btn-main:hover { background-color: #0056b3; }
    .btn-back {
        background-color: #6c757d; color: white;
        padding: 10px 22px; font-size: 15px;
        border: none; border-radius: 8px;
        cursor: pointer;
    }
    .btn-back:hover { background-color: #5a6268; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Sửa nguyên vật liệu</h2>

            <?php
            $getNL = $nl->get_nguyen_lieu($id);
            if ($getNL && $row = $getNL->fetch_assoc()):
            ?>

            <form action="" method="post">

                <div class="form-group">
                    <label>Tên nguyên liệu</label>
                    <input type="text" name="ten_nl"
                        value="<?= htmlspecialchars($row['ten_nl'] ?? '', ENT_QUOTES) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Đơn vị tính</label>
                    <input type="text" name="don_vi"
                        value="<?= htmlspecialchars($row['don_vi'] ?? '', ENT_QUOTES) ?>"
                        placeholder="kg, l, ml, quả..."
                        required>
                </div>

                <div class="form-group">
                    <label>Tồn kho</label>
                    <input type="number" name="so_luong_ton" step="0.01" min="0"
                        value="<?= htmlspecialchars($row['so_luong_ton'] ?? 0, ENT_QUOTES) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Giá nhập trung bình (VNĐ)</label>
                    <input type="number" name="gia_nhap_tb" step="100" min="0"
                        value="<?= htmlspecialchars($row['gia_nhap_tb'] ?? 0, ENT_QUOTES) ?>">
                </div>

                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghichu"><?= htmlspecialchars($row['ghichu'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <input type="submit" name="submit" class="btn-main" value="Cập nhật">
                    <a href="nguyenlieu_list.php"><button type="button" class="btn-back">Quay lại</button></a>

                    <?php
                        if ($updateSuccess) {
                            echo "<script>
                                alert('Cập nhật nguyên liệu thành công!');
                                window.location = 'nguyenlieu_list.php';
                            </script>";
                            exit();
                        }
                    ?>
                </div>

            </form>

            <?php else: ?>
                <p style="color:red;text-align:center;">Không tìm thấy nguyên liệu.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
