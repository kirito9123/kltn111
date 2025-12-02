<?php include 'inc/header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cartid']) && isset($_POST['soluong'])) {
        foreach ($_POST['cartid'] as $index => $id_cart) {
            $soluong = $_POST['soluong'][$index];
            $ct->update_cart($soluong, $id_cart);
        }
    }
    header('location:cartt.php');
}
if (isset($_GET['delid'])) {
    $id = $_GET['delid'];
    $delcart = $ct->del_loai($id);
    header('location:cartt.php');
}
?>

<style>
/* Table giỏ hàng */
.table.cart-table {
    border-collapse: collapse;
    width: 100%;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.cart-table thead {
    background-color: #28a745;
    color: white;
    font-weight: bold;
}
.cart-table th, .cart-table td {
    text-align: center;
    padding: 15px 10px;
    vertical-align: middle;
}
.cart-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}
.cart-table img {
    border-radius: 8px;
    width: 80px;
    height: auto;
    object-fit: cover;
}
/* Số lượng */
.cart-table input[type="number"] {
    width: 70px;
    padding: 5px;
    text-align: center;
    border-radius: 6px;
    border: 1px solid #ccc;
}
/* Nút xóa */
.btn-danger.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 6px;
}
/* Tổng tiền */
.cart-wrap {
    background-color: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.cart-total h3 {
    font-size: 20px;
    margin-bottom: 15px;
}
.cart-total p {
    margin: 5px 0;
    display: flex;
    justify-content: space-between;
    font-size: 16px;
}
.cart-total hr {
    margin: 15px 0;
}
.cart-total .total-price {
    font-weight: bold;
    font-size: 18px;
}
.cart-total .btn-primary {
    width: 100%;
    font-size: 16px;
    border-radius: 8px;
}
/* Cập nhật giỏ hàng và Quay lại menu */
.cart-action-btns {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 15px;
}
.cart-action-btns.left-btns {
    justify-content: flex-start;
}
.update-cart-btn input[type="submit"], .back-menu-btn a {
    background-color: #007bff;
    border: none;
    padding: 10px 22px;
    color: white;
    font-size: 15px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.3s;
    font-weight: 500;
}
.update-cart-btn input[type="submit"]:hover, .back-menu-btn a:hover {
    background-color: #0056b3;
}
a.btn.btn-primary {
    border-radius: 10px; /* Bo tròn như viên thuốc */
    font-weight: bold;
    font-size: 16px;
    background-color: #28a745;
    border: none;
    padding: 12px 24px;
    transition: background-color 0.3s ease;
}
a.btn.btn-primary:hover {
    background-color: #218838;
    text-decoration: none;
}
</style>

<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">Giỏ hàng</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thanh toán <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section ftco-cart">
    <div class="container">
        <div class="row">
            <div class="col-md-12 ftco-animate">
                <div class="cart-list">
                    <form action="" method="post">
                        <table class="table cart-table">
                            <thead>
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Món ăn</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th>Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $get_cart = $ct->get_cart();
                                if ($get_cart) {
                                    $subtotal = 0;
                                    while ($result = $get_cart->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td><img src="images/food/<?php echo $result['images'] ?>"></td>
                                        <td><?php echo $result['name_mon'] ?></td>
                                        <td><?php echo $fm->formatMoney($result['gia_mon']) ?> VNĐ</td>
                                        <td>
                                            <input type="hidden" name="cartid[]" value="<?php echo $result['cart_id'] ?>">
                                            <input type="number" name="soluong[]" value="<?php echo $result['soluong'] ?>" min="1" max="50">
                                        </td>
                                        <td>
                                            <?php
                                            $total = $result['soluong'] * $result['gia_mon'];
                                            echo $fm->formatMoney($total) . " VNĐ";
                                            $subtotal += $total;
                                            ?>
                                        </td>
                                        <td>
                                            <a href="cartt.php?delid=<?php echo $result['cart_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa món này khỏi giỏ hàng?')">Xóa</a>
                                        </td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                        <!-- Nút bên trái -->
                        <div class="cart-action-btns left-btns">
                            <div class="back-menu-btn">
                                <a href="menu.php">Quay lại menu</a>
                            </div>
                            <div class="update-cart-btn">
                                <input type="submit" value="Cập nhật giỏ hàng">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row justify-content-end">
            <div class="col col-lg-3 col-md-6 mt-5 cart-wrap ftco-animate">
                <div class="cart-total mb-3">
                    <h3>Tổng tiền</h3>
                    <p><span>Tạm tính:</span> <span><?php echo isset($subtotal) ? $fm->formatMoney($subtotal) : "0"; ?> VNĐ</span></p>
                    <p><span>Giảm giá:</span> <span>0 VNĐ</span></p>
                    <hr>
                    <p class="total-price"><span>Tổng cộng:</span> <span><?php echo isset($subtotal) ? $fm->formatMoney($subtotal) : "0"; ?> VNĐ</span></p>
                </div>
                <p class="text-center">
                    <a href="datban.php?idorder=order" class="btn btn-primary py-3 px-4">Tiến hành đặt hàng</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>
