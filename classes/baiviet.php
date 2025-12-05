<?php
// FILE: classes/baiviet.php

$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class baiviet
{
    private $db;
    private $fm;
    private $upload_dir = "../images/baiviet/";

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
        // Đảm bảo thư mục upload tồn tại
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    /**
     * Danh sách thể loại bài viết (Theo cấu trúc DB của bạn)
     */
    public function get_all_categories()
    {
        return [
            1 => 'Hướng dẫn nấu ăn',
            2 => 'Tin thế giới',
            3 => 'Tin nhà hàng',
            4 => 'Tin khuyến mãi',
            5 => 'Tin khác'
        ];
    }

    /**
     * Lấy tên thể loại từ ID
     */
    public function get_category_name($id)
    {
        $cats = $this->get_all_categories();
        return isset($cats[$id]) ? $cats[$id] : 'Chưa phân loại';
    }

    // --- HELPER: Xử lý upload file ---
    private function process_file_upload($file_key, $old_file_name = null)
    {
        if (!isset($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
            return $old_file_name; // Giữ lại tên file cũ nếu không upload file mới
        }

        $file_name = $_FILES[$file_key]['name'];
        $file_temp = $_FILES[$file_key]['tmp_name'];

        $div = explode('.', $file_name);
        $file_ext = strtolower(end($div));
        $unique_image = substr(md5(time() . $file_key . rand()), 0, 15) . '.' . $file_ext;
        $uploaded_image = $this->upload_dir . $unique_image;

        $permitted = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $permitted)) {
            move_uploaded_file($file_temp, $uploaded_image);

            // Xóa file cũ nếu có và đang update (để tránh rác server)
            if ($old_file_name && $old_file_name !== $unique_image && file_exists($this->upload_dir . $old_file_name)) {
                unlink($this->upload_dir . $old_file_name);
            }
            return $unique_image;
        }
        return false; // Lỗi định dạng file
    }

    // --- CRUD FUNCTIONS ---

    public function insert_baiviet($data, $files)
    {
        $ten_baiviet = mysqli_real_escape_string($this->db->link, $data['ten_baiviet']);
        $noidung_tongquan = mysqli_real_escape_string($this->db->link, $data['noidung_tongquan']);

        // Lấy thể loại từ form, mặc định là 5 (Tin khác) nếu không chọn
        $theloai = isset($data['theloai']) ? (int)$data['theloai'] : 5;

        $ngay_tao = date('Y-m-d H:i:s');
        $xoa = 0;

        if (empty($ten_baiviet) || empty($noidung_tongquan)) {
            return "<span class='error'>Tên bài viết và Nội dung tổng quan không được để trống!</span>";
        }

        // Xử lý nội dung chi tiết (1-5)
        $contents = [];
        for ($i = 1; $i <= 5; $i++) {
            $contents['noidung_' . $i] = mysqli_real_escape_string($this->db->link, $data['noidung_' . $i] ?? '');
        }

        // Xử lý hình ảnh
        $images = [];
        $images['anh_chinh'] = $this->process_file_upload('anh_chinh');
        if ($images['anh_chinh'] === false && !empty($_FILES['anh_chinh']['name'])) {
            return "<span class='error'>Ảnh chính không đúng định dạng!</span>";
        }

        for ($i = 1; $i <= 5; $i++) {
            $images['anh_' . $i] = $this->process_file_upload('anh_' . $i);
        }

        // Tạo câu lệnh SQL chèn dữ liệu
        $field_names = "ten_baiviet, noidung_tongquan, theloai, anh_chinh, ngay_tao, xoa";
        $field_values = "'$ten_baiviet', '$noidung_tongquan', '$theloai', '{$images['anh_chinh']}', '$ngay_tao', '$xoa'";

        for ($i = 1; $i <= 5; $i++) {
            $field_names .= ", anh_{$i}, noidung_{$i}";
            $field_values .= ", '{$images['anh_' .$i]}', '{$contents['noidung_' .$i]}'";
        }

        $query = "INSERT INTO baiviet ({$field_names}) VALUES ({$field_values})";
        $result = $this->db->insert($query);

        if ($result) {
            echo "<script>alert('Thêm bài viết thành công!'); window.location.href='baivietlist.php';</script>";
            exit();
        } else {
            return "<span class='error'>Thêm bài viết thất bại! Lỗi: " . $this->db->link->error . "</span>";
        }
    }

    /**
     * Lấy danh sách bài viết (Có hỗ trợ lọc theo danh mục)
     * @param int $xoa_status Trạng thái xóa (0: hiện, 1: ẩn)
     * @param int|null $catid ID danh mục cần lọc (null = lấy hết)
     */
    public function show_baiviet($xoa_status = 0, $catid = null)
    {
        $xoa_status = mysqli_real_escape_string($this->db->link, $xoa_status);

        $query = "SELECT * FROM baiviet WHERE xoa = '$xoa_status'";

        // Nếu có lọc theo danh mục
        if ($catid !== null && is_numeric($catid)) {
            $catid = (int)$catid;
            $query .= " AND theloai = '$catid'";
        }

        $query .= " ORDER BY id_baiviet DESC";

        $result = $this->db->select($query);
        return $result;
    }

    public function get_baiviet_by_id($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "SELECT * FROM baiviet WHERE id_baiviet = '$id'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    public function update_baiviet($data, $files, $id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $ten_baiviet = mysqli_real_escape_string($this->db->link, $data['ten_baiviet']);
        $noidung_tongquan = mysqli_real_escape_string($this->db->link, $data['noidung_tongquan']);

        // Cập nhật thể loại
        $theloai = isset($data['theloai']) ? (int)$data['theloai'] : 5;

        $old_baiviet = $this->get_baiviet_by_id($id);
        if (!$old_baiviet) {
            return "<span class='error'>Không tìm thấy Bài viết để cập nhật!</span>";
        }

        // Bắt đầu chuỗi SET
        $update_set = "ten_baiviet = '$ten_baiviet', noidung_tongquan = '$noidung_tongquan', theloai = '$theloai'";

        // Xử lý nội dung 1-5
        for ($i = 1; $i <= 5; $i++) {
            $noidung_i = mysqli_real_escape_string($this->db->link, $data['noidung_' . $i] ?? '');
            $update_set .= ", noidung_{$i} = '$noidung_i'";
        }

        // Xử lý hình ảnh
        $image_fields = ['anh_chinh', 'anh_1', 'anh_2', 'anh_3', 'anh_4', 'anh_5'];
        foreach ($image_fields as $field) {
            $old_image_name = $old_baiviet[$field];
            $new_image_name = $this->process_file_upload($field, $old_image_name);

            if ($new_image_name === false && !empty($_FILES[$field]['name'])) {
                return "<span class='error'>File ảnh {$field} không đúng định dạng!</span>";
            }

            if ($new_image_name !== false) {
                $update_set .= ", {$field} = '$new_image_name'";
            }
        }

        $query = "UPDATE baiviet SET {$update_set} WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);

        if ($result) {
            echo "<script>alert('Cập nhật bài viết thành công!'); window.location.href='baivietlist.php';</script>";
            exit();
        } else {
            return "<span class='error'>Cập nhật bài viết thất bại!</span>";
        }
    }

    // Xóa mềm (vào thùng rác)
    public function del_baiviet($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE baiviet SET xoa = 1 WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            return "<span style='color:green;'>Đã chuyển bài viết vào danh sách ẩn.</span>";
        } else {
            return "<span class='error'>Lỗi khi ẩn bài viết.</span>";
        }
    }

    // Khôi phục
    public function restore_baiviet($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE baiviet SET xoa = 0 WHERE id_baiviet = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            echo "<script>alert('Khôi phục bài viết thành công'); window.location.href='baivietlist_hidden.php';</script>";
            exit();
        }
    }

    // Xóa vĩnh viễn
    public function delete_baiviet_permanently($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $old_baiviet = $this->get_baiviet_by_id($id);

        if ($old_baiviet) {
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
        }
    }

    // Lấy bài viết mới nhất (cho Sidebar hoặc Footer)
    public function get_latest_posts($limit)
    {
        $limit = (int)$limit;
        $query = "SELECT id_baiviet, ten_baiviet, anh_chinh, ngay_tao, theloai FROM baiviet 
                  WHERE xoa = 0 
                  ORDER BY ngay_tao DESC 
                  LIMIT $limit";

        $result = $this->db->select($query);
        return $result;
    }
}
