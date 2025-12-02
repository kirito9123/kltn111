<?php
include 'inc/header.php'; // Giả định có header/footer của frontend
include 'classes/baiviet.php'; 
include_once 'helpers/format.php'; 

$baiviet = new baiviet();
$fm = new Format();
$all_baiviet = $baiviet->show_baiviet(0); // Lấy TẤT CẢ các bài viết chưa ẩn

// Tách 2 bài viết mới nhất (Dành cho phần TIN TỨC MỚI NHẤT/FEATURED)
$featured_posts = [];
$remaining_posts = [];

if ($all_baiviet && $all_baiviet->num_rows > 0) {
    $count = 0;
    while ($result = $all_baiviet->fetch_assoc()) {
        // Mặc định người đăng là Triskiet Restaurant
        $result['ten_admin'] = 'Triskiet Restaurant'; 

        if ($count < 2) {
            $featured_posts[] = $result;
        } else {
            $remaining_posts[] = $result;
        }
        $count++;
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
    /* CSS Hero Banner (Đảm bảo có CSS này nếu nó không nằm trong file style chung) */
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

    /* Tổng thể & Font */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #ffffff; /* Nền trắng như ảnh mẫu */
        color: #333;
    }
    .news-section-wrap {
        /* Đặt nội dung vào section để cách ly với header */
        padding: 50px 0; 
        background-color: #ffffff;
    }
    .main-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Tiêu đề chính */
    .section-title {
        font-size: 24px;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 30px;
        padding-bottom: 5px;
        display: block;
        border-bottom: 3px solid #E3242B; /* Màu đỏ nổi bật cho tiêu đề TIN TỨC MỚI NHẤT */
        width: fit-content;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Bố cục Tin tức Nổi bật (Giống như 2 bài đầu trong ảnh) */
    .featured-news-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #e0e0e0;
    }

    .featured-news-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .featured-image-wrap {
        min-width: 300px; /* Chiều rộng cố định cho ảnh */
        width: 300px; 
        height: 200px;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .featured-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .featured-news-item:hover .featured-image-wrap img {
        transform: scale(1.05);
    }

    .featured-content {
        flex-grow: 1;
        padding-top: 5px;
    }

    .featured-content h2 {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .featured-content h2 a {
        text-decoration: none;
        color: inherit;
        transition: color 0.2s;
    }
    
    .featured-content h2 a:hover {
        color: #000000ff; /* Hover màu đỏ */
    }

    .featured-content p {
        font-size: 15px;
        line-height: 1.6;
        color: #555;
        margin-bottom: 15px;
    }

    .featured-meta {
        font-size: 13px;
        color: #888;
        display: flex;
        align-items: center;
    }
    
    .featured-meta a {
        font-weight: 600;
        color: #3498db;
        margin-right: 15px;
        text-decoration: none;
    }
    
    .featured-meta a:hover {
        text-decoration: underline;
    }
    
    .featured-meta span {
        font-style: italic;
    }

    /* Các bài viết khác */
    .other-news-list {
        margin-top: 40px;
        border-top: 1px solid #e0e0e0;
        padding-top: 30px;
    }
    
    .other-news-list h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 20px;
        border-left: 5px solid #3498db;
        padding-left: 10px;
    }

    .other-news-item {
        padding: 15px 0;
        border-bottom: 1px dashed #eee;
    }

    .other-news-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .other-news-item a {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        text-decoration: none;
        transition: color 0.2s;
    }

    .other-news-item a:hover {
        color: #3498db;
    }

    .other-news-meta {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .featured-news-item {
            flex-direction: column;
        }
        .featured-image-wrap {
            width: 100%;
            height: 250px;
            min-width: unset;
        }
        .section-title {
            font-size: 20px;
        }
    }
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');"
    data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">TIN TỨC CỦA NHÀ HÀNG</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i
                    class="ion-ios-arrow-forward"></i></a></span> <span> Tin Tức <i
                    class="ion-ios-arrow-forward"></i></span></p>
                </div>
            </div>
        </div>
</section>
<section class="news-section-wrap">
    <div class="main-container">
        <?php if (!empty($featured_posts)): ?>
            
            <span class="section-title">TIN TỨC MỚI NHẤT</span>
            
            <div class="featured-news">
                <?php foreach ($featured_posts as $result): ?>
                    <div class="featured-news-item">
                        <div class="featured-image-wrap">
                            <?php if (!empty($result['anh_chinh'])): ?>
                                <a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                    <img src="images/baiviet/<?php echo $result['anh_chinh']; ?>" alt="<?php echo htmlspecialchars($result['ten_baiviet']); ?>">
                                </a>
                            <?php else: ?>
                                <img src="https://placehold.co/600x400/1a1a1a/ffffff?text=Triskiet+News" alt="Không có ảnh">
                            <?php endif; ?>
                        </div>
                        <div class="featured-content">
                            <h2><a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                <?php echo htmlspecialchars($fm->textShorten($result['ten_baiviet'], 100)); ?>
                            </a></h2>
                            <p><?php echo $fm->textShorten($result['noidung_tongquan'], 250); ?></p>
                            <div class="featured-meta">
                                <a href="#"><?php echo htmlspecialchars($result['ten_admin']); ?></a> 
                                <span><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($result['ngay_tao'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($remaining_posts)): ?>
            
            <div class="other-news-list">
                <h3>Các Tin Tức & Bài Viết Khác</h3>
                
                <?php foreach ($remaining_posts as $result): ?>
                    <div class="other-news-item">
                        <a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                            <?php echo htmlspecialchars($fm->textShorten($result['ten_baiviet'], 120)); ?>
                        </a>
                        <div class="other-news-meta">
                            <span>Người đăng: **<?php echo htmlspecialchars($result['ten_admin']); ?>**</span> |
                            <span>Ngày đăng: <?php echo date('d/m/Y', strtotime($result['ngay_tao'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php elseif (empty($featured_posts)): ?>
            <p style="text-align: center; font-size: 18px; color: #7f8c8d; margin-top: 50px;">Hiện chưa có bài viết nào được đăng.</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'inc/footer.php'; ?>