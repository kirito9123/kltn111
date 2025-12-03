<?php
include 'inc/header.php';
include 'inc/sidebar.php';
require_once '../lib/database.php';
require_once '../helpers/format.php';

if (session_status() == PHP_SESSION_NONE) session_start();

ob_start();
$db = new Database();
$fm = new Format();

if (!isset($_SESSION['adminlogin']) || $_SESSION['adminlogin'] !== true) {
    header('Location: login.php');
    exit();
}

$level = $_SESSION['adminlevel'] ?? 1;
if ($level != 0 && $level != 2) {
    echo "<script>
        alert('Bạn không có quyền truy cập trang này!');
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
    WHERE kh.xoa = 0 
    GROUP BY kh.id, kh.ten");

$selectedUser = isset($_GET['user_id']) ? $fm->validation($_GET['user_id']) : '';
$selectedUserName = '';

if ($selectedUser) {
    $db->update("UPDATE messages SET read_status = 1 WHERE id_user = '$selectedUser' AND sender_type = 'user'");
    $query = "SELECT * FROM messages WHERE id_user = '$selectedUser' ORDER BY timestamp ASC";
    $messages = $db->select($query);
    $chatHistory = [];
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .chat-bubble img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 5px;
            display: block;
        }

        .chat-bubble span {
            display: block;
            font-size: 11px;
            margin-top: 6px;
            opacity: 0.8;
            text-align: right;
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
            padding: 12px 16px;
            border-radius: 15px;
            border: 1px solid #cbd5e1;
            resize: vertical;
            min-height: 48px;
            max-height: 150px;
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
            <div class="flex" style="min-height: 600px;">
                <!-- Danh sách người dùng -->
                <div class="w-1/3 border-r bg-gray-50 overflow-y-auto" style="max-height: 600px;">
                    <h4 class="p-4 text-lg font-semibold text-gray-700">Danh sách khách hàng</h4>
                    <ul class="divide-y divide-gray-200">
                        <?php
                        if ($users) {
                            while ($user = $users->fetch_assoc()) {
                        ?>
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
                        <?php
                            }
                        }
                        ?>
                    </ul>
                </div>

                <!-- Khu vực chat -->
                <div class="w-2/3 flex flex-col bg-gray-50">
                    <?php if ($selectedUser) { ?>
                        <div class="p-4 border-b bg-white flex justify-between items-center">
                            <h4 class="text-lg font-semibold text-gray-700">Chat với <?= htmlspecialchars($selectedUserName); ?></h4>
                        </div>
                        <div id="chatBox" class="flex-1 p-4 overflow-y-auto" style="height: 450px;">
                            <?php
                            if (empty($chatHistory)) {
                                echo '<p class="text-gray-500 text-center mt-4">Chưa có tin nhắn nào với khách hàng này.</p>';
                            } else {
                                foreach ($chatHistory as $msg) {
                                    $senderClass = $msg['sender_type'] === 'admin' ? 'admin' : 'user';
                                    $senderName = $msg['sender_type'] === 'admin' ? 'Admin' : 'Khách hàng';
                                    $statusHtml = ($msg['sender_type'] === 'admin' && $msg['read_status'] == 1) ? ' • Đã xem' : '';

                                    $contentHtml = '';
                                    if ($msg['image']) {
                                        $contentHtml .= "<img src='../images/chat/{$msg['image']}' alt='Image'>";
                                    }
                                    if ($msg['message_content']) {
                                        $contentHtml .= "<div>{$msg['message_content']}</div>";
                                    }

                                    echo "<div class='chat-bubble $senderClass'><b>$senderName:</b> $contentHtml <span>{$msg['timestamp']}$statusHtml</span></div>";
                                }
                            }
                            ?>
                        </div>
                        <div class="p-4 bg-white border-t">
                            <div class="flex gap-2 items-end">
                                <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageSelect(this)">
                                <button onclick="document.getElementById('imageInput').click()" class="px-4 py-3 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300 transition h-full" title="Gửi ảnh">
                                    <i class="fas fa-image text-xl"></i>
                                </button>
                                <textarea id="messageInput" rows="1" placeholder="Nhập tin nhắn..." class="flex-1"></textarea>
                                <button onclick="sendMessage()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold h-full">Gửi</button>
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
        const id_admin = <?php echo $id_admin; ?>;
        const selectedUser = '<?php echo $selectedUser; ?>';
        const chatBox = document.getElementById('chatBox');
        let isScrolledToBottom = true;

        const tx = document.getElementsByTagName("textarea");
        for (let i = 0; i < tx.length; i++) {
            tx[i].setAttribute("style", "height:" + (tx[i].scrollHeight) + "px;overflow-y:hidden;");
            tx[i].addEventListener("input", OnInput, false);
        }

        function OnInput() {
            this.style.height = 0;
            this.style.height = (this.scrollHeight) + "px";
        }

        function scrollToBottom() {
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        }

        if (chatBox) {
            chatBox.addEventListener('scroll', () => {
                const threshold = 50;
                const position = chatBox.scrollTop + chatBox.offsetHeight;
                const height = chatBox.scrollHeight;
                isScrolledToBottom = position > height - threshold;
            });
        }

        function handleImageSelect(input) {
            if (input.files && input.files[0]) {
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const imageInput = document.getElementById('imageInput');
            const message = input.value.trim();
            const image = imageInput.files[0];

            if (message === '' && !image) return;

            const formData = new FormData();
            formData.append('user_id', selectedUser);
            formData.append('message', message);
            if (image) {
                formData.append('image', image);
            }

            fetch('chat_api.php?action=send_message', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        input.value = '';
                        input.style.height = 'auto';
                        imageInput.value = '';
                        fetchMessages();
                    } else {
                        alert('Lỗi gửi tin nhắn: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function fetchMessages() {
            if (!selectedUser) return;

            fetch(`chat_api.php?action=get_messages&user_id=${selectedUser}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        let html = '';
                        if (data.messages.length === 0) {
                            html = '<p class="text-gray-500 text-center mt-4">Chưa có tin nhắn nào với khách hàng này.</p>';
                        } else {
                            data.messages.forEach(msg => {
                                const senderClass = msg.sender_type === 'admin' ? 'admin' : 'user';
                                const senderName = msg.sender_type === 'admin' ? 'Admin' : 'Khách hàng';

                                let statusHtml = '';
                                if (msg.sender_type === 'admin' && msg.read_status == 1) {
                                    statusHtml = ' • Đã xem';
                                }

                                let contentHtml = '';
                                if (msg.image) {
                                    contentHtml += `<img src="../images/chat/${msg.image}" alt="Image">`;
                                }
                                if (msg.message_content) {
                                    contentHtml += `<div>${msg.message_content}</div>`;
                                }

                                html += `<div class='chat-bubble ${senderClass}'><b>${senderName}:</b> ${contentHtml} <span>${msg.timestamp}${statusHtml}</span></div>`;
                            });
                        }

                        chatBox.innerHTML = html;

                        if (isScrolledToBottom) {
                            scrollToBottom();
                        }
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function fetchUsers() {
            fetch('chat_api.php?action=get_users')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const userListContainer = document.querySelector('.divide-y');
                        if (!userListContainer) return;

                        const currentSelected = '<?php echo $selectedUser; ?>';
                        let html = '';
                        data.users.forEach(user => {
                            const activeClass = user.id == currentSelected ? 'active' : '';
                            const badge = user.unread_count > 0 ?
                                `<span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">${user.unread_count}</span>` :
                                '';

                            html += `
                                <li class="list-group-item p-4 ${activeClass}">
                                    <a href="admin_chat.php?user_id=${user.id}" class="block">
                                        <div class="flex justify-between items-center">
                                            <span>${user.ten}</span>
                                            ${badge}
                                        </div>
                                    </a>
                                </li>
                            `;
                        });
                        userListContainer.innerHTML = html;
                    }
                })
                .catch(error => console.error('Error fetching users:', error));
        }

        if (selectedUser) {
            setInterval(fetchMessages, 1000);
        }

        setInterval(fetchUsers, 5000);

        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        window.onload = () => {
            scrollToBottom();
        };
    </script>

    <?php include 'inc/footer.php'; ?>
</body>

</html>