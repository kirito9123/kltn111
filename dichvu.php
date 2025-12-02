<?php include 'inc/header.php'; ?>
<?php
    // Nếu chưa có, include class & khởi tạo
    if (! isset($dv)) {
        include_once 'classes/dichvu.php';
        $dv = new dichvu();
    }

    // Lấy danh sách dịch vụ để render menu
    $list     = $dv->show_dichvu_all();
    $services = [];
    if ($list) {
        while ($r = $list->fetch_assoc()) {
            $services[] = $r;
        }
    }

    // Xác định id hiện tại
    $has_id_param = isset($_GET['id']) && ctype_digit($_GET['id']) && (int) $_GET['id'] > 0;
    $id           = $has_id_param ? (int) $_GET['id'] : 1;

    // Lấy chi tiết theo id hiện tại
    $detail = $dv->show_dichvu_by_id($id);

    // Fallback CHỈ khi KHÔNG truyền id và id=1 không tồn tại
    if (! $has_id_param && (! $detail || $detail->num_rows <= 0) && ! empty($services)) {
        $id     = (int) $services[0]['id_dichvu'];
        $detail = $dv->show_dichvu_by_id($id);
    }

    // Lấy row chi tiết (nếu có)
    $row = ($detail && $detail->num_rows > 0) ? $detail->fetch_assoc() : null;
?>

<style>
    .nav-pills .nav-link{margin:0 6px 8px;border-radius:20px;}
    .svc-card{border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.1);background:#fff;}
    .svc-img{width:100%;height:280px;background-size:cover;background-position:center;}
    .svc-body{padding:16px;}
    .svc-title{font-size:22px;font-weight:700;margin:0 0 8px;}
    .svc-price{font-weight:700;}
</style>

<section class="hero-wrap hero-wrap-2" style="background-image:url('images/bg3.jpg')" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
        <div class="col-md-9 ftco-animate text-center mb-4">
            <h1 class="mb-2 bread">DỊCH VỤ ĐẶT TIỆC</h1>
            <p class="breadcrumbs">
            <span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
            <span>Dịch vụ <i class="ion-ios-arrow-forward"></i></span>
            </p>
        </div>
        </div>
    </div>
</section>

<section class="ftco-section" style="margin-top:-100px;">
    <div class="container">

        <!-- MENU A HREF (nav-pills) -->
        <div class="nav nav-pills d-flex text-center mb-4">
        <?php if (!empty($services)): ?>
            <?php foreach ($services as $s): 
            $active = ((int)$s['id_dichvu'] === $id) ? ' active' : '';
            ?>
            <a class="nav-link ftco-animate<?php echo $active; ?>"
                href="dichvu.php?id=<?php echo (int)$s['id_dichvu']; ?>">
                <?php echo htmlspecialchars($s['Name_dichvu']); ?>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="text-muted">Chưa có dịch vụ nào.</span>
        <?php endif; ?>
        </div>

        <!-- NỘI DUNG DỊCH VỤ -->
        <?php if ($row): ?>
        <div class="text-center ftco-animate">
            <?php
            // Nếu nhiều ảnh được nối bằng dấu ";"
            $imgs = !empty($row['images']) ? explode(';', $row['images']) : ['placeholder.jpg'];
            $imgs = array_filter(array_map('trim', $imgs));
            ?>

            <?php if (!empty($imgs)): ?>
            <!-- Carousel -->
            <div id="dichvuCarousel" class="carousel slide mb-4" data-ride="carousel" data-interval="5000">
                <div class="carousel-inner">
                <?php $first = true; foreach ($imgs as $img): ?>
                    <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                    <img src="images/dichvu/<?php echo htmlspecialchars($img); ?>" 
                        alt="<?php echo htmlspecialchars($row['Name_dichvu']); ?>" 
                        class="d-block w-100"
                        style="max-height:500px; object-fit:cover; border-radius:10px;">
                    </div>
                <?php $first = false; endforeach; ?>
                </div>

                <!-- Nút điều hướng -->
                <a class="carousel-control-prev" href="#dichvuCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Trước</span>
                </a>
                <a class="carousel-control-next" href="#dichvuCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Sau</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Thông tin dịch vụ -->
            <h2><b><?php echo htmlspecialchars($row['Name_dichvu']); ?></b></h2>
            <div class="text-primary mb-2">
            <?php echo number_format((int)$row['Gia_dichvu']); ?> VND
            </div>
            <?php if (!empty($row['ghichu'])): ?>
            <p><?php echo nl2br(htmlspecialchars($row['ghichu'])); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-center">Không tìm thấy dịch vụ.</p>
        <?php endif; ?>
    </div>
    </section>


<?php include 'inc/footer.php'; ?>