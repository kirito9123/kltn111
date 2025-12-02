<?php
$filepath= realpath(dirname(__FILE__));

include_once ($filepath.'/../lib/database.php');
include_once ($filepath.'/../helpers/format.php');

class Admin {
    private $db;
    private $fm;

    public function __construct(){
        $this->db = new Database();
        $this->fm = new Format();
    }

    public function getUserInfo($id_admin) {
        $query = "SELECT * FROM tb_admin WHERE id_admin='$id_admin' LIMIT 1";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    public function showAllUsers() {
        $query = "SELECT * FROM tb_admin";
        $result = $this->db->select($query);
        return $result;
    }
}
?>
