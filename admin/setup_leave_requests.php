<?php
include '../lib/database.php';
$db = new Database();

$sql = "CREATE TABLE IF NOT EXISTS `tbl_xinnghi` (
  `id_xinnghi` int(11) NOT NULL AUTO_INCREMENT,
  `mans` int(11) NOT NULL,
  `id_ca` int(11) NOT NULL,
  `ngay` date NOT NULL,
  `ly_do` text NOT NULL,
  `trang_thai` tinyint(1) DEFAULT 0 COMMENT '0: Chờ, 1: Duyệt, 2: Từ chối',
  `ngay_tao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_xinnghi`),
  KEY `mans` (`mans`),
  KEY `id_ca` (`id_ca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

echo "<h2>Đang khởi tạo bảng xin nghỉ...</h2>";

$result = $db->link->query($sql);
if ($result) {
    echo "<p style='color:green'>✅ Executed successfully: CREATE TABLE tbl_xinnghi...</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . $db->link->error . "</p>";
}

echo "<h3>Hoàn tất! Bạn có thể quay lại trang Danh sách xin nghỉ.</h3>";
echo "<a href='xinnghi_list.php'>Quay lại</a>";
