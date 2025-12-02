<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/khuyenmai.php'; ?>

<?php
$km = new khuyenmai();
if (!isset($_GET['id_km']) || $_GET['id_km'] == NULL) {
    echo "<script>window.location = 'kmlist.php'</script>";
    exit();
} else {
    $id = $_GET['id_km'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $updatekm = $km->update_km($_POST, $_FILES, $id);
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
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="number"],
    .form-group input[type="file"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 15px;
        background-color: #fff;
        transition: border 0.2s ease;
    }
    .form-group textarea {
        min-height: 100px;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #007bff;
        outline: none;
    }
    .image-preview {
        margin-top: 8px;
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
    .form-actions {
        text-align: center;
        margin-top: 30px;
    }
    .form-actions input[type="submit"] {
        background-color: #007bff;
        color: white;
        padding: 10px 30px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .form-actions input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Sửa khuyến mãi</h2>
            <?php
            $getkmbyid = $km->getkmbyid($id);
            if ($getkmbyid && $result_km = $getkmbyid->fetch_assoc()):
            ?>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Tên khuyến mãi</label>
                    <input type="text" name="name_km" value="<?php echo htmlspecialchars($result_km['name_km']); ?>" readonly onclick="return confirm('Không được đổi tên?')">
                </div>
                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="date" name="time_star" value="<?php echo $result_km['time_star']; ?>">
                </div>
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" name="time_end" value="<?php echo $result_km['time_end']; ?>">
                </div>
                <div class="form-group">
                    <label>Giảm giá (%)</label>
                    <input type="number" name="discout" min="1" max="100" value="<?php echo $result_km['discout']; ?>">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghichu" class="tinymce"><?php echo trim($result_km['ghichu']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Hình ảnh hiện tại</label><br>
                    <img class="image-preview" src="../images/food/<?php echo $result_km['images']; ?>" alt="Hình khuyến mãi">
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Cập nhật">
                    <?php if (isset($updatekm)) echo "<div style='margin-top:15px;'>$updatekm</div>"; ?>
                </div>
            </form>
            <?php else: ?>
                <p style="color: red; text-align: center;">Không tìm thấy thông tin khuyến mãi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="js/tiny-mce/jquery.tinymce.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function () {
        setupTinyMCE();
    });
</script>

<?php include 'inc/footer.php'; ?>