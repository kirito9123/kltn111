<?php
include 'inc/header.php';

if (!isset($_GET['id']) || $_GET['id'] == NULL) {
    echo "<script>window.location = '404.php'</script>";
    exit();
} else {
    $id = $_GET['id'];
}

$mess = "";
$pass0 = $pass1 = $repass = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass0 = $_POST['pass0'];
    $pass1 = $_POST['pass1'];
    $repass = $_POST['repass'];
    $result = $us->change_pass($id, md5($pass0), md5($pass1), md5($repass));

    if (strpos($result, 'Thành Công') !== false) {
        echo "<script>alert('Đổi mật khẩu thành công!'); window.location.href='userblog.php?id=$id';</script>";
        exit();
    } else {
        $mess = $result;
    }
}
?>

<!-- Giao diện -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 text-center mb-4">
                <h1 class="mb-2 bread">Thay đổi mật khẩu</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Đổi mật khẩu <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 p-4 shadow rounded bg-white">
                <h4 class="mb-4 font-weight-bold">Đổi mật khẩu</h4>
                <?php if ($mess) echo "<div class='alert alert-danger'>$mess</div>"; ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label>Mật khẩu cũ</label>
                        <input type="password" name="pass0" class="form-control" value="<?= htmlspecialchars($pass0) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="pass1" class="form-control" value="<?= htmlspecialchars($pass1) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nhập lại mật khẩu mới</label>
                        <input type="password" name="repass" class="form-control" value="<?= htmlspecialchars($repass) ?>" required>
                    </div>
                    <div class="form-group mt-4">
                        <input type="submit" value="Xác nhận đổi mật khẩu" class="btn btn-primary w-100">
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- CSS nhẹ -->
<style>
    label {
        font-weight: bold;
        color: #333;
    }
    .btn-primary {
        background-color: #d19c65;
        border: none;
        border-radius: 25px;
    }
    .btn-primary:hover {
        background-color: #b87e4b;
    }
</style>

<?php include 'inc/footer.php'; ?>
