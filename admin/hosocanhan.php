<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include 'inc/header.php';
include 'inc/sidebar.php';
include '../classes/admin.php';
include_once '../helpers/format.php';

$admin = new Admin();
$fm = new Format();

$id_admin = $_SESSION['id_admin'];
$userInfo = $admin->getUserInfo($id_admin);
?>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Hồ Sơ Cá Nhân</h2>
        <div class="block">
            <?php if ($userInfo): ?>
                <table class="form">
                    <tr>
                        <td>ID Admin:</td>
                        <td><?php echo $userInfo['id_admin']; ?></td>
                    </tr>
                    <tr>
                        <td>Tên:</td>
                        <td><?php echo $userInfo['Name_admin']; ?></td>
                    </tr>
                    <tr>
                        <td>Username:</td>
                        <td><?php echo $userInfo['adminuser']; ?></td>
                    </tr>
                    <tr>
                        <td>Level:</td>
                        <td><?php echo $userInfo['level'] == 1 ? 'Admin' : 'User'; ?></td>
                    </tr>
                </table>
            <?php else: ?>
                <p>User not found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
