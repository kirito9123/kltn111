<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting check_fix v3...<br>";

include '../classes/lichdangky.php';

try {
    $lich = new lichdangky();
    echo "Instantiated.<br>";

    echo "Calling get_leave_requests...<br>";
    $requests = $lich->get_leave_requests();

    if ($requests) {
        echo "Got requests object.<br>";
        if ($requests->num_rows > 0) {
            while ($row = $requests->fetch_assoc()) {
                echo "Row: " . print_r($row, true) . "<br>";
            }
        } else {
            echo "No requests found (empty table).<br>";
        }
    } else {
        echo "get_leave_requests returned false.<br>";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
