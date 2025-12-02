<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');

class dichvu {
    private $db;

    public function __construct() {
        $this->db = new Database();   // KHỞI TẠO $this->db
    }

    // Lấy tất cả dịch vụ
    public function show_dichvu_all() {
        $sql = "SELECT * FROM dichvu ORDER BY id_dichvu ASC";
        return $this->db->select($sql);
    }

    // Lấy dịch vụ theo mã (id_dichvu)
    public function show_dichvu_by_id($id) {
        $id = (int)$id; 
        $sql = "SELECT * FROM dichvu WHERE id_dichvu = {$id} LIMIT 1";
        return $this->db->select($sql);
    }
}
