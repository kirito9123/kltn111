<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting test_xinnghi_add...<br>";

include '../classes/lichdangky.php';

try {
    $lich = new lichdangky();
    echo "Instantiated.<br>";

    // Test get_all_nhansu_active
    echo "Calling get_all_nhansu_active...<br>";
    $nhansu = $lich->get_all_nhansu_active();
    if ($nhansu) {
        echo "Got nhansu list. First row: " . print_r($nhansu->fetch_assoc(), true) . "<br>";
    } else {
        echo "No nhansu found.<br>";
    }

    // Test get_future_shifts_by_employee
    // Need a valid mans. Let's pick one from the list if available, or just use a dummy.
    $mans = 1; // Assuming 1 exists, or use the one from above if fetched.

    echo "Calling get_future_shifts_by_employee for mans=$mans...<br>";
    $shifts = $lich->get_future_shifts_by_employee($mans);

    if ($shifts) {
        echo "Got shifts.<br>";
        while ($row = $shifts->fetch_assoc()) {
            echo "Shift: " . $row['ten_ca'] . " on " . $row['ngay'] . "<br>";
        }
    } else {
        echo "No future shifts found (or false returned).<br>";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
