<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname(__FILE__) . '/../lib/database.php';
require dirname(__FILE__) . '/../vendor/autoload.php';

class Chat implements MessageComponentInterface
{
    private $db;
    private $clients;

    public function __construct()
    {
        $this->db = new Database();
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        $id_user = $data['id_user'];
        $id_admin = $data['id_admin'];
        $message = mysqli_real_escape_string($this->db->link, $data['message']);
        $sender_type = $data['sender_type'];
        $read_status = ($sender_type == 'admin') ? 1 : 0; // Tin nhắn từ admin đánh dấu đã đọc, từ user là chưa đọc

        // Lưu tin nhắn vào DB
        $query = "INSERT INTO messages (id_user, id_admin, message_content, sender_type, read_status, timestamp) 
                  VALUES ('$id_user', '$id_admin', '$message', '$sender_type', '$read_status', NOW())";
        $result = $this->db->insert($query);
        if (!$result) {
            error_log("Failed to insert message: " . $this->db->link->error);
        }

        // Lấy số tin nhắn chưa đọc cho user này
        $unreadQuery = "SELECT COUNT(*) as unread_count FROM messages WHERE id_user = '$id_user' AND id_admin = '$id_admin' AND sender_type = 'user' AND read_status = 0";
        $unreadResult = $this->db->select($unreadQuery);
        $unreadCount = $unreadResult ? $unreadResult->fetch_assoc()['unread_count'] : 0;

        // Broadcast tới tất cả client
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'id_user' => $id_user,
                'id_admin' => $id_admin,
                'message' => $message,
                'sender_type' => $sender_type,
                'timestamp' => date('Y-m-d H:i:s'),
                'unread_count' => $unreadCount
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);
$server->run();
