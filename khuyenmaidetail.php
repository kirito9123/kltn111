<?php
include 'inc/header.php';
?>

<?php
// KHỞI TẠO OBJECT KHUYENMAI
include_once 'classes/khuyenmai.php';
$km = new khuyenmai();

if(!isset($_GET['kmid']) || $_GET['kmid']==NULL){
    echo "<script>window.location = '404.php'</script>";
}else{
    $id = $_GET['kmid'];
}
?>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');"
    data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Chi Tiết Khuyến Mãi</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i
                                class="ion-ios-arrow-forward"></i></a></span> <span> Chi tiết khuyến mãi <i
                            class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section">
    <div class="container">
        <div class="row">
            <?php
            $get_detail = $km->get_km_detail($id);
            if($get_detail && $get_detail->num_rows > 0){
                $result_detail = $get_detail->fetch_assoc();
                
                // Kiểm tra xem khuyến mãi còn hiệu lực không
                $now = time();
                $time_star = strtotime($result_detail['time_star']);
                $time_end = strtotime($result_detail['time_end']);
                $is_active = ($now >= $time_star && $now <= $time_end);
            ?>
            
            <div class="col-lg-6 mb-5 ftco-animate">
                <?php if(!empty($result_detail['images'])): ?>
                <a href="images/food/<?php echo $result_detail['images'] ?>" class="image-popup">
                    <img src="images/food/<?php echo $result_detail['images'] ?>" class="img-fluid"
                        alt="<?php echo htmlspecialchars($result_detail['name_km']) ?>">
                </a>
                <?php else: ?>
                <img src="images/default-promotion.jpg" class="img-fluid" alt="Khuyến mãi">
                <?php endif; ?>
            </div>
            
            <div class="col-lg-6 product-details pl-md-5 ftco-animate">
                <h2><?php echo htmlspecialchars($result_detail['name_km']) ?></h2>
                
                <div class="km-status mb-3">
                    <?php if($is_active): ?>
                    <span class="badge badge-success" style="font-size: 16px; padding: 8px 15px;">
                        <i class="fa fa-check-circle"></i> Đang áp dụng
                    </span>
                    <?php elseif($now < $time_star): ?>
                    <span class="badge badge-info" style="font-size: 16px; padding: 8px 15px;">
                        <i class="fa fa-clock-o"></i> Sắp diễn ra
                    </span>
                    <?php else: ?>
                    <span class="badge badge-secondary" style="font-size: 16px; padding: 8px 15px;">
                        <i class="fa fa-times-circle"></i> Đã kết thúc
                    </span>
                    <?php endif; ?>
                </div>

                <p class="price">
                    <span class="discount-value">Giảm <?php echo $result_detail['discout'] ?>%</span>
                </p>

                <div class="km-time mb-4">
                    <h4 style="font-size: 18px; margin-bottom: 10px;">
                        <i class="fa fa-calendar"></i> Thời gian áp dụng:
                    </h4>
                    <p style="font-size: 16px; color: #666;">
                        <strong>Từ:</strong> <?php echo date('d/m/Y H:i', $time_star) ?><br>
                        <strong>Đến:</strong> <?php echo date('d/m/Y H:i', $time_end) ?>
                    </p>
                </div>

                <?php if(!empty($result_detail['ghichu'])): ?>
                <div class="km-description">
                    <h4 style="font-size: 18px; margin-bottom: 10px;">
                        <i class="fa fa-info-circle"></i> Điều kiện áp dụng:
                    </h4>
                    <p style="font-size: 15px; line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($result_detail['ghichu'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="km-note mt-4 p-3" style="background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                    <p style="margin: 0; color: #856404;">
                        <i class="fa fa-exclamation-triangle"></i> 
                        <strong>Lưu ý:</strong> Khuyến mãi chỉ áp dụng cho đơn hàng mua tại quán.
                    </p>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <a href="menu.php" class="btn btn-primary py-3 px-5">
                            <i class="fa fa-cutlery"></i> Xem Thực Đơn
                        </a>
                        <a href="index.php" class="btn btn-outline-primary py-3 px-5 ml-2">
                            <i class="fa fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
            
            <?php
            } else {
                echo "<div class='col-12 text-center'>";
                echo "<h3>Không tìm thấy thông tin khuyến mãi!</h3>";
                echo "<a href='index.php' class='btn btn-primary mt-3'>Quay lại trang chủ</a>";
                echo "</div>";
            }
            ?>
        </div>
    </div>
</section>

<style>
.discount-value {
    font-size: 48px;
    font-weight: bold;
    color: #d32f2f;
}

.km-status .badge {
    font-weight: normal;
}

.km-time, .km-description {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.image-popup img {
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.image-popup img:hover {
    transform: scale(1.02);
}
</style>

<?php
include 'inc/footer.php';
?>