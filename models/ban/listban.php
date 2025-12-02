<?php
include_once __DIR__ . '/../../lib/database.php';

class listban
{
    private $db;
    
    public function __construct()
    {
        $this->db = new Database();
    }
    
    public function show_ban($id_phong, $id_loaiban){
        $id_phong = mysqli_real_escape_string($this->db->link, $id_phong);
        $id_loaiban = mysqli_real_escape_string($this->db->link, $id_loaiban);
        
        $query = "
            SELECT b.*, b.trangthai
            FROM ban b
            WHERE b.id_phong = '$id_phong' 
              AND b.id_loaiban = '$id_loaiban' 
            ORDER BY b.id_ban ASC
        ";
        $result = $this->db->select($query);
        return $result;
    }
}
?>