<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include '../classes/loaimon.php'; ?>

<?php
if (!Session::get('adminlogin')) {
    header("Location: login.php");
    exit();
}
?>

<style>
    .form-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #343a40;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        font-weight: bold;
        display: block;
        margin-bottom: 8px;
        color: #495057;
    }

    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 16px;
    }

    .form-actions {
        text-align: center;
        margin-top: 25px;
    }

    .form-actions input[type="submit"] {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-actions input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>

<?php
    $loai = new loaimon();
    if (!isset($_GET['id_loai']) || $_GET['id_loai'] == NULL) {
        echo "<script>window.location = 'catlist.php'</script>";
    } else {
        $id = $_GET['id_loai'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tenloai = $_POST['tenloai'];
        $ghichu = $_POST['ghichu'];

        $updatetloai = $loai->update_loai($tenloai, $ghichu, $id);
    }
?>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>Sửa Loại Món</h2>

            <?php
            if (isset($updatetloai)) {
                echo "<p style='color: green; text-align: center; font-weight: bold;'>$updatetloai</p>";
            }

            $get_ten_loai = $loai->getloaibyid($id);
            if ($get_ten_loai) {
                while ($result = $get_ten_loai->fetch_assoc()) {
            ?>

                    <form action="" method="post">
                        <div class="form-group">
                            <label for="tenloai">Tên loại món</label>
                            <input type="text" name="tenloai" id="tenloai" value="<?php echo $result['name_loai'] ?>" placeholder="Nhập tên loại món" required />
                        </div>

                        <div class="form-group">
                            <label for="ghichu">Ghi chú</label>
                            <input type="text" name="ghichu" id="ghichu" placeholder="Nhập ghi chú (nếu có)" />
                        </div>

                        <div class="form-actions">
                            <input type="submit" name="submit" value="Sửa" />
                        </div>
                    </form>

            <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
