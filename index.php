<?php
include 'inc/header.php';

// Kiểm tra và khởi tạo Database
if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
if (!isset($db) || !isset($db->link)) { $db = new Database(); @$db->link->set_charset('utf8mb4'); }

// --- Lấy danh sách Menu (Combo) ---
$menu_rs = $db->select("
  SELECT id_menu, ten_menu, ghi_chu, hinhanh
  FROM menu
  WHERE trang_thai = 0
  ORDER BY id_menu ASC
");

// --- Xử lý Link Đặt ngay ---
$logged = (bool)(Session::get('customer_login') ?? Session::get('userlogin') ?? false);
$returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
$linkDatNgay = $logged ? 'datban.php' : ('login.php?return=' . $returnUrl);

if (isset($_GET['msg']) && $_GET['msg'] === 'ThanhCong') {
    echo "<script>alert('Thanh toán thành công!');</script>";
}
?>

<style>
  /* ========================================= */
  /* 1. CSS CHO HERO SLIDER (BANNER ĐẦU TRANG) */
  /* ========================================= */
  .hero-slider { position: relative; width: 100%; height: 600px; overflow: hidden; background-color: #333; }
  .hero-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; opacity: 0; transition: opacity 1s ease-in-out, transform 1s ease-in-out; transform: scale(1.05); }
  .hero-slide.active { opacity: 1; z-index: 1; transform: scale(1); }
  .hero-slide .overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); }
  
  .slide-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 2; text-shadow: 0 3px 12px rgba(0,0,0,0.8); }
  .slide-content h1 { color: #FFD700; font-size: 4rem; font-weight: 700; margin-bottom: 1rem; animation: fadeInDown 1s ease-out 0.5s both; }
  
  @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }

  .slider-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; border: none; background: rgba(0, 0, 0, 0.3); color: white; font-size: 2rem; cursor: pointer; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
  .slider-nav:hover { background: rgba(211, 47, 47, 0.8); }
  .slider-nav.prev { left: 30px; }
  .slider-nav.next { right: 30px; }

  @media (max-width: 768px) {
      .hero-slider { height: 60vh; }
      .slide-content h1 { font-size: 2.5rem; }
      .slider-nav { width: 40px; height: 40px; font-size: 1.5rem; left: 10px; }
      .slider-nav.next { right: 10px; }
  }

  /* ========================================= */
  /* 2. CSS CHO COMBO BOX                      */
  /* ========================================= */
  .row.no-gutters.d-flex.align-items-stretch > [class*="col-"] { padding: 10px 12px; }
  .menus.ftco-animate.combo-box{ width: 100%; border: 1px solid #eaeaea; border-radius: 10px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.06); overflow: hidden; }
  .combo-box .menu-img{ min-height: 220px; height: 100%; background-size: cover; background-position: center center; width: 100%; border-radius: 0; border-top-left-radius: 10px; border-bottom-left-radius: 10px;}
  @media (min-width: 992px){ .menus.ftco-animate.combo-box{ display: grid; grid-template-columns: 50% 50%; grid-template-areas: "img content" "footer footer"; } .combo-box .menu-img{ grid-area: img; } .combo-box .text{ grid-area: content; border-left: 1px solid #eee; } .combo-box .combo-footer{ grid-area: footer; border-top: 1px solid #eee; } }
  .combo-box .text{ padding: 20px; background: #fff; display: flex; flex-direction: column; justify-content: center; }
  .combo-box .text h3{ margin: 0 0 6px; font-weight: 700; }
  .combo-box .combo-footer{ width: 100%; background: #fff; padding: 10px 12px 12px; box-sizing: border-box; }
  .combo-box .combo-footer .d-flex{ justify-content: center; gap: 10px; }
  .combo-box .combo-footer .btn{ min-width: 120px; }
  .combo-box .combo-footer .combo-detail{ margin-top: 10px; border: 1px solid #eceff1; border-radius: 8px; background: #fff; padding: 8px 10px; overflow-x: auto; box-shadow: 0 1px 6px rgba(0,0,0,.04); display:none; }
  .combo-box .combo-footer .combo-detail.open{ display:block; }
  .combo-detail .combo-detail-table{ width:100%; border-collapse:collapse }
  .combo-detail .combo-detail-table th,.combo-detail .combo-detail-table td{ border:1px solid #eceff1; padding:8px 10px }
  .combo-detail .combo-detail-table thead th{ background:#f6f8fa; font-weight:600 }
  .combo-detail .combo-total{ margin-top:8px; font-weight:700; text-align:left }
  @media (max-width: 991.98px){ .menus.ftco-animate.combo-box{ grid-template-columns: 1fr; grid-template-areas: "img" "content" "footer"; } .combo-box .menu-img{ min-height: 200px; border-radius: 10px 10px 0 0; } .combo-box .text{ border-left: none; border-top: 1px solid #eee; } .combo-box .combo-footer{ border-top: none; border-top: 1px solid #eee; } }

  /* ========================================= */
  /* 3. [MỚI] CSS KHÔI PHỤC HÌNH ẢNH GIỚI THIỆU */
  /* ========================================= */
  .about-preview .img {
      width: 100%;
      background-size: cover;
      background-position: center;
      border-radius: 12px;
      min-height: 400px; /* Chiều cao cố định để hình hiện rõ */
      flex: 1;
  }
  @media (max-width: 768px) {
      .about-preview .img { min-height: 200px; margin-bottom: 15px; }
      /* Nếu trên mobile hình bị đè, cho xếp dọc */
      .about-preview .d-flex { display: block !important; }
      .about-preview .img-1, .about-preview .img-2 { margin: 0 !important; }
  }

  /* ========================================= */
  /* 4. CSS CHO PHẦN BÌNH LUẬN (REVIEW SLIDER) */
  /* ========================================= */
  .review-section { background: #f8f9fa; padding: 80px 0; text-align: center; border-top: 1px solid #eee; }
  .review-wrapper { position: relative; max-width: 800px; margin: 0 auto; background: #fff; border-radius: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); padding: 50px 60px; min-height: 300px; display: flex; align-items: center; justify-content: center; }
  .review-item { display: none; animation: fadeIn 0.5s ease; width: 100%; }
  .review-item.active { display: block; }
  .quote-icon { font-size: 40px; color: #e0e0e0; margin-bottom: 15px; }
  .review-content { font-size: 22px; font-style: italic; color: #333; line-height: 1.6; margin-bottom: 20px; font-family: "Times New Roman", Times, serif; }
  .review-author { font-weight: 800; color: #d32f2f; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; margin-top: 15px; }
  .review-stars { color: #f1c40f; margin-bottom: 10px; font-size: 24px; }
  .review-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; background: #fff; border: 1px solid #eee; border-radius: 50%; color: #333; font-size: 24px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.3s; z-index: 5; display: flex; align-items: center; justify-content: center; }
  .review-nav-btn:hover { background: #d32f2f; color: #fff; border-color: #d32f2f; }
  .review-nav-btn.prev { left: -25px; }
  .review-nav-btn.next { right: -25px; }
  @media (max-width: 768px) { .review-wrapper { padding: 30px; margin: 0 20px; } .review-nav-btn { width: 40px; height: 40px; font-size: 18px; } .review-nav-btn.prev { left: -15px; } .review-nav-btn.next { right: -15px; } }
</style>

<section id="hero-slider" class="hero-slider">
    <div class="hero-slide active" style="background-image: url('images/nhahangtriskiet.jpg');">
        <div class="overlay"></div>
        <div class="container"><div class="slide-content"><h1>Nhà Hàng TRisKiet</h1></div></div>
    </div>
    <div class="hero-slide" style="background-image: url('images/nhahangcaocap.jpg');">
        <div class="overlay"></div>
        <div class="container"><div class="slide-content"><h1>Sự Kiện Nổi Bật</h1></div></div>
    </div>
    <div class="hero-slide" style="background-image: url('images/khongkhisangtrong.jpg');">
        <div class="overlay"></div>
        <div class="container"><div class="slide-content"><h1>Không Gian Ấm Cúng</h1></div></div>
    </div>
    <div class="hero-slide" style="background-image: url('images/thucdondadang.jpg');">
        <div class="overlay"></div>
        <div class="container"><div class="slide-content"><h1>Thực Đơn Đa Dạng</h1></div></div>
    </div>
    <div class="hero-slide" style="background-image: url('images/phucvutantam.jpg');">
        <div class="overlay"></div>
        <div class="container"><div class="slide-content"><h1>Phục Vụ Tận Tâm</h1></div></div>
    </div>
    <button class="slider-nav prev" id="hero-prev">&#10094;</button>
    <button class="slider-nav next" id="hero-next">&#10095;</button>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('#hero-slider .hero-slide');
    const prevBtn = document.getElementById('hero-prev');
    const nextBtn = document.getElementById('hero-next');
    let currentSlide = 0, slideInterval;
    function showSlide(index) { slides.forEach((slide, i) => { slide.classList.remove('active'); if (i === index) slide.classList.add('active'); }); }
    function nextSlide() { currentSlide = (currentSlide + 1) % slides.length; showSlide(currentSlide); }
    function prevSlide() { currentSlide = (currentSlide - 1 + slides.length) % slides.length; showSlide(currentSlide); }
    function startSlider() { if (slides.length > 1) slideInterval = setInterval(nextSlide, 5000); }
    function resetSliderInterval() { clearInterval(slideInterval); startSlider(); }
    if (slides.length > 0) { showSlide(currentSlide); startSlider(); if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetSliderInterval(); }); if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetSliderInterval(); }); }
});
</script>

<section class="ftco-section">
  <div class="container">
    <div class="row no-gutters justify-content-center mb-5 pb-2">
      <div class="col-md-12 text-center heading-section ftco-animate">
        <span class="subheading">Các combo nổi bật</span>
        <h2 class="mb-4">Thực đơn đặc biệt</h2>
      </div>
    </div>

    <div class="row no-gutters d-flex align-items-stretch">
      <?php if ($menu_rs && $menu_rs->num_rows > 0): ?>
        <?php while ($m = $menu_rs->fetch_assoc()):
          $id_menu  = (int)$m['id_menu'];
          $ten_menu = $m['ten_menu'] ?? '';
          $ghichu   = trim($m['ghi_chu'] ?? '');
          $img      = !empty($m['hinhanh']) ? 'images/combo/'.trim($m['hinhanh']) : 'images/placeholder_combo.jpg';
        ?>
        <div class="col-md-12 col-lg-6 d-flex align-self-stretch">
          <div class="menus ftco-animate combo-box" data-idmenu="<?php echo $id_menu; ?>">
            <div class="menu-img img" style="background-image:url('<?php echo htmlspecialchars($img); ?>')"></div>
            <div class="text">
              <h3><?php echo htmlspecialchars($ten_menu); ?></h3>
              <?php if ($ghichu !== ''): ?>
                <p><span><?php echo htmlspecialchars($ghichu); ?></span></p>
              <?php endif; ?>
            </div>
            <div class="combo-footer">
              <div class="d-flex gap-2 mb-2">
                <center>
                <button type="button" class="btn btn-outline-secondary mr-2 btn-xemthem"
                        data-url="hopdong_menu.php?ajax=combo_detail_html&id=<?php echo $id_menu; ?>"
                        data-id="<?php echo $id_menu; ?>">Xem thêm</button>
                <a href="<?php echo htmlspecialchars($linkDatNgay); ?>" class="btn btn-primary">Đặt ngay</a>
                </center>
              </div>
              <div class="combo-detail">
                <div class="detail-loading p-2 text-muted">Đang tải chi tiết...</div>
                <div class="detail-body" style="display:none;"></div>
              </div>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted">Hiện chưa có combo nào để hiển thị.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function(){
  async function fetchComboDetailHTML(url){
    const res = await fetch(url, {cache:'no-store'});
    if(!res.ok) throw new Error('HTTP ' + res.status);
    return await res.text();
  }
  document.querySelectorAll('.btn-xemthem').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const card    = this.closest('.combo-box');
      const panel   = card.querySelector('.combo-detail');
      const loading = panel.querySelector('.detail-loading');
      const body    = panel.querySelector('.detail-body');
      const url     = this.dataset.url;
      const id      = this.dataset.id;
      if (panel.classList.contains('open')) {
        panel.classList.remove('open');
        const usp = new URLSearchParams(window.location.search);
        usp.delete('menu');
        history.replaceState(null, '', window.location.pathname + (usp.toString() ? ('?' + usp.toString()) : ''));
        return;
      }
      panel.classList.add('open');
      const usp = new URLSearchParams(window.location.search);
      usp.set('menu', id);
      history.replaceState(null, '', window.location.pathname + '?' + usp.toString());
      if (panel.dataset.loaded === '1') return;
      try{
        loading.textContent = 'Đang tải chi tiết...';
        const html = await fetchComboDetailHTML(url);
        body.innerHTML = html;
        loading.style.display = 'none';
        body.style.display = 'block';
        panel.dataset.loaded = '1';
      }catch(e){
        loading.textContent = 'Lỗi khi tải chi tiết.';
        console.error(e);
      }
    });
  });
  const params = new URLSearchParams(window.location.search);
  const autoId = params.get('menu');
  if (autoId) {
    const targetBtn = document.querySelector(`.btn-xemthem[data-id="${autoId}"]`);
    if (targetBtn) targetBtn.click();
  }
});
</script>

<section class="ftco-section about-preview">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-7 d-flex mb-4 mb-md-0">
        <div class="img img-1 mr-md-2" style="background-image: url(images/about-2.jpg);"></div>
        <div class="img img-2 ml-md-2" style="background-image: url(images/about-3.jpg);"></div>
      </div>
      <div class="col-md-5">
        <div class="heading-section mb-3">
          <span class="subheading">Giới thiệu</span>
          <h2 class="mb-3">TRisKiet Quán</h2>
        </div>
        <p class="line-clamp-3">Chúng tôi mang đến những món ăn chay tinh túy, được chế biến từ nguyên liệu tươi ngon, mang lại sự hài lòng cho mỗi thực khách.</p>
        <a href="about.php" class="btn btn-primary btn-sm px-4">Xem thêm</a>
      </div>
    </div>
  </div>
</section>

<section class="ftco-section bg-light">
  <div class="container">
    <div class="row justify-content-center mb-5 pb-2">
      <div class="col-md-12 text-center heading-section ftco-animate">
        <span class="subheading">Dịch vụ</span>
        <h2 class="mb-4">Các dịch vụ tổ chức hiện tại</h2>
      </div>
    </div>
    <div class="row">
      <div class="col-md-4 d-flex align-self-stretch ftco-animate text-center">
        <div class="media block-6 services d-block">
          <div class="icon d-flex justify-content-center align-items-center"><span class="flaticon-cake"></span></div>
          <div class="media-body p-2 mt-3"><h3 class="heading">Tiệc Sinh Nhật</h3><p>Chúng tôi cung cấp những món ăn tuyệt vời cho bữa tiệc sinh nhật của bạn.</p></div>
        </div>
      </div>
      <div class="col-md-4 d-flex align-self-stretch ftco-animate text-center">
        <div class="media block-6 services d-block">
          <div class="icon d-flex justify-content-center align-items-center"><span class="flaticon-meeting"></span></div>
          <div class="media-body p-2 mt-3"><h3 class="heading">Cuộc Họp Kinh Doanh</h3><p>Thưởng thức những món ăn chay tinh tế trong các cuộc họp kinh doanh.</p></div>
        </div>
      </div>
      <div class="col-md-4 d-flex align-self-stretch ftco-animate text-center">
        <div class="media block-6 services d-block">
          <div class="icon d-flex justify-content-center align-items-center"><span class="flaticon-tray"></span></div>
          <div class="media-body p-2 mt-3"><h3 class="heading">Tiệc Cưới</h3><p>Món ăn chay thanh đạm và tinh tế cho ngày trọng đại của bạn.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$sql_review = "
    SELECT dg.binh_luan, dg.so_sao, kh.ten
    FROM danhgia dg
    LEFT JOIN khach_hang kh ON dg.id_khachhang = kh.id
    WHERE dg.so_sao >= 4 AND dg.binh_luan != ''
    ORDER BY dg.so_sao DESC, RAND() 
    LIMIT 10
";
$list_reviews = $db->select($sql_review);
?>

<?php if ($list_reviews && $list_reviews->num_rows > 0): ?>
<section class="review-section">
    <div class="container">
        <div class="heading-section text-center mb-5">
            <span class="subheading" style="color: #d32f2f; font-weight: bold;">Đánh giá</span>
            <h2 class="mb-4">Khách hàng nói gì về chúng tôi?</h2>
        </div>

        <div class="review-wrapper">
            <button class="review-nav-btn prev" onclick="moveReview(-1)">❮</button>
            <?php 
            $i = 0;
            while ($rv = $list_reviews->fetch_assoc()): 
                $activeClass = ($i == 0) ? 'active' : ''; 
                $starCount = (int)$rv['so_sao'];
                $stars = str_repeat('★', $starCount); 
                $tenKhach = !empty($rv['ten']) ? htmlspecialchars($rv['ten']) : 'Khách hàng ẩn danh';
            ?>
                <div class="review-item <?php echo $activeClass; ?>">
                    <div class="review-stars"><?php echo $stars; ?></div>
                    <div class="quote-icon"><i class="fa fa-quote-left"></i></div>
                    <div class="review-content">
                        "<?php echo htmlspecialchars($rv['binh_luan']); ?>"
                    </div>
                    <h4 class="review-author">- <?php echo $tenKhach; ?> -</h4>
                </div>
            <?php $i++; endwhile; ?>
            <button class="review-nav-btn next" onclick="moveReview(1)">❯</button>
        </div>
    </div>
</section>

<script>
    function moveReview(direction) {
        let items = document.querySelectorAll('.review-item');
        let current = -1;
        for (let i = 0; i < items.length; i++) {
            if (items[i].classList.contains('active')) {
                current = i;
                items[i].classList.remove('active');
                break;
            }
        }
        let next = (current + direction + items.length) % items.length;
        items[next].classList.add('active');
    }
    setInterval(function(){ moveReview(1); }, 6000);
</script>
<?php endif; ?>

<?php include 'inc/footer.php'; ?>