<?php
include 'lib/database.php';
include 'helpers/format.php';

if (session_status() == PHP_SESSION_NONE) session_start();

$db = new Database();
$fm = new Format();

if (!isset($_SESSION['userlogin']) || $_SESSION['userlogin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id_user = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
$id_admin_default = 0;

$action = isset($_GET['action']) ? $_GET['action'] : '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_messages':
        $db->update("UPDATE messages SET read_status = 1 WHERE id_user = '$id_user' AND sender_type = 'admin'");
        $query = "SELECT * FROM messages WHERE id_user = '$id_user' ORDER BY timestamp ASC";
        $result = $db->select($query);

        $messages = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    case 'send_message':
        // Xử lý upload ảnh
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = $_FILES['image']['type'];
            $filesize = $_FILES['image']['size'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                // Tạo tên file mới để tránh trùng
                $imageName = uniqid() . '.' . $ext;
                $uploadPath = 'images/chat/' . $imageName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']);
                exit();
            }
        }

        // Nhận message từ POST (vì dùng FormData nên không dùng php://input json)
        $message = isset($_POST['message']) ? mysqli_real_escape_string($db->link, $_POST['message']) : '';

        if ($message || $imageName) {
            $imgCol = $imageName ? "'$imageName'" : "NULL";
            $query = "INSERT INTO messages (id_user, id_admin, message_content, image, sender_type, read_status) 
                      VALUES ('$id_user', '$id_admin_default', '$message', $imgCol, 'user', 0)";
            $insert_row = $db->insert($query);

            if ($insert_row) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No message or image provided']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
