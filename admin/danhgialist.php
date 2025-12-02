<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include_once '../helpers/format.php'; ?>
<?php include_once '../lib/database.php'; ?>
<?php
// Chặn truy cập chưa đăng nhập admin
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}

$fm = new Format();
$db = new Database();
if (isset($db->link) && $db->link instanceof mysqli) {
    @$db->link->set_charset('utf8mb4');
}

/* ========== XÓA đánh giá (hard delete) ========== */
// ... (đầu file đã include $db, v.v.)

if (isset($_GET['delid'])) {
    $id = (int)$_GET['delid'];
    if ($id > 0) {
        // XÓA MỀM: đặt xoa = 1 cho đúng bản ghi chưa bị xóa
        $sql = "UPDATE danhgia 
                SET xoa = 1 
                WHERE id_danhgia = {$id} 
                  AND (xoa IS NULL OR xoa = 0)
                LIMIT 1";
        $ok = $db->update($sql);

        // Popup + giữ nguyên trang (remove ?delid=... khỏi URL)
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        (function() {
          const ok = " . ($ok ? 'true' : 'false') . ";
          const id = " . json_encode($id) . ";
          const title = ok ? 'Đã xóa thành công!' : 'Không thể xóa';
          const text  = ok ? ('Đánh giá #' + id + ' đã được xóa.') 
                           : ('Vui lòng thử lại hoặc kiểm tra trạng thái.');

          Swal.fire({
            icon: ok ? 'success' : 'error',
            title: title,
            text: text,
            timer: 2200,
            timerProgressBar: true,
            confirmButtonText: 'OK'
          }).then(() => {
            // Xóa param delid để tránh xóa lại khi refresh
            const url = new URL(window.location.href);
            url.searchParams.delete('delid');
            // Giữ nguyên ngay trang danh sách:
            window.location.replace(url.toString());
          });
        })();
        </script>";
        exit;
    }
}


/* ========== LẤY DANH SÁCH ĐÁNH GIÁ ========== */
$sql = "
    SELECT 
        dg.id_danhgia, dg.id_hopdong, dg.id_khachhang,
        dg.so_sao, dg.binh_luan, dg.hinhanh,
        kh.ten AS ten_kh, kh.sodienthoai,
        h.dates
    FROM danhgia dg
    JOIN khach_hang kh ON kh.id = dg.id_khachhang
    JOIN hopdong h ON h.id = dg.id_hopdong
    WHERE dg.xoa = 0
    ORDER BY dg.id_danhgia DESC
";
$reviews = $db->select($sql);
?>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
    .btn-action {
        display: inline-block;
        padding: 6px 12px;
        margin: 2px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        text-align: center;
    }
    .btn-edit { background-color: #007bff; color: #fff; }
    .btn-edit:hover { background-color: #0056b3; }
    .btn-delete { background-color: #dc3545; color: #fff; }
    .btn-delete:hover { background-color: #a71d2a; }

    td, th { text-align: center !important; vertical-align: middle !important; }
    .nowrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
    .thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
    .star { color: #f5a623; font-size: 15px; }


    /* Đồng bộ màu nền header/body để không thấy “mảng trắng” */
    #example thead th {
    background: #aa5252ff !important;        /* màu xám nhạt như ảnh header */
    }
    #example tbody td {
    background: #ffffff;                    /* nền trắng thống nhất cho ô */
    }

    /* Kẻ viền mảnh để không thấy khoảng trắng như rãnh */
    #example thead th,
    #example tbody td {
    border: 1px solid #e6e6e6;
    }

    /* Giữ chữ không bị đè sát cạnh (tránh tưởng khoảng trắng) */
    #example thead th,
    #example tbody td {
    padding: 10px 12px;
    }

    /* Nếu ô ảnh bị “ô trắng” do thiếu width/height -> ép thumbnail nhất quán */
    #example td img.thumb {
    display: block;
    width: 60px; height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #eee;
    background: #fff;                      /* nền trắng tránh lộ khoảng trống */
    }

    /* Tránh DataTables chèn icon sort lệch nền (để icon nổi đúng trên header) */
    table.dataTable thead .sorting:before,
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:before,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:before,
    table.dataTable thead .sorting_desc:after {
    opacity: 0.6;                           /* nhìn rõ trên nền xám */
    }

