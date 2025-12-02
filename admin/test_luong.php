<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../classes/luong.php';

try {
    $luong = new Luong();
    echo "Luong class instantiated successfully.";

    $ds = $luong->layDanhSachMucLuong();
    if ($ds) {
        echo "layDanhSachMucLuong returned result.";
    } else {
        echo "layDanhSachMucLuong returned false (likely DB error or no data).";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
