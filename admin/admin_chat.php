<?php
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../lib/database.php';
require_once '../helpers/format.php';

// Khởi tạo session nếu cần
if (session_status() == PHP_SESSION_NONE) session_start();

ob_start();
$db = new Database();
$fm = new Format();

// Kiểm tra đăng nhập
if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

// Kiểm tra quyền hạn (chỉ cho admin truy cập)
$level = $_SESSION['adminlevel'] ?? 1;
if ($level != 0) {
    echo "<script>
        alert('Bạn không phải quản trị viên, vui lòng đăng nhập bằng tài khoản admin!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$id_admin = $_SESSION['idadmin'] ?? 1;

$users = $db->select("SELECT kh.id, kh.ten, COUNT(m.id_message) as unread_count 
    FROM khach_hang kh 
    LEFT JOIN messages m ON kh.id = m.id_user 
    AND m.sender_type = 'user' 
    AND m.read_status = 0 
    AND m.id_admin = '$id_admin' 
    WHERE kh.xoa = 0 
    GROUP BY kh.id, kh.ten");


$chatHistory = [];
$selectedUser = isset($_GET['user_id']) ? $fm->validation($_GET['user_id']) : '';
$selectedUserName = '';

if ($selectedUser) {
    $db->update("UPDATE messages SET read_status = 1 WHERE id_user = '$selectedUser' AND id_admin = '$id_admin' AND sender_type = 'user'");
    $query = "SELECT * FROM messages WHERE (id_user = '$selectedUser' AND id_admin = '$id_admin') OR (id_user = '$id_admin' AND id_admin = '$selectedUser') ORDER BY timestamp ASC";
    $messages = $db->select($query);
    if ($messages) {
        while ($msg = $messages->fetch_assoc()) {
            $chatHistory[] = $msg;
        }
    }
    $userResult = $db->select("SELECT ten FROM khach_hang WHERE id = '$selectedUser'");
    if ($userResult) {
        $userData = $userResult->fetch_assoc();
        $selectedUserName = $userData['ten'];
    }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .chat-bubble {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 20px;
            margin-bottom: 10px;
            position: relative;
            font-size: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .chat-bubble.admin {
            background-color: #2563eb;
            color: #fff;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .chat-bubble.user {
            background-color: #f1f5f9;
            color: #111827;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }

        .chat-bubble span {
            display: block;
            font-size: 12px;
            margin-top: 6px;
            color: #94a3b8;
        }

        .list-group-item:hover {
            background-color: #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .list-group-item.active {
            background-color: #2563eb;
            color: #fff;
            font-weight: bold;
        }

        .list-group-item.active a {
            color: #fff !important;
        }

        #messageInput {
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        #messageInput:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        #chatBox::-webkit-scrollbar {
            width: 6px;
        }

        #chatBox::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }

        #chatBox::-webkit-scrollbar-track {
            background-color: transparent;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto p-4">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b bg-white">
            <h2 class="text-2xl font-bold text-gray-800">Chat với khách hàng</h2>
        </div>
        <div class="flex" style="min-height: 550px;">
            <!-- Danh sách người dùng -->
            <div class="w-1/3 border-r bg-gray-50 overflow-y-auto" style="max-height: 550px;">
                <h4 class="p-4 text-lg font-semibold text-gray-700">Danh sách khách hàng</h4>
                <ul class="divide-y divide-gray-200">
                    <?php while ($user = $users->fetch_assoc()) { ?>
                        <li class="list-group-item p-4 <?php echo $user['id'] == $selectedUser ? 'active' : ''; ?>">
                            <a href="admin_chat.php?user_id=<?= $user['id']; ?>" class="block">
                                <div class="flex justify-between items-center">
                                    <span><?= htmlspecialchars($user['ten']); ?></span>
                                    <?php if ($user['unread_count'] > 0) { ?>
                                        <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                            <?= $user['unread_count']; ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <!-- Khu vực chat -->
            <div class="w-2/3 flex flex-col bg-gray-50">
                <?php if ($selectedUser) { ?>
                    <div class="p-4 border-b bg-white">
                        <h4 class="text-lg font-semibold text-gray-700">Chat với <?= htmlspecialchars($selectedUserName); ?></h4>
                    </div>
                    <div id="chatBox" class="flex-1 p-4 overflow-y-auto" style="height: 400px;">
                        <?php
                        if (empty($chatHistory)) {
                            echo '<p class="text-gray-500">Chưa có tin nhắn nào với khách hàng này.</p>';
                        } else {
                            foreach ($chatHistory as $msg) {
                                $senderClass = $msg['sender_type'] === 'admin' ? 'admin' : 'user';
                                echo "<div class='chat-bubble $senderClass'><b>" .
                                    ($msg['sender_type'] === 'admin' ? 'Admin' : 'Khách hàng') .
                                    ":</b> {$msg['message_content']} <span>{$msg['timestamp']}</span></div>";
                            }
                        }
                        ?>
                    </div>
                    <div class="p-4 bg-white border-t">
                        <div class="flex gap-2">
                            <input id="messageInput" type="text" placeholder="Nhập tin nhắn..." class="flex-1">
                            <button onclick="sendMessage()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Gửi</button>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="flex-1 flex items-center justify-center">
                        <p class="text-gray-500 text-lg">Vui lòng chọn một khách hàng để bắt đầu.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
    const socket = new WebSocket('ws://localhost:8080');
    const id_admin = <?php echo $id_admin; ?>;
    const selectedUser = '<?php echo $selectedUser; ?>';
    const chatBox = document.getElementById('chatBox');

    socket.onopen = () => console.log('Đã kết nối WebSocket');

    socket.onmessage = event => {
        const data = JSON.parse(event.data);
        if (data.id_user == selectedUser && data.id_admin == id_admin) {
            const senderClass = data.sender_type === 'admin' ? 'admin' : 'user';
            chatBox.innerHTML += `<div class='chat-bubble ${senderClass}'><b>${data.sender_type === 'admin' ? 'Admin' : 'Khách hàng'}:</b> ${data.message} <span>${data.timestamp}</span></div>`;
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        if (data.sender_type === 'user' && data.id_admin == id_admin) {
            const userItem = document.querySelector(`a[href='admin_chat.php?user_id=${data.id_user}']`);
            if (userItem) {
                const badge = userItem.querySelector('.bg-red-600');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else if (data.id_user != selectedUser) {
                    userItem.innerHTML += `<span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">1</span>`;
                }
            }
        }
    };

    function sendMessage() {
        const message = document.getElementById('messageInput').value;
        if (message.trim() === '') return;
        socket.send(JSON.stringify({
            id_user: selectedUser,
            id_admin: id_admin,
            message: message,
            sender_type: 'admin'
        }));
        document.getElementById('messageInput').value = '';
    }

    window.onload = () => {
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    };
</script>

<?php include 'inc/footer.php'; ?>
</body>
</html>
