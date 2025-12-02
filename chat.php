<?php
include 'inc/header.php';
require_once 'lib/database.php';
require_once 'helpers/format.php';

ob_start();
$db = new Database();
$fm = new Format();

if (!isset($_SESSION['userlogin']) || $_SESSION['userlogin'] !== true) {
    header('Location: login.php');
    exit();
}

$id_user = isset($_SESSION['id']) ? $_SESSION['id'] : 15;

$query = "SELECT * FROM messages WHERE id_user = '$id_user' ORDER BY timestamp ASC";
$messages = $db->select($query);
$chatHistory = [];
if ($messages) {
    while ($msg = $messages->fetch_assoc()) {
        $chatHistory[] = $msg;
    }
}
?>

<!-- PHẦN BANNER GIỎ HÀNG -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">TRAO ĐỔI VỚI CHỦ QUÁN</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Thanh toán <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<!-- NÚT QUAY LẠI -->
<div style="text-align: center; margin-top: 30px; margin-right: 1000px;">
    <a href="index.php" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 10px;">← Quay lại Trang Chủ</a>
</div>

<!-- GIAO DIỆN CHAT -->
<style>
    #chatBox {
        height: 300px;
        overflow-y: auto;
        border-radius: 10px;
        border: 1px solid #ddd;
        padding: 15px;
        background-color: #f8f9fa;
        margin-bottom: 10px;
        font-family: 'Segoe UI', sans-serif;
        font-size: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .message {
        padding: 14px 20px;
        border-radius: 24px;
        max-width: 85%;
        word-wrap: break-word;
        position: relative;
        display: inline-block;
    }

    .user-message {
        background-color: #d1e7dd;
        color: #0f5132;
        align-self: flex-end;
        text-align: right;
    }

    .admin-message {
        background-color: #e2e3e5;
        color: #383d41;
        align-self: flex-start;
        text-align: left;
    }

    .message small {
        display: block;
        font-size: 11px;
        margin-top: 6px;
        color: #888;
    }

    #messageInput {
        width: 80%;
        padding: 12px;
        border-radius: 25px;
        border: 1px solid #ccc;
        outline: none;
        font-size: 15px;
    }

    #sendBtn {
        padding: 12px 24px;
        background-color: #0d6efd;
        color: white;
        border: none;
        border-radius: 25px;
        margin-left: 10px;
        cursor: pointer;
        font-size: 15px;
    }

    #sendBtn:hover {
        background-color: #0b5ed7;
    }

    .chat-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .container.chat-container {
        max-width: 800px;
        margin: 40px auto;
    }
</style>

<div class="container chat-container">
    <div class="box round first grid">
        <h2 class="text-center mb-4">Chat với Admin</h2>
        <div class="block">
            <div id="chatBox">
                <?php
                if (empty($chatHistory)) {
                    echo '<p>Chưa có tin nhắn nào.</p>';
                } else {
                    foreach ($chatHistory as $msg) {
                        $isUser = $msg['sender_type'] === 'user';
                        $class = $isUser ? 'user-message' : 'admin-message';
                        echo "<div class='message $class'>{$msg['message_content']}<small>{$msg['timestamp']}</small></div>";
                    }
                }
                ?>
            </div>
            <div class="chat-controls mt-3">
                <input id="messageInput" type="text" placeholder="Nhập tin nhắn">
                <button id="sendBtn" onclick="sendMessage()">Gửi</button>
            </div>
        </div>
    </div>
</div>

<script>
    const socket = new WebSocket('ws://localhost:8080');
    const id_user = <?php echo $id_user; ?>;
    const chatBox = document.getElementById('chatBox');

    socket.onopen = function () {
        console.log('Đã kết nối WebSocket');
    };

    socket.onmessage = function (event) {
        const data = JSON.parse(event.data);
        if (data.id_user == id_user) {
            const div = document.createElement('div');
            div.classList.add('message');
            div.classList.add(data.sender_type === 'user' ? 'user-message' : 'admin-message');
            div.innerHTML = `${data.message}<small>${data.timestamp}</small>`;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    };

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        if (message === '') return;

        socket.send(JSON.stringify({
            id_user: id_user,
            id_admin: 3,
            message: message,
            sender_type: 'user'
        }));

        input.value = '';
    }

    window.onload = function () {
        chatBox.scrollTop = chatBox.scrollHeight;
    };
</script>

<?php include 'inc/footer.php'; ?>
