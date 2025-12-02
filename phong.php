<?php include 'inc/header.php'; ?>
<?php
    // ====== DÙNG CLASS PHÒNG THAY CHO DỊCH VỤ ======
    if (! isset($ph)) {
        include_once 'classes/phong.php';
        $ph = new phong();
    }

    // Helper: lấy giá trị cột theo danh sách tên ứng viên
    function pick($row, $keys, $default = '') {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
        }
        return $default;
    }

    // Lấy toàn bộ phòng để suy ra danh sách LOẠI PHÒNG cho menu
    $allRes = $ph->show_phong_all();
    $allRooms = [];
    $types = []; // [maloaiphong => tenloaiphong]
    if ($allRes) {
        while ($r = $allRes->fetch_assoc()) {
            $allRooms[] = $r;
            $ma = pick($r, ['maloaiphong', 'ma_loai', 'maLoaiPhong']);
            $ten= pick($r, ['tenloaiphong', 'ten_loai', 'tenLoaiPhong'], 'Loại phòng');
            if ($ma !== '') $types[$ma] = $ten;
        }
    }

    // Xác định loại hiện tại
    $has_loai_param = isset($_GET['loai']) && $_GET['loai'] !== '';
    $currentLoai    = $has_loai_param ? trim($_GET['loai']) : (count($types) ? array_key_first($types) : '');

    // Lấy danh sách phòng theo LOẠI (nội dung chính)
    $list = $currentLoai !== '' ? $ph->show_phong_by_loai($currentLoai) : false;
    $rooms = [];
    if ($list) {
        while ($r = $list->fetch_assoc()) {
            $rooms[] = $r;
        }
    }
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
                <h1 class="mb-2 bread">KHÔNG GIAN NHÀ HÀNG</h1>
                <p class="breadcrumbs">
                    <span class="mr-2"><a href="index.php">trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
                    <span>không gian <i class="ion-ios-arrow-forward"></i></span>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section" style="margin-top:-100px;">
    <div class="container">

        <!-- MENU LOẠI PHÒNG (nav-pills) -->
        <div class="nav nav-pills d-flex text-center mb-4">
            <?php if (!empty($types)): ?>
                <?php foreach ($types as $ma => $ten): 
                    $active = ((string)$ma === (string)$currentLoai) ? ' active' : '';
                ?>
                    <a class="nav-link ftco-animate<?php echo $active; ?>"
                       href="phong.php?loai=<?php echo htmlspecialchars($ma); ?>">
                       <?php echo htmlspecialchars($ten); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted">Chưa có loại phòng nào.</span>
            <?php endif; ?>
        </div>

        <!-- DANH SÁCH PHÒNG THEO LOẠI -->
        <?php if (!empty($rooms)): ?>
            <div class="row">
                <?php foreach ($rooms as $row): 
                    $name   = pick($row, ['tenphong','Ten_phong','name_phong','Name_phong','ten','name'], 'Phòng');
                    $price  = (int) pick($row, ['gia','Gia_phong','gia_phong','dongia','don_gia'], 0);
                    $note   = pick($row, ['ghichu','ghi_chu','mota','mo_ta'], '');
                    $imgsRaw= pick($row, ['images','hinhanh','hinh_anh','img'], '');
                    $imgs   = array_filter(array_map('trim', $imgsRaw ? explode(';', $imgsRaw) : []));
                    $firstImg = !empty($imgs) ? $imgs[0] : 'placeholder.jpg';
                ?>
                <div class="col-md-4 mb-4">
                    <div class="svc-card ftco-animate h-100">
                        <div class="svc-img" style="background-image:url('images/<?php echo htmlspecialchars($firstImg); ?>')"></div>
                        <div class="svc-body">
                            <h3 class="svc-title"><?php echo htmlspecialchars($name); ?></h3>
                            <div class="text-primary svc-price mb-2">
                                <?php echo number_format($price); ?> VND
                            </div>
                            <?php if ($note): ?>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($note)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">Không tìm thấy phòng thuộc loại đã chọn.</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'inc/footer.php'; ?>
