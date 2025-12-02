<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/baiviet.php'; 

$baiviet = new baiviet();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $insertBaiviet = $baiviet->insert_baiviet($_POST, $_FILES);
}
?>

<!-- Import TinyMCE Editor: Dùng CDN và chèn API Key để loại bỏ cảnh báo -->
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
    /* Styling để form nhìn đẹp và rõ ràng hơn */
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
    input[type="text"]:focus, select:focus {
        border-color: #3498db;
        outline: none;
    }
    .form-actions input[type="submit"] {
        background-color: #2ecc71;
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
        background-color: #27ae60;
        transform: translateY(-2px);
    }
    .file-group {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .file-group input[type="file"] {
        width: auto;
        flex-grow: 1;
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
            <h2>Thêm Bài Viết Mới</h2>
            <?php 
            // KHÔNG CÓ KÝ TỰ/KHOẢNG TRẮNG NÀO Ở ĐÂY
            if (isset($insertBaiviet)) {
                echo $insertBaiviet;
            }
            // KHÔNG CÓ KÝ TỰ/KHOẢNG TRẮNG NÀO Ở ĐÂY
            ?>
            
            <form action="baivietadd.php" method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="ten_baiviet">Tiêu đề Bài Viết (*)</label>
                    <input type="text" name="ten_baiviet" placeholder="Nhập tiêu đề bài viết..." required />
                </div>

                <div class="form-group">
                    <label for="noidung_tongquan">Nội dung Tổng Quan (*)</label>
                    <textarea name="noidung_tongquan" class="tinymce" placeholder="Nhập nội dung tóm tắt hoặc tổng quan (Sẽ hiển thị ở trang danh sách)..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="anh_chinh">Hình ảnh Chính (*)</label>
                    <input type="file" name="anh_chinh" required />
                    <small class="note">Đây là ảnh đại diện (thumbnail) của bài viết.</small>
                </div>

                <!-- Các phần Nội dung và Hình ảnh phụ (Tùy chọn) -->
                <h3>Nội dung Chi Tiết (Tùy chọn)</h3>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="form-group" style="border: 1px dashed #ddd; padding: 15px; border-radius: 8px;">
                        <label for="noidung_<?php echo $i; ?>">Nội dung Phần <?php echo $i; ?></label>
                        <textarea name="noidung_<?php echo $i; ?>" class="tinymce" placeholder="Nhập nội dung chi tiết phần <?php echo $i; ?>..."></textarea>
                        
                        <label for="anh_<?php echo $i; ?>" style="margin-top: 15px;">Hình ảnh Kèm theo <?php echo $i; ?></label>
                        <input type="file" name="anh_<?php echo $i; ?>" />
                        <small class="note">Ảnh này sẽ hiển thị kèm Nội dung Phần <?php echo $i; ?>.</small>
                    </div>
                <?php endfor; ?>

                <div class="form-actions" style="text-align: center; margin-top: 30px;">
                    <input type="submit" name="submit" value="Lưu và Đăng Bài" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
