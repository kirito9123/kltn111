<?php
// FILE: admin/baivietedit.php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/baiviet.php';

if (!isset($_GET['baivietid']) || $_GET['baivietid'] == NULL) {
    echo "<script>window.location.href = 'baivietlist.php';</script>";
    exit();
} else {
    $id = $_GET['baivietid'];
}

$baiviet = new baiviet();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $updateBaiviet = $baiviet->update_baiviet($_POST, $_FILES, $id);
}

$result_baiviet = $baiviet->get_baiviet_by_id($id);
?>

<script src="https://cdn.tiny.cloud/1/id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        api_key: 'id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s',
        selector: '.tinymce',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        height: 400
    });
</script>

<style>
    .form-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 30px;
        background: #fff;
        border-radius: 8px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }

    input[type="text"],
    select,
    input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .img-preview {
        max-width: 150px;
        margin-top: 10px;
        border: 1px solid #ccc;
        padding: 3px;
        border-radius: 5px;
    }

    .form-actions input[type="submit"] {
        background: #007bff;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2 style="text-align:center; color:#007bff;">Sửa Bài Viết</h2>
            <?php if (isset($updateBaiviet)) echo $updateBaiviet; ?>

            <?php if ($result_baiviet): ?>
                <form action="" method="post" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Tiêu đề Bài Viết (*)</label>
                        <input type="text" name="ten_baiviet" value="<?php echo htmlspecialchars($result_baiviet['ten_baiviet']); ?>" required />
                    </div>

                    <div class="form-group">
                        <label>Thể loại Tin (*)</label>
                        <select name="theloai" required>
                            <?php
                            $cats = $baiviet->get_all_categories();
                            foreach ($cats as $key => $val) {
                                $selected = ($result_baiviet['theloai'] == $key) ? 'selected' : '';
                                echo "<option value='$key' $selected>$val</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nội dung Tổng Quan (*)</label>
                        <textarea name="noidung_tongquan" class="tinymce"><?php echo $result_baiviet['noidung_tongquan']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Ảnh Chính Hiện Tại</label>
                        <?php if (!empty($result_baiviet['anh_chinh'])): ?>
                            <img src="../images/baiviet/<?php echo $result_baiviet['anh_chinh']; ?>" class="img-preview">
                        <?php endif; ?>
                        <label style="margin-top:10px;">Thay đổi Ảnh Chính (Nếu muốn)</label>
                        <input type="file" name="anh_chinh" />
                    </div>

                    <hr>
                    <h3>Nội dung Chi Tiết</h3>
                    <?php for ($i = 1; $i <= 5; $i++):
                        $contentKey = 'noidung_' . $i;
                        $imgKey = 'anh_' . $i;
                    ?>
                        <div class="form-group" style="background: #f9f9f9; padding: 15px; border: 1px solid #eee; margin-top:15px;">
                            <label>Nội dung Phần <?php echo $i; ?></label>
                            <textarea name="<?php echo $contentKey; ?>" class="tinymce"><?php echo $result_baiviet[$contentKey]; ?></textarea>

                            <div style="margin-top:10px;">
                                <label>Ảnh Phần <?php echo $i; ?></label>
                                <?php if (!empty($result_baiviet[$imgKey])): ?>
                                    <br><img src="../images/baiviet/<?php echo $result_baiviet[$imgKey]; ?>" class="img-preview">
                                <?php endif; ?>
                                <input type="file" name="<?php echo $imgKey; ?>" style="margin-top:5px;" />
                            </div>
                        </div>
                    <?php endfor; ?>

                    <div class="form-actions" style="text-align: center; margin-top: 30px;">
                        <input type="submit" name="submit" value="Cập nhật Bài Viết" />
                    </div>
                </form>
            <?php else: ?>
                <p class="error">Không tìm thấy bài viết.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>