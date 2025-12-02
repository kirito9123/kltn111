<?php
include 'inc/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cart = $_POST['cartid'];
    $soluong = $_POST['soluong'];
    $updatecart = $ct->update_cart($soluong, $id_cart);
    header('location:cartt.php');
}
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delcart = $ct->del_loai($id);
    header('location:cartt.php');
}
?>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Giỏ hàng</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thanh toán <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section ftco-cart">
    <div class="container">
        <div class="row">
            <div class="col-md-12 ftco-animate">
                <div class="cart-list">
                    <?php
                    $get_cart = $ct->get_cart();
                    if ($get_cart && $get_cart->num_rows > 0) {
                    ?>
                        <table class="table">
                            <thead class="thead-primary">
                                <tr class="text-center">
                                    <th>Hình ảnh</th>
                                    <th>Món ăn</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th>Bỏ chọn món</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $subtotal = 0;
                                while ($result = $get_cart->fetch_assoc()) {
                                ?>
                                    <tr class="text-center">
                                        <td class="image-prod">
                                            <img width="100" height="50" src="images/food/<?php echo htmlspecialchars($result['images']) ?>">
                                        </td>
                                        <td class="product-name">
                                            <h3><?php echo htmlspecialchars($result['name_mon']) ?></h3>
                                        </td>
                                        <td class="price"><?php echo $fm->formatMoney($result['gia_mon']) . " VNĐ" ?></td>
                                        <td class="quantity">
                                            <div class="input-group mb-3">
                                                <form action="" method="post">
                                                    <input type="hidden" name="cartid" value="<?php echo $result['cart_id'] ?>">
                                                    <input type="number" name="soluong" class="form-control" value="<?php echo $result['soluong'] ?>" min="1" max="50">
                                                    <input type="submit" value="Cập nhật" class="btn btn-primary py-3 px-5 mt-2">
                                                </form>
                                            </div>
                                        </td>
                                        <td class="total">
                                            <?php
                                            $total = $result['soluong'] * $result['gia_mon'];
                                            echo $fm->formatMoney($total) . " VNĐ";
                                            $subtotal += $total;
                                            ?>
                                        </td>
                                        <td class="product-remove">
                                            <form method="get" onsubmit="return confirm('Bạn có chắc muốn xóa món này khỏi giỏ hàng?')">
                                                <input type="hidden" name="delid" value="<?php echo $result['cart_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Bỏ món</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    <?php
                    } else {
                        echo "<p class='text-center'>Giỏ hàng của bạn đang trống. Vui lòng thêm món ăn để tiếp tục.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php if ($get_cart && $get_cart->num_rows > 0): ?>
            <div class="row justify-content-end">
                <div class="col col-lg-3 col-md-6 mt-5 cart-wrap ftco-animate">
                    <div class="cart-total mb-3">
                        <h3>Tổng tiền</h3>
                        <p class="d-flex">
                            <span>Tạm tính:</span>
                            <span><?php echo $fm->formatMoney($subtotal) . " VNĐ"; ?></span>
                        </p>
                        <p class="d-flex">
                            <span>Giảm giá:</span>
                            <span>0 VNĐ</span>
                        </p>
                        <hr>
                        <p class="d-flex total-price">
                            <span>Tổng cộng:</span>
                            <span><?php echo $fm->formatMoney($subtotal) . " VNĐ"; ?></span>
                        </p>
                    </div>
                    <p class="text-center">
                        <a href="datban.php?idorder=order" class="btn btn-primary py-3 px-4">Tiến hành đặt hàng</a>
                    </p>
                    <!-- Phần mã QR hiển thị tự động -->
                    <div class="text-center mt-4">
                        <h5>Hoặc quét mã QR để thanh toán:</h5>
                        <?php
                        date_default_timezone_set('Asia/Ho_Chi_Minh');
                        $account = "0941518881";
                        $bankCode = "BIDV";
                        $accountName = "TRUONG VAN HIEU";
                        $amount = $subtotal;
                        $note = urlencode("HD" . ($_SESSION['current_order_id'] ?? 'ORDER_' . time()));
                        $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$account}-compact2.png?amount={$amount}&addInfo={$note}&accountName=" . urlencode($accountName);
                        ?>
                        <img src="<?php echo $qrUrl; ?>" alt="QR Thanh toán BIDV" style="max-width:300px; margin-top:10px">
                        <p class="mt-3"><strong>Nội dung:</strong> HD<?php echo $_SESSION['current_order_id'] ?? 'ORDER_' . time(); ?></p>
                        <p>Vui lòng chuyển đúng số tiền và ghi đúng nội dung. Liên hệ admin để xác nhận thanh toán.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'inc/footer.php'; ?>