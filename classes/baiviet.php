<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath.'/../lib/database.php');
include_once ($filepath.'/../helpers/format.php');

class baiviet {
    private $db;
    private $fm;
    private $upload_dir = "../images/baiviet/";

    public function __construct(){
        $this->db = new Database();
        $this->fm = new Format(); 
        // Đảm bảo thư mục upload tồn tại
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    // --- HELPER FUNCTIONS ---

    private function process_file_upload($file_key, $old_file_name = null) {
        $file_name = $_FILES[$file_key]['name'];
        $file_temp = $_FILES[$file_key]['tmp_name'];

        if (empty($file_name)) {
            return $old_file_name; // Giữ lại tên file cũ nếu không upload file mới
        }

        $div = explode('.', $file_name);
        $file_ext = strtolower(end($div));
        $unique_image = substr(md5(time() . $file_key), 0, 15).'.'.$file_ext;
        $uploaded_image = $this->upload_dir . $unique_image; 
        
        if ($file_ext === 'jpg' || $file_ext === 'jpeg' || $file_ext === 'png' || $file_ext === 'gif') {
            move_uploaded_file($file_temp, $uploaded_image);
            
            // Xóa file cũ nếu có và đang update
            if ($old_file_name && $old_file_name !== $unique_image && file_exists($this->upload_dir . $old_file_name)) {
                unlink($this->upload_dir . $old_file_name);
            }
            return $unique_image;
        }
        return false; // Lỗi định dạng file
    }

    // --- CRUD FUNCTIONS ---

    public function insert_baiviet($data, $files) {
        $ten_baiviet = mysqli_real_escape_string($this->db->link, $data['ten_baiviet']);
        $noidung_tongquan = mysqli_real_escape_string($this->db->link, $data['noidung_tongquan']);
        $ngay_tao = date('Y-m-d H:i:s');
        $xoa = 0; // Mặc định chưa xóa

        // 1. Validate mandatory fields
        if(empty($ten_baiviet) || empty($noidung_tongquan)) {
            return "<span class='error'>Tên bài viết và Nội dung tổng quan không được để trống!</span>";
        }

        // 2. Process contents
        $contents = [];
        for ($i = 1; $i <= 5; $i++) {
            $contents['noidung_'.$i] = mysqli_real_escape_string($this->db->link, $data['noidung_'.$i] ?? '');
        }

        // 3. Process images (6 fields)
        $images = [];
        $images['anh_chinh'] = $this->process_file_upload('anh_chinh');
        for ($i = 1; $i <= 5; $i++) {
            $images['anh_'.$i] = $this->process_file_upload('anh_'.$i);
        }
        
        // 4. Construct Query
        $field_names = "ten_baiviet, noidung_tongquan, anh_chinh, ngay_tao, xoa";
        $field_values = "'$ten_baiviet', '$noidung_tongquan', '{$images['anh_chinh']}', '$ngay_tao', '$xoa'";

        // Add optional content and images
        for ($i = 1; $i <= 5; $i++) {
            $field_names .= ", anh_{$i}, noidung_{$i}";
            $field_values .= ", '{$images['anh_'.$i]}', '{$contents['noidung_'.$i]}'";
        }

        $query = "INSERT INTO baiviet ({$field_names}) VALUES ({$field_values})";
        $result = $this->db->insert($query);

        if ($result) {
            // Chuyển hướng sau khi thêm thành công
            echo "<script>alert('Thêm bài viết thành công!'); window.location.href='baivietlist.php';</script>";
            exit();
        } else {
            return "<span class='error'>Thêm bài viết thất bại!</span>";
        }
    }

    public function show_baiviet($xoa_status = 0) {
        $query = "SELECT * FROM baiviet WHERE xoa = '$xoa_status' ORDER BY id_baiviet DESC";
        $result = $this->db->select($query);
        return $result;
    }
    
    public function get_baiviet_by_id($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "SELECT * FROM baiviet WHERE id_baiviet = '$id'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    public function update_baiviet($data, $files, $id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $ten_baiviet = mysqli_real_escape_string($this->db->link, $data['ten_baiviet']);
        $noidung_tongquan = mysqli_real_escape_string($this->db->link, $data['noidung_tongquan']);
        
        // Lấy thông tin bài viết cũ để xử lý hình ảnh
        $old_baiviet = $this->get_baiviet_by_id($id);
        if (!$old_baiviet) {
            return "<span class='error'>Không tìm thấy Bài viết để cập nhật!</span>";
        }

        // Bắt đầu chuỗi SET
        $update_set = "ten_baiviet = '$ten_baiviet', noidung_tongquan = '$noidung_tongquan'";

        // 1. Process contents (noidung_1 to noidung_5)
        for ($i = 1; $i <= 5; $i++) {
            $noidung_i = mysqli_real_escape_string($this->db->link, $data['noidung_'.$i] ?? '');
            $update_set .= ", noidung_{$i} = '$noidung_i'";
        }

        // 2. Process images (anh_chinh, anh_1 to anh_5)
        $image_fields = ['anh_chinh', 'anh_1', 'anh_2', 'anh_3', 'anh_4', 'anh_5'];
        foreach ($image_fields as $field) {
            $old_image_name = $old_baiviet[$field];
            $new_image_name = $this->process_file_upload($field, $old_image_name);
            
            if ($new_image_name === false) {
                 return "<span class='error'>File ảnh {$field} không đúng định dạng!</span>";
            }
            $update_set .= ", {$field} = '$new_image_name'";
        }

        // 3. Construct Final Query
        $query = "UPDATE baiviet SET {$update_set} WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);

        if ($result) {
            // === PHẦN ĐÃ CHỈNH SỬA: Thông báo và Chuyển hướng ===
            echo "<script>alert('Cập nhật bài viết thành công!'); window.location.href='baivietlist.php';</script>";
            exit(); 
            // ======================================================
        } else {
            return "<span class='error'>Cập nhật bài viết thất bại!</span>";
        }
    }

    // Xóa mềm (chuyển xoa = 1)
    public function del_baiviet($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE baiviet SET xoa = 1 WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Ẩn bài viết thành công'); window.location.href='baivietlist.php';</script>";
            exit();
        } else {
            echo "<script>alert('Ẩn bài viết thất bại'); window.location.href='baivietlist.php';</script>";
            exit();
        }
    }

