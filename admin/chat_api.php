<?php
include '../lib/database.php';
include '../helpers/format.php';

if (session_status() == PHP_SESSION_NONE) session_start();

$db = new Database();
$fm = new Format();

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$level = $_SESSION['adminlevel'] ?? 1;
if ($level != 0 && $level != 2) {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit();
}

$id_admin = $_SESSION['idadmin'] ?? 1;

$action = isset($_GET['action']) ? $_GET['action'] : '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_messages':
        $user_id = isset($_GET['user_id']) ? $fm->validation($_GET['user_id']) : '';
        if ($user_id) {
            $db->update("UPDATE messages SET read_status = 1 WHERE id_user = '$user_id' AND sender_type = 'user'");
            $query = "SELECT * FROM messages WHERE id_user = '$user_id' ORDER BY timestamp ASC";
            $result = $db->select($query);

            $messages = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'messages' => $messages]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
        }
        break;

    case 'send_message':
        // Xử lý upload ảnh
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $imageName = uniqid() . '.' . $ext;
                // Lưu ý đường dẫn: admin/chat_api.php -> ../images/chat/
                $uploadPath = '../images/chat/' . $imageName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
                exit();
            }
        }

        $user_id = isset($_POST['user_id']) ? $fm->validation($_POST['user_id']) : '';
        $message = isset($_POST['message']) ? mysqli_real_escape_string($db->link, $_POST['message']) : '';

        if ($user_id && ($message || $imageName)) {
            $imgCol = $imageName ? "'$imageName'" : "NULL";
            $query = "INSERT INTO messages (id_user, id_admin, message_content, image, sender_type, read_status) 
                      VALUES ('$user_id', '$id_admin', '$message', $imgCol, 'admin', 0)";
            $insert_row = $db->insert($query);

            if ($insert_row) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
        break;

    case 'get_users':
        $query = "SELECT kh.id, kh.ten, COUNT(m.id_message) as unread_count 
                  FROM khach_hang kh 
                  LEFT JOIN messages m ON kh.id = m.id_user 
                  AND m.sender_type = 'user' 
                  AND m.read_status = 0 
                  WHERE kh.xoa = 0 
                  GROUP BY kh.id, kh.ten
                  ORDER BY unread_count DESC, kh.ten ASC";

        $result = $db->select($query);
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'users' => $users]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
