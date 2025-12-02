<?php
include 'inc/header.php'; // Bao gồm header của trang web
include 'classes/baiviet.php'; 
include_once 'helpers/format.php'; 

$baiviet = new baiviet();
$fm = new Format();

// 1. Kiểm tra ID bài viết
if(!isset($_GET['id']) || $_GET['id']==NULL){
    echo "<script>window.location = '404.php'</script>";
}else{
    $id_baiviet = $_GET['id'];
}

// 2. Lấy chi tiết bài viết
$result_detail = $baiviet->get_baiviet_by_id($id_baiviet); 

if($result_detail){
    // Giả định tên người đăng
    $result_detail['ten_admin'] = 'Triskiet Restaurant';
}
?>

<style>
    /* ------------------------------------ */
    /* CSS CỦA HERO BANNER */
    /* ------------------------------------ */
    .hero-wrap-2 {
        height: 400px; /* Chiều cao cố định */
        background-position: center center;
        background-size: cover;
        position: relative;
    }
    .hero-wrap-2 .overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        content: '';
        background: rgba(0, 0, 0, 0.5); /* Độ mờ */
    }
    .hero-wrap-2 .slider-text {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        color: #fff;
    }
    .hero-wrap-2 .breadcrumbs a {
        color: rgba(255, 255, 255, 0.7);
        transition: color 0.3s;
    }
    .hero-wrap-2 .breadcrumbs a:hover {
        color: #fff;
    }
    
    /* ----------------------------------- */

    /* CSS CHUNG & BÀI VIẾT */
    /* ------------------------------------ */
    .post-detail-section {
        padding: 40px 0;
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa; 
    }
    /* Post container cũ được thay thế bằng padding và background cho post chính trong lưới */
    .post-container {
        padding: 0 20px;
        background-color: #ffffff;
    }
    
    .post-header {
        text-align: left;
        margin-bottom: 30px;
    }
    
    .post-header h1 {
        font-size: 32px;
        font-weight: 800;
        color: #1a1a1a;
        margin-top: 0;
        margin-bottom: 10px;
        line-height: 1.2;
    }
    
    .post-meta-info {
        font-size: 14px;
        color: #777;
        margin-top: 15px;
    }
    
    .post-meta-info .author-name {
        font-weight: 600;
        color: #E3242B; 
        margin-right: 15px;
    }
    
    .post-hero-image-wrap {
        width: 100%;
        max-height: 450px;
        overflow: hidden;
        margin-bottom: 30px;
        border-radius: 12px;
    }
    .post-hero-image-wrap img {
        width: 100%;
        object-fit: cover; /* Giữ cover để lấp đầy khung 450px và cắt ảnh */
        height: auto; /* Quan trọng: để giữ tỉ lệ */
        display: block;
    }

    .post-body p {
        font-size: 17px;
        line-height: 1.7;
        color: #333;
        margin-bottom: 25px;
        text-align: justify;
    }

    /* === Sửa đổi quan trọng: Đảm bảo tất cả thẻ <img> trong nội dung giới hạn 100% === */
    .post-body img {
        max-width: 100%; /* Giới hạn chiều rộng tối đa là 100% của khung chứa */
        height: auto; /* Giữ nguyên tỉ lệ ảnh */
        display: block; 
        margin: 20px auto; /* Canh giữa và tạo khoảng cách */
        border-radius: 8px; /* Làm đẹp thêm */
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
    }
    /* ================================================================================= */

    .post-body h2, .post-body h3 {
        color: #E3242B; 
        margin-top: 30px;
        margin-bottom: 15px;
        font-weight: 700;
        border-bottom: 2px solid #eee;
        padding-bottom: 5px;
    }

    .post-body .summary-intro {
        font-size: 18px; 
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 35px;
        padding-bottom: 20px;
        border-bottom: 1px dashed #ddd; 
        line-height: 1.6;
    }

    .post-body .post-inner-image-wrap {
        margin: 30px 0; 
        width: 100%;
        overflow: hidden;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
    }

    .post-body .post-inner-image-wrap img {
        max-width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
    }
    
    /* ------------------------------------ */
    /* CSS CỦA KHUNG BÀI VIẾT MỚI NHẤT */
    /* ------------------------------------ */
    .latest-posts-sidebar {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    .sidebar-title {
        font-size: 20px;
        font-weight: 700;
        color: #E3242B;
        margin-bottom: 20px;
        border-bottom: 3px solid #E3242B;
        padding-bottom: 10px;
    }

    .latest-post-item {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 6px;
        transition: background-color 0.2s, border-color 0.2s;
        display: block; 
    }
    .latest-post-item:hover {
        background-color: #f7f7f7;
        border-color: #E3242B;
        text-decoration: none;
    }
    .latest-post-item img {
        width: 100%;
        height: 80px;
        object-fit: cover;
    }
    .latest-post-title {
        font-weight: 600;
        color: #333;
        padding-left: 10px;
        line-height: 1.3;
    }
    @media (max-width: 991.98px) {
        .latest-posts-sidebar {
            margin-top: 40px; 
        }
    }
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');"
    data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <div class="slider-text">
                    <h1 class="mb-2 bread">Chi Tiết Tin Tức</h1>
                    <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i
                                class="ion-ios-arrow-forward"></i></a></span> <span> Bài Viết <i
                                class="ion-ios-arrow-forward"></i></span></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="post-detail-section">
    <div class="container" style="max-width: 1300px;"> 
        <?php if ($result_detail): ?>
            
            <p style="margin-bottom: 20px;">
                <a href="trangbaiviet.php" class="btn btn-outline-secondary">
                    <i class="ion-ios-arrow-back"></i> Quay lại Tin Tức
                </a>
            </p>

            <div class="row">
                <div class="col-lg-9 col-md-13"> 
                    <div class="post-container" style="max-width: none; margin: 0; padding: 30px; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.05);">
                        
                        <div class="post-header">
                            <h1><?php echo htmlspecialchars($result_detail['ten_baiviet']); ?></h1>
                            
                            <div class="post-meta-info">
                                <span class="author-name"><?php echo htmlspecialchars($result_detail['ten_admin']); ?></span> 
                                <span><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($result_detail['ngay_tao'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="post-hero-image-wrap">
                            <?php if (!empty($result_detail['anh_chinh'])): ?>
                                <img src="images/baiviet/<?php echo $result_detail['anh_chinh']; ?>" alt="<?php echo htmlspecialchars($result_detail['ten_baiviet']); ?>">
                            <?php else: ?>
                                <img src="https://placehold.co/800x450/3498db/ffffff?text=Tin+Tuc+Moi+Nhat" alt="Không có ảnh">
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-body">
                            <?php 
                                $content_found = false;
                                
                                // A. Hiển thị Nội dung tổng quan (noidung_tongquan)
                                if (!empty($result_detail['noidung_tongquan'])) {
                                    echo '<p class="summary-intro">' . $result_detail['noidung_tongquan'] . '</p>';
                                    $content_found = true;
                                }
                                
                                // B. Lặp qua các khối nội dung (ảnh_i -> noidung_i)
                                for ($i = 1; $i <= 5; $i++) {
                                    $image_field = 'anh_' . $i;
                                    $content_field = 'noidung_' . $i;
                                    
                                    $has_image = !empty($result_detail[$image_field]);
                                    $has_content = !empty($result_detail[$content_field]);
                                    
                                    // 1. Hiển thị ảnh (anh_1, anh_2, ...) trước
                                    if ($has_image) {
                                        $image_path = "images/baiviet/" . $result_detail[$image_field];
                                        echo '<div class="post-inner-image-wrap">';
                                        echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($result_detail['ten_baiviet']) . ' - Phần ' . $i . '">';
                                        echo '</div>';
                                        $content_found = true;
                                    }
                                    
                                    // 2. Hiển thị nội dung (noidung_1, noidung_2, ...) sau
                                    if ($has_content) {
                                        echo $result_detail[$content_field]; 
                                        $content_found = true;
                                    }
                                }
                                
                                // C. Thông báo nếu không có nội dung nào
                                if (!$content_found) {
                                    echo '<p>Nội dung chi tiết của bài viết này đang được cập nhật.</p>';
                                }
                            ?>
                        </div>

                    </div>
                </div>

                <div class="col-lg-3 col-md-13">
                    <div class="latest-posts-sidebar">
                        <h4 class="sidebar-title">Bài Viết Mới Nhất 📰</h4>
                        <div class="list-group">
                            <?php
                            // 3. Gọi hàm lấy 5 bài viết mới nhất
                            $latest_posts = $baiviet->get_latest_posts(5); 
                            
                            if ($latest_posts) {
                                while ($row = $latest_posts->fetch_assoc()) {
                                    // Bỏ qua bài viết đang xem để tránh lặp lại
                                    if ($row['id_baiviet'] == $id_baiviet) continue;
                                    ?>
                                    <a href="baivietchitiet.php?id=<?php echo $row['id_baiviet']; ?>" class="latest-post-item list-group-item list-group-item-action">
                                        <div class="row no-gutters">
                                            <div class="col-4" style="padding-right: 10px;">
                                                <?php if (!empty($row['anh_chinh'])): ?>
                                                <img src="images/baiviet/<?php echo $row['anh_chinh']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($row['ten_baiviet']); ?>">
                                                <?php else: ?>
                                                <img src="https://placehold.co/80x80/cccccc/333333?text=New" class="img-fluid rounded" alt="No Image">
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-8">
                                                <p class="latest-post-title mb-1" style="padding-left: 0;"><?php echo $fm->textShorten(htmlspecialchars($row['ten_baiviet']), 50); ?></p>
                                                <small class="text-muted"><span><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($result_detail['ngay_tao'])); ?></span></small>
                                            </div>
                                        </div>
                                    </a>
                                    <?php
                                }
                            } else {
                                echo '<p class="text-center text-muted mt-3">Chưa có bài viết mới nào được công khai.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div> <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <p style="font-size: 18px; color: #e74c3c;">Bài viết này không tồn tại hoặc đã bị ẩn.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'inc/footer.php'; ?>