<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/baiviet.php'; ?>

<?php
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

// Giả sử hàm này trả về MẢNG DỮ LIỆU bài viết nếu thành công, hoặc FALSE/NULL nếu thất bại.
$get_baiviet = $baiviet->get_baiviet_by_id($id);
?>

<!-- Import TinyMCE Editor: Dùng CDN và chèn API Key -->
<script src="https://cdn.tiny.cloud/1/id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    tinymce.init({
        // API Key của bạn
        api_key: 'id1i2zvdb4eh1a9cym2e8ca2xpqusdphnfe8rdg3j4bjmo9s', 

        // Áp dụng cho tất cả các textarea có class là tinymce
        selector: '.tinymce',
        
        // Cấu hình Z-INDEX RẤT QUAN TRỌNG: Khắc phục lỗi menu, hộp thoại bị ẩn sau sidebar/header
        z_index: 99999,
        
        // BỔ SUNG PLUGINS: Thêm các tính năng như màu chữ, căn lề, bảng, video, danh sách nâng cao.
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount formatpainter hr emoticons',
        
        // BỔ SUNG TOOLBAR: Thêm các nút điều khiển giống Word
        toolbar: 'undo redo | formatselect | ' +
                    'bold italic underline strikethrough | forecolor backcolor | ' + 
                    'alignleft aligncenter alignright alignjustify | ' + 
                    'bullist numlist outdent indent | ' + 
                    'link image media table | ' + 
                    'removeformat code fullscreen | help',
        
        // Tùy chỉnh Menubar (menu File, Edit, Insert...)
        menubar: 'file edit view insert format tools table help',
        
        // Cấu hình hiển thị
        height: 500, // Tăng chiều cao lên 500px
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px; line-height: 1.6; }'
    });
</script>

<style>
    /* Styling tương tự baivietadd.php cho sự đồng nhất */
    * { box-sizing: border-box; }
    .form-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 30px 40px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        font-family: 'Segoe UI', sans-serif;
    }
    .form-wrapper h2 {
        text-align: center;
        margin-bottom: 35px;
        font-size: 28px;
        color: #2c3e50;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 15px;
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #34495e;
    }
    input[type="text"], input[type="file"], select {
        width: 100%;
        padding: 12px;
        border: 1px solid #bdc3c7;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    .image-preview {
        max-width: 120px;
        height: auto;
        border: 1px solid #ccc;
        padding: 5px;
        margin-bottom: 10px;
        border-radius: 8px;
        display: block;
    }
    .form-actions input[type="submit"] {
        background-color: #3498db;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
        font-weight: bold;
        transition: background-color 0.3s, transform 0.1s;
    }
    .form-actions input[type="submit"]:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
    }
    .note {
        font-size: 13px;
        color: #7f8c8d;
        margin-top: 5px;
    }
    .error, .success {
        display: block;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: bold;
        text-align: center;
    }
    .error { background-color: #fdd; color: #c0392b; border: 1px solid #e74c3c; }
    .success { background-color: #dff; color: #27ae60; border: 1px solid #2ecc71; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-wrapper">
            <h2>Sửa Bài Viết</h2>
            <?php 
            if (isset($updateBaiviet)) {
                echo $updateBaiviet;
            }
            // KHẮC PHỤC LỖI: $get_baiviet đã là mảng dữ liệu (Array), không cần fetch_assoc()
            if ($get_baiviet):
                $result_baiviet = $get_baiviet;
            ?>
            
            <form action="baivietedit.php?baivietid=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="ten_baiviet">Tiêu đề Bài Viết (*)</label>
                    <input type="text" name="ten_baiviet" value="<?php echo htmlspecialchars($result_baiviet['ten_baiviet']); ?>" required />
                </div>

                <div class="form-group">
                    <label for="noidung_tongquan">Nội dung Tổng Quan (*)</label>
                    <textarea name="noidung_tongquan" class="tinymce" required><?php echo htmlspecialchars($result_baiviet['noidung_tongquan']); ?></textarea>
                </div>
                
                <div class="form-group" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background-color: #f9f9f9;">
                    <label>Ảnh Chính Hiện Tại</label>
                    <?php if (!empty($result_baiviet['anh_chinh'])): ?>
                        <img class="image-preview" src="../images/baiviet/<?php echo $result_baiviet['anh_chinh']; ?>" alt="Ảnh Chính">
                    <?php else: ?>
                        <span>(Không có ảnh chính)</span>
                    <?php endif; ?>
                    <label for="anh_chinh" style="margin-top: 10px;">Thay đổi Hình ảnh Chính (Nếu muốn)</label>
                    <input type="file" name="anh_chinh" />
                    <small class="note">Chọn file ảnh mới để thay thế ảnh hiện tại.</small>
                </div>

                <!-- Các phần Nội dung và Hình ảnh phụ -->
                <h3>Cập nhật Nội dung Chi Tiết (Tùy chọn)</h3>
                <?php for ($i = 1; $i <= 5; $i++): 
                    $noidung_key = 'noidung_' . $i;
                    $anh_key = 'anh_' . $i;
                ?>
                    <div class="form-group" style="border: 1px dashed #ccc; padding: 15px; border-radius: 8px;">
                        <label for="<?php echo $noidung_key; ?>">Nội dung Phần <?php echo $i; ?></label>
                        <textarea name="<?php echo $noidung_key; ?>" class="tinymce"><?php echo htmlspecialchars($result_baiviet[$noidung_key]); ?></textarea>
                        
                        <label style="margin-top: 15px;">Ảnh Kèm theo <?php echo $i; ?> Hiện Tại</label>
                        <?php if (!empty($result_baiviet[$anh_key])): ?>
                            <img class="image-preview" src="../images/baiviet/<?php echo $result_baiviet[$anh_key]; ?>" alt="Ảnh Kèm theo <?php echo $i; ?>">
                        <?php else: ?>
                            <span>(Không có ảnh kèm theo)</span>
                        <?php endif; ?>

                        <label for="<?php echo $anh_key; ?>" style="margin-top: 10px;">Thay đổi Hình ảnh Kèm theo <?php echo $i; ?> (Nếu muốn)</label>
                        <input type="file" name="<?php echo $anh_key; ?>" />
                    </div>
                <?php endfor; ?>

                <div class="form-actions" style="text-align: center; margin-top: 30px;">
                    <input type="submit" name="submit" value="Cập nhật Bài Viết" />
                </div>
            </form>
            <?php else: ?>
                <p class='error' style="margin-top: 20px;">Bài viết không tồn tại!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>