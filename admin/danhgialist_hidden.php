<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include_once '../helpers/format.php'; ?>
<?php include_once '../lib/database.php'; ?>
<?php include_once __DIR__ . '/../classes/danhgia.php';?>

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

/* ========== KHÔI PHỤC đánh giá (xóa mềm -> bình thường) ========== */
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    if ($id > 0) {
        $dgv = new DanhGia();                     // dùng class
        $res = $dgv->restoreReview($id);          // gọi HÀM khôi phục theo id_danhgia

        // Popup + giữ nguyên trang (remove ?restore=... khỏi URL)
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        (function() {
          const ok  = " . (!empty($res['ok']) ? 'true' : 'false') . ";
          const msg = " . json_encode($res['msg'] ?? '') . ";
          const id  = " . json_encode($id) . ";

          Swal.fire({
            icon: ok ? 'success' : 'error',
            title: ok ? 'Đã khôi phục!' : 'Không thể khôi phục',
            text: msg || (ok ? ('Đánh giá #' + id + ' đã được khôi phục.') : 'Vui lòng thử lại.'),
            timer: 2200,
            timerProgressBar: true,
            confirmButtonText: 'OK'
          }).then(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('restore');
            window.location.replace(url.toString());
          });
        })();
        </script>";
        exit;
    }
}

/* ========== LẤY DANH SÁCH ĐÁNH GIÁ ĐÃ XÓA MỀM ========== */
$sql = "
    SELECT 
        dg.id_danhgia, dg.id_hopdong, dg.id_khachhang,
        dg.so_sao, dg.binh_luan, dg.hinhanh,
        kh.ten AS ten_kh, kh.sodienthoai,
        h.dates
    FROM danhgia dg
    JOIN khach_hang kh ON kh.id = dg.id_khachhang
    JOIN hopdong h ON h.id = dg.id_hopdong
    WHERE dg.xoa = 1
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
    .btn-restore { background-color: #28a745; color: #fff; }
    .btn-restore:hover { background-color: #1e7e34; }
    .btn-back { background-color: #6c757d; color: #fff; }
    .btn-back:hover { background-color: #5a6268; }

    td, th { text-align: center !important; vertical-align: middle !important; }
    .nowrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
    .thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
    .star { color: #f5a623; font-size: 15px; }

    #example thead th { background: #aa5252ff !important; }
    #example tbody td { background: #ffffff; }
    #example thead th, #example tbody td { border: 1px solid #e6e6e6; padding: 10px 12px; }
    #example td img.thumb {
        display: block;
        width: 60px; height: 60px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #eee;
        background: #fff;
    }
    table.dataTable thead .sorting:before,
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:before,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:before,
    table.dataTable thead .sorting_desc:after { opacity: 0.6; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>🗑️ Danh sách đánh giá đã xóa</h2>

        <div class="block" id="table-container">
            <a href="danhgialist.php" class="btn-action btn-back" style="margin-bottom:10px;">
                ⬅️ Quay lại danh sách chính
            </a>

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
                            $ten_kh  = (string)($r['ten_kh'] ?? '');
                            $sdt     = (string)($r['sodienthoai'] ?? '');
                            $sao     = (int)($r['so_sao'] ?? 0);
                            $bluan   = (string)($r['binh_luan'] ?? '');
                            $imgName = trim((string)($r['hinhanh'] ?? ''));
                            $imgUrl  = $imgName !== '' ? ('../images/danhgia/' . $imgName) : '';
                            $dates   = (string)($r['dates'] ?? '');

                            $safeTenKH = htmlspecialchars($ten_kh, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeSDT   = htmlspecialchars($sdt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeDate  = htmlspecialchars($dates, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeCommentFull  = htmlspecialchars($bluan, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeCommentShort = htmlspecialchars(mb_strimwidth($bluan, 0, 60, '...', 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                            $starCount = max(0, min(5, $sao));
                            $stars = str_repeat('★', $starCount) . str_repeat('☆', 5 - $starCount);
                            ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td>#<?= $id_hd ?></td>
                                <td class="nowrap"><?= $safeTenKH ?></td>
                                <td><?= $safeSDT ?></td>
                                <td><span class="star"><?= $stars ?></span></td>
                                <td class="nowrap" title="<?= $safeCommentFull ?>"><?= $safeCommentShort ?></td>
                                <td>
                                    <?php if ($imgUrl): ?>
                                        <a href="<?= htmlspecialchars($imgUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">
                                            <img class="thumb" src="<?= htmlspecialchars($imgUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="hinhanh">
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= $safeDate ?></td>
                                <td>
                                    <a class="btn-action btn-restore"
                                    onclick="return confirm('Khôi phục đánh giá #<?= $id_dg ?>?')"
                                    href="?restore=<?= $id_dg ?>">
                                    Khôi phục
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    // else: KHÔNG in <tr><td colspan="9">...</td></tr> để tránh lỗi DataTables
                    ?>
                    </tbody>

            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $('#example').DataTable({
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
            { orderable: false, targets: [6, 8] } // Ảnh (index 6) & Hành động (index 8)
        ]
    });

    if (typeof setSidebarHeight === 'function') setSidebarHeight();
});
</script>

<?php include 'inc/footer.php'; ?>
