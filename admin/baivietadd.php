<?php
// FILE: admin/baivietadd.php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/baiviet.php';

$baiviet = new baiviet();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $insertBaiviet = $baiviet->insert_baiviet($_POST, $_FILES);
}
?>

<script src="https://cdn.tiny.cloud/1/id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        api_key: 'id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s',
        selector: '.tinymce',
        z_index: 99999,
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
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    input[type="text"],
    select,
    input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .form-actions input[type="submit"] {
        background: #28a745;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
    }

    .form-actions input[type="submit"]:hover {
        background: #218838;
    }

    .error {
        color: red;
        font-weight: bold;
    }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2 style="text-align:center; color:#007bff; margin-bottom:20px;">Thêm Bài Viết Mới</h2>

            <?php if (isset($insertBaiviet)) echo $insertBaiviet; ?>

            <form action="baivietadd.php" method="post" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="ten_baiviet">Tiêu đề Bài Viết (*)</label>
                    <input type="text" name="ten_baiviet" placeholder="Nhập tiêu đề..." required />
                </div>

                <div class="form-group">
                    <label for="theloai">Thể loại Tin (*)</label>
                    <select name="theloai" required>
                        <option value="">-- Chọn thể loại --</option>
                        <?php
                        $cats = $baiviet->get_all_categories();
                        foreach ($cats as $id => $name) {
                            echo "<option value='$id'>$name</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="noidung_tongquan">Nội dung Tổng Quan (*)</label>
                    <textarea name="noidung_tongquan" class="tinymce"></textarea>
                </div>

                <div class="form-group">
                    <label for="anh_chinh">Hình ảnh Chính (Thumbnail) (*)</label>
                    <input type="file" name="anh_chinh" required />
                </div>

                <hr style="margin: 30px 0; border-top: 1px dashed #ccc;">
                <h3 style="color:#666;">Nội dung Chi Tiết (Tùy chọn các phần)</h3>

                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="form-group" style="background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #eee;">
                        <label>Nội dung Phần <?php echo $i; ?></label>
                        <textarea name="noidung_<?php echo $i; ?>" class="tinymce"></textarea>
                        <label style="margin-top:10px;">Ảnh Kèm theo Phần <?php echo $i; ?></label>
                        <input type="file" name="anh_<?php echo $i; ?>" />
                    </div>
                <?php endfor; ?>

                <div class="form-actions" style="text-align: center; margin-top: 30px;">
                    <input type="submit" name="submit" value="Lưu Bài Viết" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>