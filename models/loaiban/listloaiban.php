<?php
$filepath = realpath(dirname(__FILE__) . '/../../');
include_once($filepath . '/lib/database.php');

class listloaiban {
    private $db;
    public function __construct() { $this->db = new Database(); }

    public function show_loaiban() {
        $sql = "SELECT id_loaiban, tenloaiban, mota
                FROM loaiban
                ORDER BY tenloaiban ASC";
        return $this->db->select($sql);
    }
}
