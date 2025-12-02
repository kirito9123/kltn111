<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/session.php');
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

Session::init();
$db = new Database();
$fm = new Format();

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper to get default admin if not provided
function getDefaultAdminId($db)
{
    $query = "SELECT id_admin FROM tb_admin LIMIT 1";
    $result = $db->select($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['id_admin'];
    }
    return 1; // Fallback
}

if ($action == 'send_message') {
    $data = json_decode(file_get_contents("php://input"), true);

    $sender_type = $data['sender_type'];

    // Security check
    if ($sender_type == 'user') {
        if (!Session::get('userlogin')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $id_user = Session::get('id'); // Trust session over input
        $id_admin = isset($data['id_admin']) && $data['id_admin'] ? $data['id_admin'] : getDefaultAdminId($db);
    } elseif ($sender_type == 'admin') {
        if (!Session::get('adminlogin')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $id_admin = Session::get('idadmin'); // Trust session
        $id_user = $data['id_user'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid sender type']);
        exit;
    }

    $message = mysqli_real_escape_string($db->link, $data['message']);
    $read_status = ($sender_type == 'admin') ? 1 : 0;

    $query = "INSERT INTO messages (id_user, id_admin, message_content, sender_type, read_status, timestamp) 
              VALUES ('$id_user', '$id_admin', '$message', '$sender_type', '$read_status', NOW())";
    $result = $db->insert($query);

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    }
} elseif ($action == 'get_messages') {
    $requester_type = isset($_GET['requester_type']) ? $_GET['requester_type'] : '';

    if ($requester_type == 'user') {
        if (!Session::get('userlogin')) {
            echo json_encode([]);
            exit;
        }
        $id_user = Session::get('id');
        $id_admin = isset($_GET['id_admin']) && $_GET['id_admin'] ? $_GET['id_admin'] : getDefaultAdminId($db);
    } elseif ($requester_type == 'admin') {
        if (!Session::get('adminlogin')) {
            echo json_encode([]);
            exit;
        }
        $id_admin = Session::get('idadmin');
        $id_user = isset($_GET['id_user']) ? $_GET['id_user'] : 0;

        // Mark as read
        $db->update("UPDATE messages SET read_status = 1 WHERE id_user = '$id_user' AND sender_type = 'user'");
    } else {
        echo json_encode([]);
        exit;
    }

    if ($requester_type == 'user') {
        $query = "SELECT * FROM messages WHERE id_user = '$id_user' ORDER BY timestamp ASC";
    } else {
        // Admin sees all messages for this user, regardless of which admin replied
        $query = "SELECT * FROM messages WHERE id_user = '$id_user' ORDER BY timestamp ASC";
    }
    $messages = $db->select($query);

    $chatHistory = [];
    if ($messages) {
        while ($msg = $messages->fetch_assoc()) {
            $chatHistory[] = $msg;
        }
    }
    echo json_encode($chatHistory);
} elseif ($action == 'get_unread') {
    if (!Session::get('adminlogin')) {
        echo json_encode([]);
        exit;
    }
    $id_admin = Session::get('idadmin');

    $query = "SELECT id_user, COUNT(*) as count FROM messages WHERE sender_type = 'user' AND read_status = 0 GROUP BY id_user";
    $result = $db->select($query);

    $unreadData = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $unreadData[$row['id_user']] = $row['count'];
        }
    }
    echo json_encode($unreadData);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
