<?php
include '../lib/database.php';
$db = new Database();

// 1. Drop existing table
$drop = "DROP TABLE IF EXISTS `tbl_xinnghi`";
$db->link->query($drop);
echo "Dropped table tbl_xinnghi.<br>";

// 2. Create new table
$sql = "CREATE TABLE `tbl_xinnghi` (
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

$result = $db->link->query($sql);
if ($result) {
    echo "<p style='color:green'>✅ Re-created table tbl_xinnghi successfully.</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . $db->link->error . "</p>";
}

echo "<a href='xinnghi_list.php'>Quay lại</a>";
