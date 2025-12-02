<?php
    /* ====================== COPY TỪ ĐÂY ====================== */
    include_once __DIR__ . '/inc/header.php';
    Session::checkSession();

    $userId     = (int) Session::get('id');
    $userName   = (string) (Session::get('name') ?? '');
    $hopdong_id = isset($_GET['hopdong_id']) ? (int) $_GET['hopdong_id'] : 0;

    if ($hopdong_id <= 0) {
        echo "<div class='container py-4'><div class='alert alert-danger'>Thiếu hoặc sai ID hợp đồng.</div></div>";
        include_once __DIR__ . '/inc/footer.php';exit;
    }

    include_once __DIR__ . '/classes/danhgia.php';
    $dgv = new DanhGia();

    /* ===== Kiểm tra hợp đồng thuộc user ===== */
    $hd = $dgv->getContractForUser($hopdong_id, $userId);
    if (! $hd) {
        echo "<div class='container py-4'><div class='alert alert-danger'>Không tìm thấy hợp đồng hoặc bạn không có quyền đánh giá.</div></div>";
        include_once __DIR__ . '/inc/footer.php';exit;
    }

    // Không tìm thấy hợp đồng hoặc không thuộc về user
    $hd = $dgv->getContractForUser($hopdong_id, $userId);
    if (! $hd) {
        echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Không thể đánh giá',
        text: 'Không tìm thấy hợp đồng hoặc bạn không có quyền đánh giá.',
        confirmButtonText: 'Về trang cá nhân'
    }).then(() => {
        window.location.href = 'userblog.php?id={$userId}';
    });
    </script>";
        include_once __DIR__ . '/inc/footer.php';
        exit;
    }

    /* === CHẶN NẾU CHƯA THANH TOÁN === */
    $status = strtolower(trim($hd['payment_status'] ?? ''));
    if ($status !== 'completed') {
        echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'warning',
        title: 'Bạn chưa thanh toán',
        text: 'Chỉ hợp đồng đã thanh toán (completed) mới được phép đánh giá.',
        confirmButtonText: 'Về trang cá nhân'
    }).then(() => {
        window.location.href = 'userblog.php?id={$userId}';
    });
    </script>";
        include_once __DIR__ . '/inc/footer.php';
        exit;
    }

    /* ===== Lấy review cũ (đã alias sẵn trong lớp DanhGia) =====
   - rating  => so_sao
   - comment => binh_luan
   - photos  => hinhanh (tên file duy nhất) */
    $oldReview = $dgv->getReview($hopdong_id, $userId);

    /* ===== Chuẩn hoá dữ liệu cũ để prefill ===== */
    $ratingVal    = isset($oldReview['rating']) ? (int) $oldReview['rating'] : 5; // mặc định 5 sao
    $commentVal   = isset($oldReview['comment']) ? (string) $oldReview['comment'] : '';
    $oldPhotoName = isset($oldReview['photos']) ? trim((string) $oldReview['photos']) : '';
    $oldPhotoUrl  = $oldPhotoName !== '' ? 'images/danhgia/' . $oldPhotoName : '';

    /* ===== Submit ===== */
    $flash = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dg = new DanhGia();

        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $photos  = $_FILES['photos'] ?? null;

        if ($oldReview) {
            // Đã có -> cập nhật
            $result   = $dg->updateReview($hopdong_id, $userId, $rating, $comment, $photos);
            $isUpdate = true;
        } else {
            // Chưa có -> tạo mới
            $result   = $dg->createReview($hopdong_id, $userId, $rating, $comment, $photos);
            $isUpdate = false;
        }

        if (! empty($result['ok'])) {
            $title = $isUpdate ? "Cập nhật thành công!" : "Cảm ơn bạn!";
            $text  = $isUpdate ? "Đánh giá đã được cập nhật." : "Đánh giá của bạn đã được ghi nhận.";
            echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        Swal.fire({
            icon: 'success',
            title: " . json_encode($title) . ",
            text: " . json_encode($text) . ",
            confirmButtonText: 'Quay lại trang cá nhân',
            timer: 2500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = 'userblog.php?id={$userId}';
        });
        </script>";
            exit;
        } else {
            $msg   = isset($result['msg']) ? $result['msg'] : 'Có lỗi xảy ra. Vui lòng thử lại.';
            $flash = "<div class='alert alert-danger'>{$msg}</div>";
            // Giữ lại giá trị người dùng vừa submit để hiển thị lại
            $ratingVal  = $rating;
            $commentVal = $comment;
            // Ảnh cũ vẫn giữ như cũ
        }
    }

    /* ===== Lấy tên KH nếu session rỗng (tuỳ chọn) ===== */
    if ($userName === '') {
        include_once __DIR__ . '/lib/database.php';
        $db2 = new Database();
        if (isset($db2->link) && $db2->link instanceof mysqli) {
            @$db2->link->set_charset('utf8mb4');
        }

        $u = $db2->select("SELECT ten FROM khach_hang WHERE id={$userId} LIMIT 1");
        if ($u && $row = $u->fetch_assoc()) {
            $userName = $row['ten'];
        }

    }
