<?php
include 'inc/header.php';
require_once 'lib/database.php';
require_once 'helpers/format.php';

ob_start();
$db = new Database();
$fm = new Format();

if (!isset($_SESSION['userlogin']) || $_SESSION['userlogin'] !== true) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$id_user = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
?>

<!-- PHẦN BANNER -->
<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg3.jpg');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
            <div class="col-md-9 ftco-animate text-center mb-4">
                <h1 class="mb-2 bread">TRAO ĐỔI VỚI CHỦ QUÁN</h1>
                <p class="breadcrumbs"><span class="mr-2"><a href="index.html">Trang chủ <i class="ion-ios-arrow-forward"></i></a></span> <span>Chat <i class="ion-ios-arrow-forward"></i></span></p>
            </div>
        </div>
    </div>
</section>

<!-- NÚT QUAY LẠI -->
<div class="container mt-4">
    <a href="index.php" class="btn btn-secondary" style="border-radius: 20px;"><i class="ion-ios-arrow-back"></i> Quay lại</a>
</div>

<!-- GIAO DIỆN CHAT -->
<style>
    .chat-container {
        max-width: 800px;
        margin: 20px auto 50px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .chat-header {
        background: #0d6efd;
        color: white;
        padding: 15px 20px;
        font-weight: bold;
        font-size: 1.2rem;
    }

    #chatBox {
        height: 400px;
        overflow-y: auto;
        padding: 20px;
        background-color: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .message {
        padding: 10px 15px;
        border-radius: 20px;
        max-width: 75%;
        word-wrap: break-word;
        position: relative;
        font-size: 15px;
        line-height: 1.5;
    }

    .user-message {
        background-color: #0d6efd;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 5px;
    }

    .admin-message {
        background-color: #e9ecef;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 5px;
    }

    .message img {
        max-width: 100%;
        border-radius: 10px;
        margin-top: 5px;
        display: block;
    }

    .message small {
        display: block;
        font-size: 11px;
        margin-top: 5px;
        opacity: 0.8;
        text-align: right;
    }

    .chat-controls {
        padding: 20px;
        background: #fff;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    #messageInput {
        flex: 1;
        padding: 12px 15px;
        border-radius: 15px;
        border: 1px solid #ced4da;
        outline: none;
        font-size: 15px;
        resize: vertical;
        min-height: 46px;
        max-height: 150px;
        font-family: inherit;
    }

    #messageInput:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }

    .btn-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        border: none;
        background: #f1f3f5;
        color: #555;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: background 0.2s;
    }

    .btn-icon:hover {
        background: #e9ecef;
    }

    #sendBtn {
        padding: 0 25px;
        background-color: #0d6efd;
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: bold;
        height: 46px;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    #sendBtn:hover {
        background-color: #0b5ed7;
    }

    #chatBox::-webkit-scrollbar {
        width: 6px;
    }

    #chatBox::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }

    #chatBox::-webkit-scrollbar-track {
        background-color: transparent;
    }
</style>

<div class="container">
    <div class="chat-container">
        <div class="chat-header">
            Chat với Admin
        </div>
        <div id="chatBox">
            <div class="text-center text-muted mt-5">Đang tải tin nhắn...</div>
        </div>
        <div class="chat-controls">
            <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageSelect(this)">
            <button class="btn-icon" onclick="document.getElementById('imageInput').click()" title="Gửi ảnh">
                <i class="ion-ios-image"></i>
            </button>
            <textarea id="messageInput" rows="1" placeholder="Nhập tin nhắn..."></textarea>
            <button id="sendBtn" onclick="sendMessage()"><i class="ion-ios-send"></i> Gửi</button>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    let isScrolledToBottom = true;

    // Tự động điều chỉnh chiều cao textarea
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
            sendMessage(); // Gửi ngay khi chọn ảnh
        }
    }

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const imageInput = document.getElementById('imageInput');
        const message = input.value.trim();
        const image = imageInput.files[0];

        if (message === '' && !image) return;

        const formData = new FormData();
        formData.append('message', message);
        if (image) {
            formData.append('image', image);
        }

        fetch('api_chat_user.php?action=send_message', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    input.style.height = 'auto';
                    imageInput.value = ''; // Reset file input
                    fetchMessages();
                } else {
                    alert('Lỗi gửi tin nhắn: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function fetchMessages() {
        fetch('api_chat_user.php?action=get_messages')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let html = '';
                    if (data.messages.length === 0) {
                        html = '<div class="text-center text-muted mt-5">Chưa có tin nhắn nào. Hãy bắt đầu cuộc trò chuyện!</div>';
                    } else {
                        data.messages.forEach(msg => {
                            const isUser = msg.sender_type === 'user';
                            const classMsg = isUser ? 'user-message' : 'admin-message';

                            let statusHtml = '';
                            if (isUser && msg.read_status == 1) {
                                statusHtml = ' • Đã xem';
                            }

                            let contentHtml = '';
                            if (msg.image) {
                                contentHtml += `<img src="images/chat/${msg.image}" alt="Image">`;
                            }
                            if (msg.message_content) {
                                contentHtml += `<div>${msg.message_content}</div>`;
                            }

                            html += `<div class='message ${classMsg}'>
                                        ${contentHtml}
                                        <small>${msg.timestamp}${statusHtml}</small>
                                     </div>`;
                        });
                    }

                    if (chatBox.innerHTML !== html) {
                        chatBox.innerHTML = html;
                        if (isScrolledToBottom) {
                            scrollToBottom();
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    fetchMessages();
    setInterval(fetchMessages, 1000);

    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
</script>

<?php include 'inc/footer.php'; ?>