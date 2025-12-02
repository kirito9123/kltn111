<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath.'/../lib/database.php');
include_once ($filepath.'/../helpers/format.php');

class trangthietbi {
    private $db;
    private $fm;

    public function __construct(){
        $this->db = new Database();
        $this->fm = new Format(); 
    }

    // Hàm thêm thiết bị
    public function insert_thietbi($data, $files) {
        $tenthietbi = mysqli_real_escape_string($this->db->link, $data['tenthietbi']);
        $id_phong = mysqli_real_escape_string($this->db->link, $data['id_phong']);
        $ghichu = mysqli_real_escape_string($this->db->link, $data['ghichu']);
        $tinhtrang = mysqli_real_escape_string($this->db->link, $data['tinhtrang']); // Trạng thái mới

        $file_name = $_FILES['image']['name'];
        $file_temp = $_FILES['image']['tmp_name'];

        $div = explode('.', $file_name);
        $file_ext = strtolower(end($div));
        $unique_image = substr(md5(time()), 0, 10).'.'.$file_ext;
        $uploaded_image = "../images/equipment/".$unique_image; 

        if(empty($tenthietbi) || empty($id_phong) || empty($tinhtrang)) {
            $alert = "<span class='error'>Tên thiết bị, Phòng và Trạng thái không được để trống!</span>";
            return $alert;
        } else {
            $query = "";
            $image_field = !empty($file_name) ? ", hinhanh_thietbi" : "";
            $image_value = !empty($file_name) ? ", '$unique_image'" : "";
            
            if(!empty($file_name)) move_uploaded_file($file_temp, $uploaded_image);

            $query = "INSERT INTO trangthietbi(tenthietbi, id_phong, ghichu, tinhtrang_trangthietbi {$image_field}, xoa) 
                      VALUES('$tenthietbi', '$id_phong', '$ghichu', '$tinhtrang' {$image_value}, 0)";
            
            $result = $this->db->insert($query);
            if ($result) {
                echo "<script>alert('Thêm trang thiết bị thành công'); window.location.href='equipmentlist.php';</script>";
                exit();
            } else {
                echo "<script>alert('Thêm trang thiết bị thất bại'); window.location.href='equipmentadd.php';</script>";
                exit();
            }
        }
    }

    // Hàm hiển thị thiết bị
    // public function show_thietbi() {
    //     // Chỉ lấy các thiết bị chưa bị ẩn (xoa = 0)
    //     $sql = "SELECT tt.*, p.tenphong 
    //             FROM trangthietbi tt
    //             INNER JOIN phong p ON tt.id_phong = p.id_phong
    //             WHERE tt.xoa = 0  
    //             ORDER BY tt.id_phong DESC, tt.id_thietbi DESC";
    //     return $this->db->select($sql);
    // }
    
    // Hàm lấy thiết bị theo ID
    public function getthietbibyid($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "SELECT * FROM trangthietbi WHERE id_thietbi = '$id'";
        return $this->db->select($query);
    }

    // Hàm cập nhật thiết bị
    public function update_thietbi($data, $files, $id) {
        $tenthietbi = mysqli_real_escape_string($this->db->link, $data['tenthietbi']);
        $id_phong = mysqli_real_escape_string($this->db->link, $data['id_phong']);
        $ghichu = mysqli_real_escape_string($this->db->link, $data['ghichu']);
        $tinhtrang = mysqli_real_escape_string($this->db->link, $data['tinhtrang']); // Trạng thái mới
        $id = mysqli_real_escape_string($this->db->link, $id);
        
        $file_name = $_FILES['image']['name'];
        $file_temp = $_FILES['image']['tmp_name'];
        
        if(empty($tenthietbi) || empty($id_phong) || empty($tinhtrang)) {
            $alert = "<span class='error'>Tên thiết bị, Phòng và Trạng thái không được để trống!</span>";
            return $alert;
        } else {
            $update_query = "UPDATE trangthietbi SET 
                            tenthietbi = '$tenthietbi',
                            id_phong = '$id_phong',
                            ghichu = '$ghichu',
                            tinhtrang_trangthietbi = '$tinhtrang'";

            if(!empty($file_name)){
                $div = explode('.', $file_name);
                $file_ext = strtolower(end($div));
                $unique_image = substr(md5(time()), 0, 10).'.'.$file_ext;
                $uploaded_image = "../images/equipment/".$unique_image;
                
                move_uploaded_file($file_temp, $uploaded_image);
                $update_query .= ", hinhanh_thietbi = '$unique_image'";
            }
            
            $update_query .= " WHERE id_thietbi = '$id'";

            $result = $this->db->update($update_query);
            if ($result) {
                echo "<script>alert('Cập nhật trang thiết bị thành công'); window.location.href='equipmentlist.php';</script>";
                exit();
            } else {
                echo "<script>alert('Cập nhật trang thiết bị thất bại'); window.location.href='equipmentedit.php?id=$id';</script>";
                exit();
            }
        }
    }

    // Hàm ẩn (Chuyển trạng thái xóa: xoa = 1)
    public function del_thietbi($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE trangthietbi SET xoa = 1 WHERE id_thietbi = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Ẩn trang thiết bị thành công'); window.location.href='equipmentlist.php';</script>";
            exit();
        } else {
            echo "<script>alert('Ẩn trang thiết bị thất bại'); window.location.href='equipmentlist.php';</script>";
            exit();
        }
    }

    // Hàm hiển thị danh sách ẩn (xoa = 1)
    public function show_thietbi_an() {
        $sql = "SELECT tt.*, p.tenphong 
                FROM trangthietbi tt
                INNER JOIN phong p ON tt.id_phong = p.id_phong
                WHERE tt.xoa = 1  
                ORDER BY tt.id_phong DESC, tt.id_thietbi DESC";
        return $this->db->select($sql);
    }

    // Hàm khôi phục (xoa = 0)
    public function restore_thietbi($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE trangthietbi SET xoa = 0 WHERE id_thietbi = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Khôi phục trang thiết bị thành công'); window.location.href='equipmentlist_hidden.php';</script>";
            exit();
        } else {
            echo "<script>alert('Khôi phục trang thiết bị thất bại'); window.location.href='equipmentlist_hidden.php';</script>";
            exit();
        }
    }

    // Hàm xóa vĩnh viễn
    public function delete_thietbi_permanently($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "DELETE FROM trangthietbi WHERE id_thietbi = '$id'";
        $result = $this->db->delete($query);
        if ($result) {
            echo "<script>alert('Xóa hoàn toàn trang thiết bị thành công'); window.location.href='equipmentlist_hidden.php';</script>";
            exit();
        } else {
            echo "<script>alert('Xóa hoàn toàn trang thiết bị thất bại'); window.location.href='equipmentlist_hidden.php';</script>";
            exit();
        }
    }
    public function show_thietbi() {
    // ✅ Truy vấn JOIN 3 bảng để lấy tenloaiphong
    $sql = "SELECT tt.*, p.tenphong, lp.tenloaiphong 
            FROM trangthietbi tt
            INNER JOIN phong p ON tt.id_phong = p.id_phong
            LEFT JOIN loaiphong lp ON p.maloaiphong = lp.maloaiphong
            WHERE tt.xoa = 0 
            ORDER BY tt.id_thietbi DESC";
            
    return $this->db->select($sql);
    }

}
?>