<?php
include 'inc/header.php';

if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
if (!isset($db) || !isset($db->link)) { $db = new Database(); @$db->link->set_charset('utf8mb4'); }

$menu_rs = $db->select("
  SELECT id_menu, ten_menu, ghi_chu, hinhanh
  FROM menu
  WHERE trang_thai = 0
  ORDER BY id_menu ASC
");

// Link Đặt ngay theo trạng thái đăng nhập (không sửa logic có sẵn của bạn)
$logged = (bool)(Session::get('customer_login') ?? Session::get('userlogin') ?? false);
$returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
$linkDatNgay = $logged ? 'datban.php' : ('login.php?return=' . $returnUrl);

if (isset($_GET['msg']) && $_GET['msg'] === 'ThanhCong') {
    echo "<script>alert('Thanh toán thành công!');</script>";
}

include_once 'classes/KhuyenMai.php';
$km = new KhuyenMai();
$ds_km = $km->show_km_active();
?>

<style>
  .km-header{ text-align:center;font-size:100px;font-weight:bold;color:#d32f2f;text-transform:uppercase;letter-spacing:1.5px;margin-top:12px;margin-bottom:10px }
  .banner-wrapper{position:relative;width:100%;max-width:1000px;height:300px;margin:12px auto;overflow:hidden;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,.15)}
  .banner-slide{position:absolute;width:100%;height:100%;opacity:0;transition:opacity .7s ease-in-out}
  .banner-slide.active{opacity:1;z-index:2}
  .banner-link{display:block;width:100%;height:100%;cursor:pointer}
  .banner-link img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
  .banner-link:hover img{transform:scale(1.02);opacity:.95}
  .discount-text{position:absolute;bottom:20px;right:20px;background:rgba(255,0,0,.75);color:#fff;padding:8px 16px;font-size:20px;font-weight:700;border-radius:5px;z-index:3;pointer-events:none}
  .date-range{position:absolute;bottom:20px;left:20px;background:rgba(255,255,255,.9);color:#000;padding:8px 16px;font-size:16px;font-weight:500;border-radius:5px;z-index:3;pointer-events:none}
  .banner-prev,.banner-next{position:absolute;top:50%;transform:translateY(-50%);width:40px;height:40px;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:24px;font-weight:bold;cursor:pointer;z-index:5;display:flex;align-items:center;justify-content:center;transition:background .3s;border-radius:4px}
  .banner-prev{left:20px}.banner-next{right:20px}
  .banner-prev:hover,.banner-next:hover{background:rgba(0,0,0,.85);box-shadow:0 0 6px rgba(255,255,255,.3)}
  @media (max-width:768px){.banner-wrapper{height:180px}.discount-text{font-size:16px;padding:6px 12px}.date-range{font-size:12px;padding:5px 10px;bottom:15px;left:10px}.banner-prev,.banner-next{width:32px;height:32px;font-size:20px}}
  .line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
  .about-preview .img{background-size:cover;background-position:center;border-radius:12px;min-height:180px;flex:1}
  @media (max-width:768px){.about-preview .img{min-height:140px}}
  /* panel chi tiết combo */
  .combo-detail .combo-detail-table{width:100%;border-collapse:collapse}
  .combo-detail .combo-detail-table th,.combo-detail .combo-detail-table td{border:1px solid #eceff1;padding:8px 10px}
  .combo-detail .combo-detail-table thead th{background:#f6f8fa;font-weight:600}
  .combo-detail .combo-total{margin-top:8px;font-weight:700;text-align:left}

  /* ====== COMBO NỔI BẬT (INDEX) ====== */
  .combo-box .menu-img.img{
    background-size:cover;background-position:center;border-radius:12px;min-height:180px;flex:1
  }
  .combo-box .text{ padding:12px 14px; }
  .combo-box .btn-xemthem{ white-space:nowrap; }
  .combo-detail{ display:none; margin-top:8px; }
  .combo-detail.open{ display:block; }
  .combo-detail .combo-detail-table{ width:100%; border-collapse:collapse }
  .combo-detail .combo-detail-table th,.combo-detail .combo-detail-table td{ border:1px solid #eceff1; padding:8px 10px }
  .combo-detail .combo-detail-table thead th{ background:#f6f8fa; font-weight:600 }
  .combo-detail .combo-total{ margin-top:8px; font-weight:700; text-align:left }

  /* Sửa lỗi tràn bảng chi tiết combo */
/* ========= POLISH COMBO NỔI BẬT (KHÔNG ĐỔI JS/PHP/HTML) ========= */

/* Tạo khoảng thở giữa các card trong hàng "no-gutters" */
.row.no-gutters.d-flex.align-items-stretch > [class*="col-"] {
  padding: 10px 12px;
}

/* Card nhìn gọn hơn, không nhảy */
.menus.ftco-animate.combo-box{
  width: 100%;
  border: 1px solid #eaeaea;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  overflow: hidden;
}

/* Ảnh cố định tỉ lệ và không “co giật” khi mở/đóng chi tiết */
.combo-box .menu-img{
  min-height: 230px;            /* đồng đều chiều cao ảnh */
  background-size: cover;
  background-position: center;
}

/* Chia cột ổn định hơn ở màn rộng */
@media (min-width: 992px){
  .menus.ftco-animate.combo-box{
    display: grid;
    grid-template-columns: 42% 58%;
    grid-template-areas:
      "img content"
      "footer footer";
  }
  .combo-box .menu-img{ grid-area: img; }
  .combo-box .text{ grid-area: content; border-left: 1px solid #eee; }
  .combo-box .combo-footer{ grid-area: footer; border-top: 1px solid #eee; }
}

/* Nội dung bên phải */
.combo-box .text{
  padding: 14px 16px;
  background: #fff;
  box-sizing: border-box;
}
.combo-box .text h3{
  margin: 0 0 6px;
  font-weight: 700;
}

/* Hàng dưới: căn giữa nút, tạo khoảng cách đẹp */
.combo-box .combo-footer{
  width: 100%;
  background: #fff;
  padding: 10px 12px 12px;
  box-sizing: border-box;
}
.combo-box .combo-footer .d-flex{
  justify-content: center;      /* căn giữa 2 nút, giữ nguyên HTML cũ */
  gap: 10px;
}
.combo-box .combo-footer .btn{
  min-width: 120px;
}

/* Panel chi tiết: mép, bóng nhẹ, không tràn, nhìn sạch */
.combo-box .combo-footer .combo-detail{
  margin-top: 10px;
  border: 1px solid #eceff1;
  border-radius: 8px;
  background: #fff;
  padding: 8px 10px;
  overflow-x: auto;             /* nếu bảng rộng thì cuộn ngang, không bể layout */
  box-shadow: 0 1px 6px rgba(0,0,0,.04);
}

/* Bảng bên trong panel */
.combo-box .combo-footer .combo-detail-table{
  width: 100%;
  border-collapse: collapse;
}
.combo-box .combo-footer .combo-detail-table th,
.combo-box .combo-footer .combo-detail-table td{
  border: 1px solid #eceff1;
  padding: 8px 10px;
}
.combo-box .combo-footer .combo-detail-table thead th{
  background: #f7f9fb;
  font-weight: 600;
}
.combo-box .combo-footer .combo-total{
  margin-top: 8px;
  font-weight: 700;
  text-align: left;
}

/* Mobile: xếp dọc, vẫn đẹp */
@media (max-width: 991.98px){
  .menus.ftco-animate.combo-box{
    display: grid;
    grid-template-columns: 1fr;
    grid-template-areas:
      "img"
      "content"
      "footer";
  }
  .combo-box .menu-img{ min-height: 190px; }
  .combo-box .text{ border-left: none; border-top: 1px solid #eee; }
  .combo-box .combo-footer{ border-top: none; border-top: 1px solid #eee; }
}
/* ===== FIX ẢNH COMBO BỊ MÓP / ÉP TỈ LỆ ===== */
.combo-box .menu-img {
  aspect-ratio: 4 / 3;        /* Giữ tỉ lệ ngang 4:3 tự nhiên */
  height: auto;               /* Không ép chiều cao cứng */
  min-height: unset;          /* Bỏ giới hạn cũ 220px */
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
  overflow: hidden;
  background-size: cover;
  background-position: center center;
}

/* Nếu ảnh vẫn hơi dọc, có thể dùng tỉ lệ 3:2 */
@media (min-width: 992px){
  .combo-box .menu-img { aspect-ratio: 3 / 2; }
}

/* ==== FIX FINAL: GIỮ ẢNH ĐẸP, KHÔNG BỊ MÓP VÀ KHÔNG QUÁ BÉ ==== */
.combo-box .menu-img {
  aspect-ratio: unset;     /* bỏ giới hạn tỉ lệ */
  min-height: 220px;       /* đảm bảo chiều cao cố định đồng đều */
  height: 100%;
  background-size: cover;
  background-position: center center;
}
@media (min-width: 992px){
  .menus.ftco-animate.combo-box{
    display: grid;
    grid-template-columns: 50% 50%;   /* chia đôi đều 2 cột */
    grid-template-areas:
      "img content"
      "footer footer";
  }
}

/* khối ảnh chiếm toàn bộ chiều cao bên trái */
.combo-box .menu-img {
  width: 100%;
  height: 100%;
  min-height: 260px;                /* chiều cao tối thiểu, tuỳ bạn tăng/giảm */
  background-size: cover;
  background-position: center center;
  border-radius: 0;                 /* để liền mạch với khung */
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

/* Giữ nội dung không dính sát ảnh */
.combo-box .text {
  padding: 20px;
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* Mobile: vẫn giữ layout dọc như cũ */
@media (max-width: 991.98px){
  .menus.ftco-animate.combo-box{
    grid-template-columns: 1fr;
    grid-template-areas:
      "img"
      "content"
      "footer";
  }
  .combo-box .menu-img{
    min-height: 200px;
    border-radius: 10px 10px 0 0;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Banner slider
  const slides = document.querySelectorAll('.banner-slide');
  const prevBtn = document.querySelector('.banner-prev');
  const nextBtn = document.querySelector('.banner-next');
  let index = 0, interval;
  function showSlide(i){ slides.forEach(s=>s.classList.remove('active')); slides[i].classList.add('active'); }
  function nextSlide(){ index = (index + 1) % slides.length; showSlide(index); }
  function prevSlide(){ index = (index - 1 + slides.length) % slides.length; showSlide(index); }
  function resetInterval(){ clearInterval(interval); interval = setInterval(nextSlide, 5000); }
  if (slides.length > 1) {
    interval = setInterval(nextSlide, 5000);
    if (nextBtn) nextBtn.addEventListener('click', ()=>{ nextSlide(); resetInterval(); });
    if (prevBtn) prevBtn.addEventListener('click', ()=>{ prevSlide(); resetInterval(); });
  }
});
</script>

<section class="home-slider owl-carousel">
  <div class="slider-item" style="background-image: url(images/bg3.jpg);" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
      <div class="row slider-text justify-content-center align-items-center">
        <div class="col-md-7 col-sm-12 text-center ftco-animate">
          <h1 class="mb-3 mt-5 bread">TRisKiet Quán</h1>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ==== LẤY COMBO HOẠT ĐỘNG ====
if (!class_exists('Database')) require_once __DIR__ . '/lib/database.php';
if (!isset($db) || !isset($db->link)) { $db = new Database(); @$db->link->set_charset('utf8mb4'); }

$menu_rs = $db->select("
  SELECT id_menu, ten_menu, ghi_chu, hinhanh
  FROM menu
  WHERE trang_thai = 0
  ORDER BY id_menu ASC
");

// Link Đặt ngay theo trạng thái đăng nhập
$logged = (bool)(Session::get('customer_login') ?? Session::get('userlogin') ?? false);
$returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
$linkDatNgay = $logged ? 'datban.php' : ('login.php?return=' . $returnUrl);
?>

<section class="ftco-section">
  <div class="container">
    <div class="row no-gutters justify-content-center mb-5 pb-2">
      <div class="col-md-12 text-center heading-section ftco-animate">
        <span class="subheading">Các combo nổi bật</span>
        <h2 class="mb-4"></h2>
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
            <!-- Hàng trên: ảnh & nội dung -->
            <div class="menu-img img" style="background-image:url('<?php echo htmlspecialchars($img); ?>')"></div>
            <div class="text">
              <h3><?php echo htmlspecialchars($ten_menu); ?></h3>
              <?php if ($ghichu !== ''): ?>
                <p><span><?php echo htmlspecialchars($ghichu); ?></span></p>
              <?php endif; ?>
            </div>

            <!-- Hàng dưới: nút + chi tiết -->
            <div class="combo-footer">
              <div class="d-flex gap-2 mb-2">
                <center>
                <button type="button"
                        class="btn btn-outline-secondary mr-2 btn-xemthem"
                        data-url="hopdong_menu.php?ajax=combo_detail_html&id=<?php echo $id_menu; ?>"
                        data-id="<?php echo $id_menu; ?>">
                  Xem thêm
                </button>
                <a href="<?php echo htmlspecialchars($linkDatNgay); ?>" class="btn btn-primary">Đặt ngay</a>
                </center>
              </div>

              <!-- Panel chi tiết -->
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

  // mở panel từ nút Xem thêm (đổ xuống)
  document.querySelectorAll('.btn-xemthem').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const card   = this.closest('.combo-box');
      const panel  = card.querySelector('.combo-detail');
      const loading= panel.querySelector('.detail-loading');
      const body   = panel.querySelector('.detail-body');
      const url    = this.dataset.url;
      const id     = this.dataset.id;

      const isOpen = panel.style.display !== 'none';
      if (isOpen) {
        panel.style.display = 'none';
        // xóa ?menu khỏi URL khi đóng
        const usp = new URLSearchParams(window.location.search);
        usp.delete('menu');
        history.replaceState(null, '', window.location.pathname + (usp.toString() ? ('?' + usp.toString()) : ''));
        return;
      }

      panel.style.display = 'block';
      // cập nhật ?menu=<id> lên URL khi mở
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

  // nếu URL có ?menu=ID => tự mở combo đó
  const params = new URLSearchParams(window.location.search);
  const autoId = params.get('menu');
  if (autoId) {
    const targetBtn = document.querySelector(`.btn-xemthem[data-id="${autoId}"]`);
    if (targetBtn) targetBtn.click();
  }
});

document.addEventListener('DOMContentLoaded', function(){
  async function fetchComboDetailHTML(url){
    const res = await fetch(url, {cache:'no-store'});
    if(!res.ok) throw new Error('HTTP ' + res.status);
    return await res.text();
  }

  // Bắt sự kiện: Xem thêm => mở/đóng panel + lazy load HTML
  document.querySelectorAll('.btn-xemthem').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const card    = this.closest('.combo-box');
      const panel   = card.querySelector('.combo-detail');
      const loading = panel.querySelector('.detail-loading');
      const body    = panel.querySelector('.detail-body');
      const url     = this.dataset.url;
      const id      = this.dataset.id;

      // Toggle open/close
      if (panel.classList.contains('open')) {
        panel.classList.remove('open');
        // Xoá ?menu khỏi URL khi đóng
        const usp = new URLSearchParams(window.location.search);
        usp.delete('menu');
        history.replaceState(null, '', window.location.pathname + (usp.toString() ? ('?' + usp.toString()) : ''));
        return;
      }
      panel.classList.add('open');

      // Cập nhật ?menu=<id> lên URL khi mở
      const usp = new URLSearchParams(window.location.search);
      usp.set('menu', id);
      history.replaceState(null, '', window.location.pathname + '?' + usp.toString());

      // Nếu chưa load thì nạp HTML chi tiết
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

  // Tự mở combo nếu URL có ?menu=ID (tiện cho share link)
  const params = new URLSearchParams(window.location.search);
  const autoId = params.get('menu');
  if (autoId) {
    const targetBtn = document.querySelector(`.btn-xemthem[data-id="${autoId}"]`);
    if (targetBtn) targetBtn.click();
  }
});
</script>

<div id="km-banner">
  <?php $ds_km = $km->show_km_active(); if ($ds_km): ?>
    <h2 class="km-header">Khuyến mãi</h2>
    <div style="text-align:center;">
      <small style="color:#888; font-style:italic;">Áp dụng cho đơn hàng mua tại quán</small>
    </div>
    <div class="banner-wrapper">
      <?php $i=0; while ($row = $ds_km->fetch_assoc()):
        $img = trim($row['images']); if (!$img) continue;
        $km_id = $row['id_km'];
        $discount = (float)$row['discout'];
        $time_star = date('d/m/Y', strtotime($row['time_star']));
        $time_end  = date('d/m/Y', strtotime($row['time_end']));
        $active = ($i === 0) ? 'active' : '';
      ?>
        <div class="banner-slide <?php echo $active; ?>">
          <a href="khuyenmaidetail.php?kmid=<?php echo $km_id; ?>" class="banner-link">
            <img src="images/food/<?php echo htmlspecialchars($img); ?>" alt="Khuyến mãi">
          </a>
          <div class="discount-text"><?php echo $discount ?>%</div>
          <div class="date-range"><?php echo $time_star . ' - ' . $time_end; ?></div>
        </div>
      <?php $i++; endwhile; ?>
      <button class="banner-prev">&#10094;</button>
      <button class="banner-next">&#10095;</button>
    </div>
  <?php else: ?>
    <p class="no-km">Không có khuyến mãi nào.</p>
  <?php endif; ?>
</div>

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
        <p class="line-clamp-3">
          Chúng tôi mang đến những món ăn chay tinh túy, được chế biến từ nguyên liệu tươi ngon,
          mang lại sự hài lòng cho mỗi thực khách và giúp cơ thể bạn thêm khỏe mạnh.
        </p>
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
          <div class="icon d-flex justify-content-center align-items-center">
            <span class="flaticon-cake"></span>
          </div>
          <div class="media-body p-2 mt-3">
            <h3 class="heading">Tiệc Sinh Nhật</h3>
            <p>Chúng tôi cung cấp những món ăn tuyệt vời cho bữa tiệc sinh nhật của bạn, mang đến niềm vui và sự khỏe mạnh cho tất cả mọi người.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 d-flex align-self-stretch ftco-animate text-center">
        <div class="media block-6 services d-block">
          <div class="icon d-flex justify-content-center align-items-center">
            <span class="flaticon-meeting"></span>
          </div>
          <div class="media-body p-2 mt-3">
            <h3 class="heading">Cuộc Họp Kinh Doanh</h3>
            <p>Hãy thưởng thức những món ăn chay tinh tế trong các cuộc họp kinh doanh, giúp bạn và đối tác cảm thấy thư giãn và tập trung hơn.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 d-flex align-self-stretch ftco-animate text-center">
        <div class="media block-6 services d-block">
          <div class="icon d-flex justify-content-center align-items-center">
            <span class="flaticon-tray"></span>
          </div>
          <div class="media-body p-2 mt-3">
            <h3 class="heading">Tiệc Cưới</h3>
            <p>Chúng tôi mang đến những món ăn chay thanh đạm và tinh tế cho tiệc cưới của bạn, mang lại không khí ấm cúng và trọn vẹn cho ngày trọng đại.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'inc/footer.php'; ?>
