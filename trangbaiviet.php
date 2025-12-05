<?php
// FILE: trangbaiviet.php
include 'inc/header.php';
include 'classes/baiviet.php';
include_once 'helpers/format.php';

$baiviet = new baiviet();
$fm = new Format();

// 1. XỬ LÝ LỌC DANH MỤC
$catid = isset($_GET['catid']) ? (int)$_GET['catid'] : null;

// Gọi hàm show_baiviet với tham số lọc category
$all_baiviet = $baiviet->show_baiviet(0, $catid);

// Xác định tiêu đề trang
if ($catid) {
    $catName = $baiviet->get_category_name($catid);
    $section_title = "DANH MỤC: " . mb_strtoupper($catName, 'UTF-8');
} else {
    $section_title = "TIN TỨC MỚI NHẤT";
}

// 2. PHÂN TÁCH BÀI VIẾT (Featured vs Remaining)
$featured_posts = [];
$remaining_posts = [];

if ($all_baiviet && $all_baiviet->num_rows > 0) {
    $count = 0;
    while ($result = $all_baiviet->fetch_assoc()) {
        // Giả định người đăng
        $result['ten_admin'] = 'Triskiet Restaurant';
        // Lấy tên thể loại
        $result['cat_name'] = $baiviet->get_category_name($result['theloai']);

        // 2 bài đầu tiên sẽ hiển thị to
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
    /* --- HERO BANNER --- */
    .hero-wrap-2 {
        height: 400px;
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
        background: rgba(0, 0, 0, 0.5);
    }

    .hero-wrap-2 .slider-text {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        color: #fff;
        text-align: center;
    }

    .hero-wrap-2 .breadcrumbs a {
        color: rgba(255, 255, 255, 0.7);
        transition: color 0.3s;
    }

    .hero-wrap-2 .breadcrumbs a:hover {
        color: #fff;
    }

    /* --- SIDEBAR DANH MỤC --- */
    .cat-sidebar {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #eee;
        position: sticky;
        top: 20px;
        /* Giữ sidebar khi cuộn */
    }

    .cat-sidebar h4 {
        font-size: 18px;
        font-weight: 800;
        color: #E3242B;
        margin-bottom: 20px;
        border-bottom: 2px solid #E3242B;
        padding-bottom: 10px;
        text-transform: uppercase;
    }

    .cat-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .cat-list li {
        margin-bottom: 10px;
        border-bottom: 1px dashed #ddd;
        padding-bottom: 8px;
    }

    .cat-list li:last-child {
        border-bottom: none;
    }

    .cat-list li a {
        color: #555;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.2s;
    }

    .cat-list li a:hover {
        color: #E3242B;
        padding-left: 5px;
    }

    .cat-list li a i {
        font-size: 12px;
        color: #ccc;
    }

    /* --- LAYOUT TIN TỨC --- */
    .news-section-wrap {
        padding: 60px 0;
        background-color: #fff;
    }

    .section-title {
        font-size: 24px;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 30px;
        padding-bottom: 10px;
        display: block;
        border-bottom: 3px solid #E3242B;
        width: fit-content;
    }

    /* Tin nổi bật (To) */
    .featured-news-item {
        display: flex;
        gap: 25px;
        margin-bottom: 35px;
        border-bottom: 1px solid #eee;
        padding-bottom: 30px;
    }

    .featured-news-item:last-child {
        border-bottom: none;
    }

    .featured-image-wrap {
        min-width: 320px;
        width: 320px;
        height: 220px;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .featured-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .featured-news-item:hover .featured-image-wrap img {
        transform: scale(1.05);
    }

    .featured-content h2 {
        font-size: 22px;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 10px;
        line-height: 1.4;
    }

    .featured-content h2 a {
        text-decoration: none;
        color: #333;
        transition: color 0.2s;
    }

    .featured-content h2 a:hover {
        color: #E3242B;
    }

    .cat-badge {
        display: inline-block;
        background: #E3242B;
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: bold;
    }

    .featured-desc {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
        font-size: 15px;
    }

    .meta-info {
        font-size: 13px;
        color: #999;
        font-style: italic;
    }

    .meta-info a {
        color: #999;
        text-decoration: none;
    }

    /* Tin khác (Danh sách nhỏ) */
    .other-news-list {
        margin-top: 30px;
        border-top: 2px solid #f1f1f1;
        padding-top: 30px;
    }

    .other-news-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        border-bottom: 1px dashed #eee;
        padding-bottom: 15px;
    }

    .other-news-item:last-child {
        border-bottom: none;
    }

    .other-thumb {
        width: 100px;
        height: 70px;
        border-radius: 5px;
        object-fit: cover;
    }

    .other-content {
        flex: 1;
    }

    .other-content h5 {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 5px;
        line-height: 1.4;
    }

    .other-content h5 a {
        color: #333;
        text-decoration: none;
    }

    .other-content h5 a:hover {
        color: #E3242B;
    }

    @media(max-width: 768px) {
        .featured-news-item {
            flex-direction: column;
        }

        .featured-image-wrap {
            width: 100%;
            height: 200px;
            min-width: 100%;
        }
    }
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">TIN TỨC NHÀ HÀNG</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Tin Tức</span></p>
            </div>
        </div>
    </div>
</section>

<section class="news-section-wrap">
    <div class="container">
        <div class="row">

            <div class="col-md-3">
                <div class="cat-sidebar">
                    <h4>📂 DANH MỤC TIN</h4>
                    <ul class="cat-list">
                        <li>
                            <a href="trangbaiviet.php" style="<?php echo ($catid === null) ? 'color:#E3242B; font-weight:bold;' : ''; ?>">
                                Tất cả tin tức <i class="ion-ios-arrow-forward"></i>
                            </a>
                        </li>

                        <?php
                        $cats = $baiviet->get_all_categories();
                        foreach ($cats as $id => $name) {
                            // Kiểm tra nếu đang xem danh mục này thì active màu đỏ
                            $activeStyle = ($catid == $id) ? 'color:#E3242B; font-weight:bold;' : '';
                            echo "<li>
                                    <a href='trangbaiviet.php?catid=$id' style='$activeStyle'>
                                        $name <i class='ion-ios-arrow-forward'></i>
                                    </a>
                                  </li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-9">

                <?php if (!empty($featured_posts)): ?>
                    <span class="section-title"><?php echo $section_title; ?></span>

                    <div class="featured-news">
                        <?php foreach ($featured_posts as $result): ?>
                            <div class="featured-news-item">
                                <div class="featured-image-wrap">
                                    <a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                        <?php if (!empty($result['anh_chinh'])): ?>
                                            <img src="images/baiviet/<?php echo $result['anh_chinh']; ?>" alt="<?php echo htmlspecialchars($result['ten_baiviet']); ?>">
                                        <?php else: ?>
                                            <img src="https://placehold.co/600x400/333/fff?text=No+Image" alt="Không ảnh">
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="featured-content">
                                    <span class="cat-badge"><?php echo $result['cat_name']; ?></span>

                                    <h2><a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                            <?php echo htmlspecialchars($fm->textShorten($result['ten_baiviet'], 100)); ?>
                                        </a></h2>

                                    <p class="featured-desc"><?php echo $fm->textShorten($result['noidung_tongquan'], 160); ?></p>

                                    <div class="meta-info">
                                        <span><?php echo $result['ten_admin']; ?></span> |
                                        <span><?php echo date('d/m/Y', strtotime($result['ngay_tao'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($remaining_posts)): ?>
                    <div class="other-news-list">
                        <h4><?php echo ($catid) ? 'Bài viết khác cùng chuyên mục' : 'Các tin tức khác'; ?></h4>

                        <div class="row">
                            <?php foreach ($remaining_posts as $result): ?>
                                <div class="col-md-6">
                                    <div class="other-news-item">
                                        <a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                            <?php if (!empty($result['anh_chinh'])): ?>
                                                <img src="images/baiviet/<?php echo $result['anh_chinh']; ?>" class="other-thumb">
                                            <?php else: ?>
                                                <img src="https://placehold.co/100x70/ccc/999" class="other-thumb">
                                            <?php endif; ?>
                                        </a>
                                        <div class="other-content">
                                            <span style="font-size:10px; color:#E3242B; font-weight:bold; text-transform:uppercase;">
                                                <?php echo $result['cat_name']; ?>
                                            </span>
                                            <h5><a href="baivietchitiet.php?id=<?php echo $result['id_baiviet']; ?>">
                                                    <?php echo $fm->textShorten($result['ten_baiviet'], 55); ?>
                                                </a></h5>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($result['ngay_tao'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($featured_posts) && empty($remaining_posts)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ddd;">
                        <i class="ion-ios-paper-plane" style="font-size: 40px; color: #ccc;"></i>
                        <h3 style="color:#7f8c8d; margin-top: 15px; font-size: 18px;">Chưa có bài viết nào thuộc danh mục này.</h3>
                        <a href="trangbaiviet.php" class="btn btn-primary btn-sm mt-3">Xem tất cả tin tức</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>