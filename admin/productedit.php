<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/loaimon.php'; ?>
<?php include '../classes/mon.php'; ?>

<style>
    .form-wrapper {
        max-width: 750px;
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
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 15px;
        transition: border 0.2s ease;
        background-color: #fff;
    }

    .form-group textarea {
        min-height: 100px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #007bff;
        outline: none;
    }

    .form-group img.image-preview {
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

<?php
$monan = new mon();
if (!isset($_GET['id_mon']) || $_GET['id_mon'] == NULL) {
    echo "<script>window.location = 'productlist.php'</script>";
    exit();
} else {
    $id = $_GET['id_mon'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $monan->update_mon($_POST, $_FILES, $id);
}
?>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Sửa món ăn</h2>
            <?php
            $getmonbyid = $monan->getmonbyid($id);
            if ($getmonbyid) {
                while ($result_mon = $getmonbyid->fetch_assoc()) {
            ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Tên món</label>
                            <input type="text" name="name_mon" required value="<?php echo $result_mon['name_mon']; ?>" />
                        </div>

                        <div class="form-group">
                            <label>Loại món</label>
                            <select name="loaimon" required>
                                <option value="">-----Chọn loại món-----</option>
                                <?php
                                $loaimon = new loaimon();
                                $listmon = $loaimon->show_loai();
                                if ($listmon) {
                                    while ($result = $listmon->fetch_assoc()) {
                                        $selected = ($result['id_loai'] == $result_mon['id_loai']) ? 'selected' : '';
                                        echo '<option ' . $selected . ' value="' . $result['id_loai'] . '">' . $result['name_loai'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Giá</label>
                            <input type="text" name="gia" required value="<?php echo $result_mon['gia_mon']; ?>" />
                        </div>

                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea name="ghichu"><?php echo trim($result_mon['ghichu_mon']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Ảnh món hiện tại</label>
                            <img class="image-preview" src="../images/food/<?php echo $result_mon['images']; ?>" alt="Ảnh món ăn">
                            <input type="file" name="image" />
                        </div>

                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="tinhtrang" required>
                                <option value="">----Chọn trạng thái-----</option>
                                <option value="1" <?php if ($result_mon['tinhtrang'] == 1) echo 'selected'; ?>>Phục vụ</option>
                                <option value="0" <?php if ($result_mon['tinhtrang'] == 0) echo 'selected'; ?>>Ngưng phục vụ</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <input type="submit" name="submit" value="Cập nhật món ăn" />
                        </div>
                    </form>
            <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- TinyMCE nếu cần -->
<script src="js/tiny-mce/jquery.tinymce.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function () {
        setupTinyMCE();
    });
</script>

<?php include 'inc/footer.php'; ?>
