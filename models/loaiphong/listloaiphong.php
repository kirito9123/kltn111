<?php
$filepath = realpath(dirname(__FILE__) . '/../../');
include_once($filepath . '/lib/database.php');

class listloaiphong {
    private $db;
    public function __construct() { $this->db = new Database(); }

    public function show_loaiphong() {
        $sql = "SELECT maloaiphong, tenloaiphong, ghichu
                FROM loaiphong
                ORDER BY tenloaiphong ASC";
        return $this->db->select($sql);
    }
}
