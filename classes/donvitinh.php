<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class donvitinh
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    // 1. LẤY DANH SÁCH (Chỉ lấy xoa = 0)
    public function show_don_vi_tinh()
    {
        // Thêm điều kiện WHERE xoa = 0
        $query = "SELECT * FROM don_vi_tinh WHERE xoa = 0 ORDER BY nhom ASC, he_so ASC";
        return $this->db->select($query);
    }

    // Hàm hiển thị danh sách đã xóa (nếu ông muốn làm trang Thùng rác sau này)
    public function show_don_vi_tinh_deleted()
    {
        $query = "SELECT * FROM don_vi_tinh WHERE xoa = 1 ORDER BY id_dvt DESC";
        return $this->db->select($query);
    }

    public function get_don_vi_by_id($id)
    {
        $query = "SELECT * FROM don_vi_tinh WHERE id_dvt = '$id' LIMIT 1";
        return $this->db->select($query);
    }

    public function insert_don_vi_tinh($ten_dvt, $nhom, $he_so)
    {
        $ten_dvt = mysqli_real_escape_string($this->db->link, $ten_dvt);
        $nhom    = mysqli_real_escape_string($this->db->link, $nhom);
        $he_so   = mysqli_real_escape_string($this->db->link, $he_so);

        if (empty($ten_dvt) || empty($nhom) || empty($he_so)) {
            return "Vui lòng điền đầy đủ thông tin.";
        }

        // Mặc định xoa = 0
        $query = "INSERT INTO don_vi_tinh (ten_dvt, nhom, he_so, xoa) 
                  VALUES ('$ten_dvt', '$nhom', '$he_so', 0)";
        
        return $this->db->insert($query);
    }

    public function update_don_vi_tinh($id, $ten_dvt, $nhom, $he_so)
    {
        $ten_dvt = mysqli_real_escape_string($this->db->link, $ten_dvt);
        $nhom    = mysqli_real_escape_string($this->db->link, $nhom);
        $he_so   = mysqli_real_escape_string($this->db->link, $he_so);

        if (empty($ten_dvt) || empty($nhom) || empty($he_so)) {
            return "Vui lòng điền đầy đủ thông tin.";
        }

        $query = "UPDATE don_vi_tinh 
                  SET ten_dvt = '$ten_dvt', 
                      nhom    = '$nhom', 
                      he_so   = '$he_so' 
                  WHERE id_dvt = '$id'";
        
        return $this->db->update($query);
    }

    // 5. XÓA MỀM (UPDATE xoa = 1)
    public function delete_don_vi_tinh($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        
        // Chuyển trạng thái xoa thành 1 thay vì DELETE FROM
        $query = "UPDATE don_vi_tinh SET xoa = 1 WHERE id_dvt = '$id'";
        return $this->db->update($query);
    }
    
    // Hàm khôi phục (nếu lỡ tay xóa nhầm)
    public function restore_don_vi_tinh($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE don_vi_tinh SET xoa = 0 WHERE id_dvt = '$id'";
        return $this->db->update($query);
    }
}
?>