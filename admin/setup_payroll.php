<?php
include '../lib/database.php';
$db = new Database();

$queries = [
    "CREATE TABLE IF NOT EXISTS `luong` (
        `id_luong` int(11) NOT NULL AUTO_INCREMENT,
        `mans` int(11) NOT NULL,
        `luong_ca` double DEFAULT 0,
        `phu_cap` double DEFAULT 0,
        `luongcoban` double DEFAULT 0,
        PRIMARY KEY (`id_luong`),
        KEY `mans` (`mans`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `bang_luong` (
        `id_bangluong` int(11) NOT NULL AUTO_INCREMENT,
        `thang` int(11) NOT NULL,
        `nam` int(11) NOT NULL,
        `mans` int(11) NOT NULL,
        `tong_ca` int(11) DEFAULT 0,
        `muc_luong_ca` double DEFAULT 0,
        `phu_cap` double DEFAULT 0,
        `tien_thuong` double DEFAULT 0,
        `tien_phat` double DEFAULT 0,
        `ly_do` text,
        `thuc_lanh` double DEFAULT 0,
        `trang_thai` tinyint(1) DEFAULT 0,
        `ngay_thanh_toan` datetime DEFAULT NULL,
        PRIMARY KEY (`id_bangluong`),
        KEY `mans` (`mans`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

echo "<h2>Đang khởi tạo bảng dữ liệu lương...</h2>";

foreach ($queries as $sql) {
    $result = $db->link->query($sql);
    if ($result) {
        echo "<p style='color:green'>✅ Executed successfully: " . substr($sql, 0, 50) . "...</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . $db->link->error . "</p>";
    }
}

echo "<h3>Hoàn tất! Bạn có thể quay lại trang Quản lý lương.</h3>";
echo "<a href='quanlyluong.php'>Quay lại</a>";