</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Danh sách đánh giá</h2>

        <div class="block" id="table-container">
            <?php if (!empty($flash)): ?>
                <p style="color:<?= strpos($flash, 'Đã xóa') !== false ? 'green' : 'red' ?>; font-weight:bold;">
                    <?= htmlspecialchars($flash) ?>
                </p>
            <?php endif; ?>

            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>ID HĐ</th>
                        <th>Khách hàng</th>
                        <th>SĐT</th>
                        <th>Số sao</th>
                        <th>Bình luận</th>
                        <th>Ảnh</th>
                        <th>Ngày đặt</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($reviews && $reviews->num_rows > 0) {
                    $i = 0;
                    while ($r = $reviews->fetch_assoc()) {
                        $i++;
                        $id_dg   = (int)$r['id_danhgia'];
                        $id_hd   = (int)$r['id_hopdong'];
                        $ten_kh  = $r['ten_kh'] ?? '';
                        $sdt     = $r['sodienthoai'] ?? '';
                        $sao     = (int)($r['so_sao'] ?? 0);
                        $bluan   = $r['binh_luan'] ?? '';
                        $imgName = trim((string)($r['hinhanh'] ?? ''));
                        $imgUrl  = $imgName !== '' ? ('../images/danhgia/' . $imgName) : '';
                        $dates   = $r['dates'] ?? '';

                        // render sao
                        $stars = str_repeat('★', max(0, min(5, $sao)));
                        $stars .= str_repeat('☆', 5 - max(0, min(5, $sao)));
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td>#<?= $id_hd ?></td>
                            <td class="nowrap"><?= htmlspecialchars($ten_kh) ?></td>
                            <td><?= htmlspecialchars($sdt) ?></td>
                            <td><span class="star"><?= $stars ?></span></td>
                            <td class="nowrap" title="<?= htmlspecialchars($bluan) ?>">
                                <?= htmlspecialchars(mb_strimwidth($bluan, 0, 60, '...', 'UTF-8')) ?>
                            </td>
                            <td>
                                <?php if ($imgUrl): ?>
                                    <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank">
                                        <img class="thumb" src="<?= htmlspecialchars($imgUrl) ?>" alt="hinhanh">
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($dates) ?></td>
                            <td>
                                <a class="btn-action btn-edit" href="danhgiaedit.php?id=<?= $id_dg ?>">Sửa</a>
                                <a class="btn-action btn-delete" 
                                   onclick="return confirm('Xóa đánh giá #<?= $id_dg ?>? Hành động này không thể hoàn tác.')" 
                                   href="?delid=<?= $id_dg ?>">Xóa</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="9">Chưa có đánh giá nào.</td></tr>';
                }
                ?>
                </tbody>
            </table>
            <a href="danhgialist_hidden.php" class="btn btn-outline-danger">
                <i class="fa fa-trash"></i> Đánh giá đã xóa
            </a>

        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    const table = $('#example').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: {
            search: "",
            searchPlaceholder: "Tìm theo tên KH, SĐT, bình luận...",
            paginate: { previous: "Trang trước", next: "Trang sau" },
            info: "Hiển thị _START_–_END_ trong _TOTAL_ đánh giá",
            emptyTable: "Không có dữ liệu",
            infoEmpty: "Không có dữ liệu để hiển thị"
        },
        columnDefs: [
            { orderable: false, targets: [6,8] } // Ảnh, Hành động không sắp xếp
        ]
    });

    table.on('page.dt', function () {
        $('html, body').animate({ scrollTop: $('#table-container').offset().top - 20 }, 400);
    });

    if (typeof setSidebarHeight === 'function') setSidebarHeight();
});
</script>

<?php include 'inc/footer.php'; ?>
