<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class loaimon {
    private $db;
    private $fm;

    public function __construct() {
        $this->db = new Database();
        $this->fm = new Format();
    }

    public function insert_loai($tenloai, $ghichu) {
        $tenloai = $this->fm->validation($tenloai);
        $ghichu = $this->fm->validation($ghichu);

        $tenloai = mysqli_real_escape_string($this->db->link, $tenloai);
        $ghichu = mysqli_real_escape_string($this->db->link, $ghichu);

        if (empty($tenloai)) {
            echo "<script>alert('Vui lòng nhập tên loại món ăn'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            exit();
        } else {
            $query = "INSERT INTO loai_mon(name_loai, ghichu, xoa) VALUES('$tenloai', '$ghichu', 0)";
            $result = $this->db->insert($query);
            if ($result) {
                echo "<script>alert('Thêm loại món thành công'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            } else {
                echo "<script>alert('Lỗi khi thêm loại món'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            }
            exit();
        }
    }

    public function show_loai() {
        $query = "SELECT * FROM loai_mon WHERE xoa = 0 ORDER BY id_loai DESC";
        return $this->db->select($query);
    }

    public function show_loaimenu() {
        $query = "SELECT * FROM loai_mon WHERE xoa = 0";
        return $this->db->select($query);
    }

    public function update_loai($tenloai, $ghichu, $id) {
        $tenloai = $this->fm->validation($tenloai);
        $ghichu = $this->fm->validation($ghichu);

        $tenloai = mysqli_real_escape_string($this->db->link, $tenloai);
        $ghichu = mysqli_real_escape_string($this->db->link, $ghichu);
        $id = mysqli_real_escape_string($this->db->link, $id);

        if (empty($tenloai)) {
            echo "<script>alert('Vui lòng nhập tên loại món ăn'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            exit();
        } else {
            $query = "UPDATE loai_mon SET name_loai='$tenloai', ghichu='$ghichu' WHERE id_loai='$id'";
            $result = $this->db->update($query);
            if ($result) {
                echo "<script>alert('Cập nhật loại món thành công'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            } else {
                echo "<script>alert('Lỗi khi cập nhật loại món'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
            }
            exit();
        }
    }

    public function del_loai($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE loai_mon SET xoa = 1 WHERE id_loai = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Đã ẩn loại món thành công'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
        } else {
            echo "<script>alert('Ẩn loại món thất bại'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
        }
        exit();
    }

    public function getloaibyid($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "SELECT * FROM loai_mon WHERE id_loai='$id'";
        return $this->db->select($query);
    }

    public function show_loai_an() {
        $query = "SELECT * FROM loai_mon WHERE xoa = 1 ORDER BY id_loai DESC";
        return $this->db->select($query);
    }

    public function restore_loai($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE loai_mon SET xoa = 0 WHERE id_loai = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Khôi phục loại món thành công'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
        } else {
            echo "<script>alert('Khôi phục loại món thất bại'); window.location.href='http://restaurant.test/admin/catlist.php';</script>";
        }
        exit();
    }

}
?>
