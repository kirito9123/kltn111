<?php
require_once '../lib/database.php';
require_once '../helpers/format.php';
require_once '../classes/hopdong.php';

$fm = new Format();
$hopdong = new HopDong();

$id = $fm->validation($_GET['id']);
$hopdong->deleteOrder($id);
header("Location: admin_orders.php");
