<?php
// FILE: baivietchitiet.php
include 'inc/header.php';
include 'classes/baiviet.php';
include_once 'helpers/format.php';

$baiviet = new baiviet();
$fm = new Format();

if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = '404.php'</script>";
} else {
    $id_baiviet = $_GET['id'];
}

$result_detail = $baiviet->get_baiviet_by_id($id_baiviet);

if ($result_detail) {
    $result_detail['ten_admin'] = 'Triskiet Restaurant';
    // Lấy tên thể loại
    $cat_name = $baiviet->get_category_name($result_detail['theloai']);
}
?>

<style>
    /* Hero Banner */
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
    }

    .hero-wrap-2 .breadcrumbs a:hover {
        color: #fff;
    }

    /* Post Content */
    .post-detail-section {
        padding: 50px 0;
        background-color: #f8f9fa;
        font-family: 'Times New Roman', serif;
    }

    /* Tăng padding cho nội dung thoáng hơn */
    .post-container {
        background: #fff;
        padding: 50px;
        border-radius: 8px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    }

    .post-header h1 {
        font-size: 36px;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 15px;
        line-height: 1.3;
    }

    .post-meta-info {
        font-size: 15px;
        color: #777;
        margin-top: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }

    .post-meta-info span {
        margin-right: 15px;
    }

    .author-name {
        font-weight: bold;
        color: #E3242B;
    }

    /* Tăng chiều cao ảnh cover */
    .post-hero-image-wrap img {
        width: 100%;
        max-height: 600px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .post-body p {
        font-size: 19px;
        line-height: 1.8;
        color: #333;
        margin-bottom: 25px;
        text-align: justify;
    }

    .post-body img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 30px auto;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .post-body h2,
    .post-body h3 {
        color: #E3242B;
        margin-top: 35px;
        font-weight: 700;
    }

    /* Sidebar Recent Posts */
    .latest-posts-sidebar {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .sidebar-title {
        font-size: 18px;
        font-weight: 700;
        color: #E3242B;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }

    .latest-post-item {
        display: flex;
        margin-bottom: 15px;
        text-decoration: none;
        color: #333;
        transition: 0.2s;
    }

    .latest-post-item:hover {
        color: #E3242B;
    }

    .lp-img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
    }

    .lp-info p {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        line-height: 1.3;
    }

    .lp-info small {
        color: #999;
        font-size: 12px;
    }

    /* Category Tag */
    .cat-label {
        background-color: #E3242B;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 13px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        margin-left: 15px;
        font-family: sans-serif;
    }

    .cat-label i {
        margin-right: 5px;
    }
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">CHI TIẾT BÀI VIẾT</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ</a></span> <span>Tin Tức</span></p>
            </div>
        </div>
    </div>
</section>

<section class="post-detail-section">
    <div class="container" style="max-width: 1400px;">
        <?php if ($result_detail): ?>
            <div class="row">
                <div class="col-lg-9">
                    <div class="post-container">

                        <div style="margin-bottom: 25px; display: flex; align-items: center;">
                            <a href="trangbaiviet.php" class="btn btn-outline-dark btn-sm" style="border-radius: 20px; padding: 5px 15px;">
                                <i class="ion-ios-arrow-back"></i> Quay lại
                            </a>

                            <span class="cat-label">
                                <i class="fa fa-folder-open"></i> <?php echo $cat_name; ?>
                            </span>
                        </div>

                        <div class="post-header">
                            <h1><?php echo $result_detail['ten_baiviet']; ?></h1>
                            <div class="post-meta-info">
                                <span class="author-name"><i class="fa fa-user"></i> <?php echo $result_detail['ten_admin']; ?></span>
                                <span><i class="fa fa-calendar"></i> <?php echo date('d/m/Y', strtotime($result_detail['ngay_tao'])); ?></span>
                            </div>
                        </div>

                        <div class="post-hero-image-wrap">
                            <?php if (!empty($result_detail['anh_chinh'])): ?>
                                <img src="images/baiviet/<?php echo $result_detail['anh_chinh']; ?>" alt="<?php echo $result_detail['ten_baiviet']; ?>">
                            <?php endif; ?>
                        </div>

                        <div class="post-body">
                            <?php
                            $content_found = false;

                            // Nội dung tổng quan
                            if (!empty($result_detail['noidung_tongquan'])) {
                                echo '<div style="font-weight:500; font-style:italic; margin-bottom:20px; border-left:4px solid #E3242B; padding-left:15px; background:#f1f1f1; padding: 15px;">' . $result_detail['noidung_tongquan'] . '</div>';
                                $content_found = true;
                            }

                            // Nội dung chi tiết 1-5
                            for ($i = 1; $i <= 5; $i++) {
                                $image_field = 'anh_' . $i;
                                $content_field = 'noidung_' . $i;

                                // Ảnh trước
                                if (!empty($result_detail[$image_field])) {
                                    echo '<img src="images/baiviet/' . $result_detail[$image_field] . '" alt="Ảnh minh họa">';
                                    $content_found = true;
                                }

                                // Nội dung sau
                                if (!empty($result_detail[$content_field])) {
                                    echo $result_detail[$content_field];
                                    $content_found = true;
                                }
                            }

                            if (!$content_found) {
                                echo '<p>Nội dung đang cập nhật...</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="latest-posts-sidebar">
                        <h4 class="sidebar-title">BÀI VIẾT MỚI 📰</h4>

                        <?php
                        $latest_posts = $baiviet->get_latest_posts(5);
                        if ($latest_posts) {
                            while ($row = $latest_posts->fetch_assoc()) {
                                if ($row['id_baiviet'] == $id_baiviet) continue;
                                $cat_name_latest = $baiviet->get_category_name($row['theloai']);
                        ?>
                                <a href="baivietchitiet.php?id=<?php echo $row['id_baiviet']; ?>" class="latest-post-item">
                                    <?php if (!empty($row['anh_chinh'])): ?>
                                        <img src="images/baiviet/<?php echo $row['anh_chinh']; ?>" class="lp-img">
                                    <?php else: ?>
                                        <img src="https://placehold.co/70x70" class="lp-img">
                                    <?php endif; ?>

                                    <div class="lp-info">
                                        <p><?php echo $fm->textShorten($row['ten_baiviet'], 45); ?></p>
                                        <small style="color:#E3242B; font-size:10px;"><?php echo $cat_name_latest; ?></small> -
                                        <small><?php echo date('d/m/Y', strtotime($row['ngay_tao'])); ?></small>
                                    </div>
                                </a>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 0;">
                <h3 style="color:#ccc;">Bài viết không tồn tại hoặc đã bị xóa.</h3>
                <a href="trangbaiviet.php" class="btn btn-primary mt-3">Quay lại trang Tin Tức</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'inc/footer.php'; ?>