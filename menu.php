<?php include 'inc/header.php'; ?>

    <style>
        input[type=search] {
            border: solid 1px #ccc;
            padding: 9px 10px 9px 32px;
            width: 20rem;
            border-radius: 10px;
        }
        button {
            border: solid 1px #ccc;
            padding-left: 10px;
            width: 10rem;
            height: 51px;
            border-radius: 10px;
        }
        /* THÊM CSS ĐỂ SUB-MENU ĐẸP HƠN */
        .list-group-item.active {
            background-color: #ffb900 !important;
            border-color: #ffb900 !important;
            color: #fff !important;
            font-weight: 600;
        }
    </style>

    <?php
    // ==== Lấy tham số filter & phân trang (dùng GET để giữ tham số khi chuyển trang) ====
    $id_loai = isset($_GET['id_loai']) ? (int)$_GET['id_loai'] : 0;
    $key     = isset($_GET['key']) ? trim($_GET['key']) : ''; // Tham số key dùng cho Sub-category và Search
    $page_search = isset($_GET['search']) ? 1 : 0; // Cờ để phân biệt tìm kiếm từ form và lọc từ sub-menu (search form không cần $page_search, chỉ dùng cho sub-menu)

    $limit  = 30;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // ==== Lấy dữ liệu & tổng số bản ghi (ĐÃ SỬA ĐỔI LOGIC LỌC KÉP) ====
    if ($id_loai > 0 && $key !== '' && $page_search == 0) {
        // 1. Lọc theo cả Loại lớn ($id_loai) VÀ Loại nhỏ ($key) - Dành cho Sub-menu
        $total  = $mon->dem_loai_and_key($id_loai, $key);
        $ds_mon = $mon->get_loai_and_key_trang($id_loai, $key, $limit, $offset);

    } elseif ($key !== '') {
        // 2. Chỉ lọc theo từ khóa tìm kiếm (từ form search, hoặc sub-menu nếu không có id_loai)
        $total  = $mon->dem_key($key);
        $ds_mon = $mon->get_key_trang($key, $limit, $offset);

    } elseif ($id_loai > 0) {
        // 3. Chỉ lọc theo Loại lớn (từ nav tabs, khi chưa chọn sub-menu)
        $total  = $mon->dem_loai($id_loai);
        $ds_mon = $mon->get_loai_trang($id_loai, $limit, $offset);

    } else {
        // 4. Mặc định (Tất cả món ăn)
        $total  = $mon->dem_all();
        $ds_mon = $mon->get_all_trang($limit, $offset);
    }
    $total_pages = max(1, (int)ceil($total / $limit));


    // ==== Hàm hỗ trợ phân trang (ĐÃ CẬP NHẬT THÊM $key & $is_search) ====
    function build_menu_url($page, $id_loai, $key) {
        $params = [];
        if ($id_loai > 0) $params['id_loai'] = $id_loai;
        if ($key !== '')  $params['key'] = $key;
        $params['page'] = $page;
        return 'menu.php?' . http_build_query($params) . '#category-section';
    }

    // Xác định trạng thái active của tab 'Tất cả'
    $active_all = ($id_loai == 0 && $key == '') ? 'active' : '';

    // Lấy tất cả loại món để hiển thị
    $show_loai_list = $loaimon->show_loaimenu();
    $loai_map = [];
    if ($show_loai_list) {
        while ($r = $show_loai_list->fetch_assoc()) {
            $loai_map[$r['id_loai']] = $r['name_loai'];
        }
        // Reset pointer (quan trọng để hiển thị Tabs)
        if ($show_loai_list->num_rows > 0) $show_loai_list->data_seek(0);
    }
    ?>

    <section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
        <div class="overlay"></div>
        <div class="container">
            <div class="row no-gutters slider-text align-items-end justify-content-center">
                <div class="col-md-9 ftco-animate text-center mb-4">
                    <h1 class="mb-2 bread">MENU MÓN ĂN</h1>
                    <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Menu <i class="ion-ios-arrow-forward"></i></span></p>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section">
        <div class="container">

            <div class="row">
                <div class="col-md-12 nav-link-wrap mb-4" id="category-section">
                    <div class="nav nav-pills d-flex text-center" id="v-pills-tab" role="tablist">
                        <a class="nav-link ftco-animate <?php echo $active_all ?>"
                        href="menu.php#category-section" role="tab">
                        Tất cả món ăn
                        </a>
                        
                        <?php
                            if (!empty($loai_map)) {
                                foreach ($loai_map as $id => $name) {
                                    // Loại bỏ tham số key khi chuyển tab loại lớn
                                    $is_active = ($id_loai == $id) ? 'active' : '';
                                    $href = 'menu.php?id_loai='.$id;
                                    $href .= '#category-section';
                        ?>
                            <a class="nav-link ftco-animate <?php echo $is_active ?>"
                            href="<?php echo $href; ?>" role="tab">
                                <?php echo htmlspecialchars($name); ?>
                            </a>
                        <?php
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php
                // HARDCODE DANH SÁCH SUB-CATEGORY TẠI ĐÂY
                // Key trong array này PHẢI LÀ id_loai. Value là array các TỪ KHÓA TÊN MÓN trong database
                $sub_categories = [
                    28 => ['Súp', 'Gỏi', 'Salad', 'Bánh Mì', 'Chả Giò', 'Khoai Tây'], // Ví dụ: Món Khai vị (id=28)
                    29 => ['Cơm', 'Bún', 'Mì', 'Phở', 'Lẩu', 'Thịt Kho', 'Beef Steak'], // Ví dụ: Món Chính (id=29)
                    30 => ['Bánh Mousse', 'Panna Cotta', 'Tiramisu'], // Ví dụ: Món Tráng miệng (id=30)
                    31 => ['Trà', 'Sinh Tố', 'Nước ép', 'Sữa'], // Ví dụ: Nước uống (id=31)
                    32 => ['Bia', 'Rượu', 'Cocktail'], // Ví dụ: Đồ uống có cồn (id=32)
                ];
                $current_subs = isset($sub_categories[$id_loai]) ? $sub_categories[$id_loai] : [];
                $current_key = strtolower($key);
                ?>

                <?php if ($id_loai > 0 && !empty($current_subs)): ?>
                    <div class="col-md-3 ftco-animate">
                        <h4 class="mb-3 text-primary">Lọc theo loại nhỏ</h4>
                        <div class="list-group">
                            <a href="menu.php?id_loai=<?php echo $id_loai; ?>#category-section" class="list-group-item list-group-item-action<?php echo ($key === '') ? ' active' : ''; ?>">
                                Tất cả món
                            </a>
                            
                            <?php foreach ($current_subs as $sub_name):
                                // Link sẽ truyền id_loai và dùng $sub_name làm $key
                                $sub_href = 'menu.php?id_loai='.$id_loai.'&key='.urlencode($sub_name).'#category-section';
                                $sub_active = ($current_key === strtolower($sub_name)) ? ' active' : '';
                            ?>
                                <a href="<?php echo $sub_href; ?>" class="list-group-item list-group-item-action<?php echo $sub_active; ?>">
                                    <?php echo htmlspecialchars($sub_name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $main_col_class = "col-md-9"; // Cột chính là 9/12 ?>
                <?php else: ?>
                    <?php $main_col_class = "col-md-12"; // Nếu không có sub-menu, cột chính là 12/12 ?>
                <?php endif; ?>

                <div class="<?php echo $main_col_class; ?> tab-wrap ftco-animate">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-1" role="tabpanel">

                            <div class="row no-gutters d-flex align-items-stretch">
                                <?php
                                    if ($ds_mon && $total > 0) {
                                        while ($mn = $ds_mon->fetch_assoc()) {
                                            $img = !empty($mn['images']) ? $mn['images'] : 'placeholder.jpg';
                                ?>
                                <div class="col-md-12 col-lg-6 d-flex align-self-stretch">
                                    <div class="menus d-sm-flex ftco-animate align-items-stretch">
                                        <div class="menu-img img" style="background-image: url('images/food/<?php echo $img; ?>');"></div>
                                        <div class="text d-flex align-items-center">
                                            <div>
                                                <div class="d-flex">
                                                    <div class="one-half">
                                                        <h3><?php echo htmlspecialchars($mn['name_mon']); ?></h3>
                                                    </div>
                                                    <div class="one-forth">
                                                        <span class="price"><?php echo $fm->formatMoney($mn['gia_mon']); ?></span>
                                                    </div>
                                                </div>
                                                <p><a href="detail.php?monid=<?php echo (int)$mn['id_mon']; ?>" class="btn btn-primary">Xem và đặt món</a></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                        }
                                    } else {
                                        echo "<p class='text-center w-100'>Không tìm thấy món ăn phù hợp.</p>";
                                    }
                                ?>
                            </div> <?php if ($total_pages > 1) { ?>
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?php echo ($page > 1) ? build_menu_url($page-1, $id_loai, $key) : '#'; ?>">«</a>
                                        </li>

                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);

                                        if ($start > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="'.build_menu_url(1, $id_loai, $key).'">1</a></li>';
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }

                                        for ($i = $start; $i <= $end; $i++) {
                                            $active = ($i == $page) ? 'active' : '';
                                            echo '<li class="page-item '.$active.'"><a class="page-link" href="'.build_menu_url($i, $id_loai, $key).'">'.$i.'</a></li>';
                                        }

                                        if ($end < $total_pages) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            echo '<li class="page-item"><a class="page-link" href="'.build_menu_url($total_pages, $id_loai, $key).'">'.$total_pages.'</a></li>';
                                        }
                                        ?>

                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?php echo ($page < $total_pages) ? build_menu_url($page+1, $id_loai, $key) : '#'; ?>">»</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.location.hash === "#category-section") {
                var section = document.getElementById("category-section");
                if (section) {
                    setTimeout(function() {
                        section.scrollIntoView({ behavior: "smooth", block: "center" });
                    }, 100);
                }
            }
        });
    </script>

<?php include 'inc/footer.php'; ?>