    // Khôi phục (chuyển xoa = 0)
    public function restore_baiviet($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE baiviet SET xoa = 0 WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Khôi phục bài viết thành công'); window.location.href='baivietlist_hidden.php';</script>";
            exit();
        } else {
            echo "<script>alert('Khôi phục bài viết thất bại'); window.location.href='baivietlist_hidden.php';</script>";
            exit();
        }
    }

    // Xóa vĩnh viễn (bao gồm xóa cả file ảnh)
    public function delete_baiviet_permanently($id) {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $old_baiviet = $this->get_baiviet_by_id($id);
        
        if ($old_baiviet) {
             // Xóa các file ảnh
             $image_fields = ['anh_chinh', 'anh_1', 'anh_2', 'anh_3', 'anh_4', 'anh_5'];
             foreach ($image_fields as $field) {
                 if (!empty($old_baiviet[$field]) && file_exists($this->upload_dir . $old_baiviet[$field])) {
                     unlink($this->upload_dir . $old_baiviet[$field]);
                 }
             }
        }

        $query = "DELETE FROM baiviet WHERE id_baiviet = '$id'";
        $result = $this->db->delete($query);
        
        if ($result) {
            echo "<script>alert('Xóa hoàn toàn bài viết thành công'); window.location.href='baivietlist_hidden.php';</script>";
            exit();
        } else {
            echo "<script>alert('Xóa hoàn toàn bài viết thất bại'); window.location.href='baivietlist_hidden.php';</script>";
            exit();
        }
    }

    /**
     * Lấy danh sách các bài viết mới nhất (không bị xóa)
     * @param int $limit Số lượng bài viết muốn lấy
     * @return mixed Kết quả truy vấn (mysqli_result) hoặc false
     */
    public function get_latest_posts($limit)
    {
        $limit = (int)$limit; // Đảm bảo limit là số nguyên
        // Lấy bài viết không bị xóa (xoa = 0)
        $query = "SELECT id_baiviet, ten_baiviet, anh_chinh, ngay_tao FROM baiviet 
                  WHERE xoa = 0 
                  ORDER BY ngay_tao DESC 
                  LIMIT $limit";

        $result = $this->db->select($query); 
        return $result;
    }
}
?>