?>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate text-center mb-4">
        <h1 class="mb-2 bread">Đánh giá dịch vụ</h1>
        <p class="breadcrumbs">
          <span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span>
          <span>Đánh giá <i class="ion-ios-arrow-forward"></i></span>
        </p>
      </div>
    </div>
  </div>
</section>

<section class="ftco-section">
  <div class="container">
    <?php echo $flash ?>
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="p-4 shadow rounded bg-white">
          <h4 class="mb-3">Thông tin hợp đồng</h4>
          <div class="mb-3">
            <strong>Mã hợp đồng:</strong> #<?php echo htmlspecialchars($hd['id']) ?><br>
            <strong>Khách hàng:</strong>                                                                                     <?php echo htmlspecialchars($userName ?: ('User #' . $userId)) ?><br>
            <strong>Ngày đặt:</strong>                                                                                     <?php echo htmlspecialchars($hd['dates']) ?><br>
            <strong>Nội dung:</strong>                                                                                 <?php echo nl2br(htmlspecialchars($hd['noidung'])) ?>
          </div>

          <hr>

          <h4 class="mb-3">Gửi đánh giá của bạn</h4>
          <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label><strong>Chọn số sao</strong></label><br>
              <?php for ($s = 5; $s >= 1; $s--): ?>
                <label class="mr-2">
                  <input type="radio" name="rating" value="<?php echo $s ?>"<?php echo($s === (int) $ratingVal ? 'checked' : '') ?>><?php echo $s ?> ⭐
                </label>
              <?php endfor; ?>
            </div>

            <div class="form-group">
              <label for="comment"><strong>Bình luận</strong></label>
              <textarea name="comment" id="comment" rows="5" class="form-control" placeholder="Chia sẻ trải nghiệm của bạn..."><?php echo htmlspecialchars($commentVal) ?></textarea>
            </div>

            <div class="form-group">
              <label for="photos"><strong>Ảnh minh họa (tối đa 1 ảnh)</strong></label>
              <input type="file" name="photos[]" id="photos" class="form-control-file" accept="image/*" multiple>
              <small class="text-muted d-block">Hỗ trợ JPG, PNG, WEBP, GIF. Tối đa 5MB/ảnh.</small>

              <?php if ($oldPhotoUrl !== ''): ?>
                <div class="mt-2">
                  <div class="mb-1"><strong>Ảnh đã tải trước đó:</strong></div>
                  <a href="<?php echo htmlspecialchars($oldPhotoUrl) ?>" target="_blank" title="Xem ảnh">
                    <img src="<?php echo htmlspecialchars($oldPhotoUrl) ?>" alt="old photo"
                         style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
                  </a>
                  <small class="text-muted d-block mt-1">
                    Nếu bạn không chọn ảnh mới, hệ thống sẽ giữ nguyên ảnh cũ.
                  </small>
                </div>
              <?php endif; ?>
            </div>

            <div class="mt-4 d-flex justify-content-between">
              <a class="btn btn-light" href="userblog.php?id=<?php echo $userId ?>">⬅ Quay lại danh sách</a>
              <button type="submit" class="btn btn-primary"><?php echo $oldReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?></button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</section>

<style>
form input[type="radio"] { transform: scale(1.2); margin-right: 4px; }
form textarea { resize: vertical; }
</style>

<?php include_once __DIR__ . '/inc/footer.php'; ?>
/* ====================== COPY ĐẾN ĐÂY ====================== */
