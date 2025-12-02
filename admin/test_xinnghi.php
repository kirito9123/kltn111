<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../classes/lichdangky.php';

try {
    echo "Instantiating lichdangky...<br>";
    $lich = new lichdangky();
    echo "Instantiated.<br>";

    echo "Calling get_leave_requests...<br>";
    $requests = $lich->get_leave_requests();

    if ($requests) {
        echo "Got requests.<br>";
        while ($row = $requests->fetch_assoc()) {
            echo "Row: " . print_r($row, true) . "<br>";
        }
    } else {
        echo "No requests found (or false returned).<br>";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString();
}
