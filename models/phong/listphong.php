<?php
$filepath = realpath(dirname(__FILE__) . '/../../');
include_once($filepath . '/lib/database.php');

class listphong {
    private $db;
    public function __construct() { $this->db = new Database(); }

    public function show_phong_all() {
        $sql = "SELECT p.id_phong, p.tenphong, p.maloaiphong, p.hinhanh,
                       lp.tenloaiphong
                FROM phong p
                LEFT JOIN loaiphong lp ON lp.maloaiphong = p.maloaiphong
                ORDER BY p.maloaiphong ASC, p.id_phong ASC";
        return $this->db->select($sql);
    }

    public function show_phongbyloaiphong($maloaiphong) {
        $id = (int)$maloaiphong;
        $sql = "SELECT p.id_phong, p.tenphong, p.maloaiphong, p.hinhanh,
                       lp.tenloaiphong
                FROM phong p
                LEFT JOIN loaiphong lp ON lp.maloaiphong = p.maloaiphong
                WHERE p.maloaiphong = {$id}
                ORDER BY p.id_phong ASC";
        return $this->db->select($sql);
    }

    public function get_phong($id_phong) {
        $id = (int)$id_phong;
        $sql = "SELECT p.*, lp.tenloaiphong
                FROM phong p
                LEFT JOIN loaiphong lp ON lp.maloaiphong = p.maloaiphong
                WHERE p.id_phong = {$id} LIMIT 1";
        return $this->db->select($sql);
    }
}
