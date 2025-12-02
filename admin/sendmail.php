<?php
ob_start();
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../classes/cart.php';
require_once '../vendor/autoload.php';

// Kiểm tra đăng nhập và phân quyền admin
if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$fm = new Format();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_ids = $_POST['user_ids'] ?? [];
    $subject = $fm->validation($_POST['subject'] ?? '');
    $message = $fm->validation($_POST['message'] ?? '');
    $send_all = isset($_POST['send_all']) && $_POST['send_all'] === '1';

    if (empty($subject) || empty($message)) {
        $error = "Vui lòng nhập tiêu đề và nội dung mail.";
    } elseif (!$send_all && empty($user_ids)) {
        $error = "Vui lòng chọn ít nhất một khách hàng hoặc chọn gửi tất cả.";
    } else {
        $emails = [];
        if ($send_all) {
            $result = $db->select("SELECT email FROM khach_hang WHERE email IS NOT NULL");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $emails[] = $row['email'];
                }
            }
        } else {
            $user_ids = array_map('intval', $user_ids);
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            $stmt = $db->link->prepare("SELECT email FROM khach_hang WHERE id IN ($placeholders) AND email IS NOT NULL");
            $stmt->bind_param(str_repeat('i', count($user_ids)), ...$user_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
            $stmt->close();
        }

        if (empty($emails)) {
            $error = "Không tìm thấy email hợp lệ để gửi.";
        } else {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tknhahangtriskiet@gmail.com';
                $mail->Password = 'gdbg tupm xquh ytms';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('tknhahangtriskiet@gmail.com', 'Nhà Hàng TRisKiet');
                $mail->CharSet = 'UTF-8';

                foreach ($emails as $email) {
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = nl2br(htmlspecialchars($message));
                    $mail->send();
                    $mail->clearAddresses();
                }

                $success = "Mail đã được gửi thành công!";
            } catch (Exception $e) {
                $error = "Lỗi khi gửi Mail: {$mail->ErrorInfo}";
            }
        }
    }
}

if ($success) {
    $recipients = implode(',', $emails);
    $sent_at = date('Y-m-d H:i:s');
    $admin_id = $_SESSION['idadmin'];
    $stmt = $db->link->prepare("INSERT INTO promotion_emails (subject, message, recipients, sent_at, admin_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $subject, $message, $recipients, $sent_at, $admin_id);
    $stmt->execute();
    $stmt->close();
}

$users = $db->select("SELECT id, ten, email FROM khach_hang WHERE xoa = 0");
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 30px auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        font-size: 16px;
    }
    .form-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: #444;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
    }
    .form-control:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 4px rgba(0, 123, 255, 0.2);
    }
    .checkbox-container {
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 12px;
        max-height: 250px;
        overflow-y: auto;
        background-color: #f9f9f9;
    }
    .checkbox-container .form-check {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .text-success {
        color: #28a745;
        font-weight: bold;
        margin-bottom: 15px;
    }
    .text-danger {
        color: #dc3545;
        font-weight: bold;
        margin-bottom: 15px;
    }
    .btn-submit {
        background-color: #007bff;
        color: #fff;
        padding: 12px 28px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: block;
        margin-top: 10px;
    }
    .btn-submit:hover {
        background-color: #0056b3;
    }
</style>

<div class="grid_10">
    <div class="form-container">
        <h2>Gửi Mail</h2>

        <?php if ($success) echo "<p class='text-success'>" . htmlspecialchars($success) . "</p>"; ?>
        <?php if ($error) echo "<p class='text-danger'>" . htmlspecialchars($error) . "</p>"; ?>

        <form method="post">
            <div class="form-group">
                <label>Danh sách khách hàng</label>
                <div class="checkbox-container">

                    <div class="form-check">
                        <label for="send_all" style="flex: 1;">Gửi đến tất cả khách hàng</label>
                        <input type="checkbox" name="send_all" id="send_all" value="1" onchange="toggleUserSelection()">
                    </div>

                    <?php while ($user = $users->fetch_assoc()) { ?>
                        <div class="form-check">
                            <label for="user_<?= $user['id']; ?>" style="flex: 1;">
                                <?= htmlspecialchars($user['ten']) . ' (' . htmlspecialchars($user['email']) . ')' ?>
                            </label>
                            <input type="checkbox" name="user_ids[]" value="<?= $user['id']; ?>" class="user-checkbox" id="user_<?= $user['id']; ?>">
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="form-group">
                <label>Tiêu đề Mail</label>
                <input type="text" name="subject" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Nội dung Mail</label>
                <textarea name="message" class="form-control" rows="6" required></textarea>
            </div>

            <div class="form-group">
                <input type="submit" value="Gửi Mail" class="btn-submit">
            </div>
        </form>
    </div>
</div>

<script>
    function toggleUserSelection() {
        const sendAll = document.getElementById('send_all');
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(cb => {
            cb.disabled = sendAll.checked;
            if (sendAll.checked) cb.checked = false;
        });
    }
</script>

<?php include 'inc/footer.php'; ?>
<?php ob_end_flush(); ?>
