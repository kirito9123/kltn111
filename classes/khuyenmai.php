<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class khuyenmai
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    public function getActiveKhuyenMai()
    {
        $query = "SELECT * FROM khuyenmai WHERE time_star <= NOW() AND time_end >= NOW() AND xoa = 0";
        return $this->db->select($query);
    }

    public function insert_km($data, $files)
    {
        $name_km   = $this->fm->validation($data['name_km']);
        $time_star = date('Y-m-d H:i:s', strtotime($data['time_star']));
        $time_end  = date('Y-m-d H:i:s', strtotime($data['time_end']));
        $discout   = floatval($data['discout']);
        $ghichu    = $this->fm->validation($data['ghichu']);

        $permited   = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name  = $files['image']['name'];
        $file_temp  = $files['image']['tmp_name'];

        // Xử lý lỗi nhập thiếu
        if (empty($name_km) || $discout <= 0) {
            echo "<script>alert('Vui lòng nhập đầy đủ tên khuyến mãi và số phần trăm giảm giá lớn hơn 0.'); window.history.back();</script>";
            exit();
        }
        // Kiểm tra không vượt quá 100%
        if ($discout > 100) {
            echo "<script>alert('Phần trăm giảm giá không được vượt quá 100%.'); window.history.back();</script>";
            exit();
        }

        $unique_image = '';
        if (!empty($file_name)) {
            $div      = explode('.', $file_name);
            $file_ext = strtolower(end($div));
            if (!in_array($file_ext, $permited)) {
                echo "<script>alert('Chỉ chấp nhận file: " . implode(', ', $permited) . ".'); window.history.back();</script>";
                exit();
            }
            $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
            move_uploaded_file($file_temp, "../images/food/" . $unique_image);
        }

        $query = "INSERT INTO khuyenmai(name_km, time_star, time_end, discout, ghichu, images, xoa)
                VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmt = $this->db->link->prepare($query);
        $stmt->bind_param("sssdds", $name_km, $time_star, $time_end, $discout, $ghichu, $unique_image);

        if ($stmt->execute()) {
            echo "<script>alert('Thêm khuyến mãi thành công!'); window.location.href='../admin/kmlist.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi thêm khuyến mãi.'); window.history.back();</script>";
        }
        $stmt->close();
        exit();
    }



    public function show_km()
    {
        $query = "SELECT * FROM khuyenmai WHERE xoa = 0 ORDER BY id_km DESC";
        return $this->db->select($query);
    }

    public function show_km_deleted()
    {
        $query = "SELECT * FROM khuyenmai WHERE xoa = 1 ORDER BY id_km DESC";
        return $this->db->select($query);
    }

    public function restore_km($id)
    {
        $query = "UPDATE khuyenmai SET xoa = 0 WHERE id_km = ?";
        $stmt = $this->db->link->prepare($query);
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            echo "<script>alert('Khôi phục khuyến mãi thành công!'); window.location.href='../admin/kmlist.php';</script>";
        } else {
            echo "<script>alert('Khôi phục khuyến mãi thất bại!'); window.location.href='../admin/kmlist.php';</script>";
        }
        exit();
    }

    // public function show_km_active() {
    //     // CURDATE() lấy ngày hiện tại của MySQL (ví dụ: '2025-10-07')
    //     $query = "SELECT images, discout, time_star, time_end 
    //               FROM khuyenmai 
    //               WHERE images IS NOT NULL 
    //               AND images != '' 
    //               AND xoa = 0 
    //               AND time_end >= CURDATE()  /* ĐIỀU KIỆN MỚI: Ngày kết thúc PHẢI lớn hơn hoặc bằng ngày hiện tại */
    //               ORDER BY id_km DESC";
    //     return $this->db->select($query);
    // }

    public function update_km($data, $files, $id)
    {
        // Lấy dữ liệu cũ từ CSDL
        $old = $this->db->select("SELECT time_star, time_end FROM khuyenmai WHERE id_km = {$id}")->fetch_assoc();

        // Validate name và ghi chú
        $name_km = $this->fm->validation($data['name_km']);
        $ghichu  = $this->fm->validation($data['ghichu']);
        $discout = floatval($data['discout']);

        // Kiểm tra discout hợp lệ: >0 và <=100
        if ($discout <= 0 || $discout > 100) {
            echo "<script>
                    alert('Phần trăm giảm giá phải lớn hơn 0 và không được vượt quá 100%.');
                    window.history.back();
                </script>";
            exit();
        }

        // Xử lý time_star: nếu input rỗng hoặc không đúng định dạng, giữ nguyên
        $raw_start = trim($data['time_star'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $raw_start)) {
            if (strlen($raw_start) === 10) $raw_start .= ' 00:00:00';
            $time_star = date('Y-m-d H:i:s', strtotime($raw_start));
        } else {
            $time_star = $old['time_star'];
        }

        // Xử lý time_end tương tự
        $raw_end = trim($data['time_end'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $raw_end)) {
            if (strlen($raw_end) === 10) $raw_end .= ' 23:59:59';
            $time_end = date('Y-m-d H:i:s', strtotime($raw_end));
        } else {
            $time_end = $old['time_end'];
        }

        // Xử lý ảnh mới nếu có
        $unique_image = '';
        if (!empty($files['image']['name'])) {
            $ext = strtolower(pathinfo($files['image']['name'], PATHINFO_EXTENSION));
            $unique_image = substr(md5(time()), 0, 10) . '.' . $ext;
            move_uploaded_file($files['image']['tmp_name'], "../images/food/{$unique_image}");
        }

        // Chuẩn bị và thực thi UPDATE
        if ($unique_image) {
            $sql = "UPDATE khuyenmai 
                    SET name_km=?, time_star=?, time_end=?, discout=?, ghichu=?, images=? 
                    WHERE id_km=?";
            $stmt = $this->db->link->prepare($sql);
            $stmt->bind_param(
                "sssdssi",
                $name_km,
                $time_star,
                $time_end,
                $discout,
                $ghichu,
                $unique_image,
                $id
            );
        } else {
            $sql = "UPDATE khuyenmai 
                    SET name_km=?, time_star=?, time_end=?, discout=?, ghichu=? 
                    WHERE id_km=?";
            $stmt = $this->db->link->prepare($sql);
            $stmt->bind_param(
                "sssdsi",
                $name_km,
                $time_star,
                $time_end,
                $discout,
                $ghichu,
                $id
            );
        }

        if ($stmt->execute()) {
            echo "<script>
                    alert('Cập nhật thành công!');
                    window.location.href='../admin/kmlist.php';
                </script>";
        } else {
            echo "<script>
                    alert('Cập nhật thất bại: {$stmt->error}');
                    window.location.href='../admin/kmlist.php';
                </script>";
        }
        $stmt->close();
        exit();
    }



    public function del_km($id)
    {
        $query = "UPDATE khuyenmai SET xoa = 1 WHERE id_km = ?";
        $stmt = $this->db->link->prepare($query);
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            echo "<script>alert('Xóa khuyến mãi thành công!'); window.location.href='../admin/kmlist.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi xóa khuyến mãi.'); window.location.href='../admin/kmlist.php';</script>";
        }
        exit();
    }

    public function getkmbyid($id)
    {
        $query = "SELECT * FROM khuyenmai WHERE id_km = ?";
        $stmt = $this->db->link->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function show_km_active()
    {
        // THÊM id_km vào SELECT
        $query = "SELECT id_km, images, discout, time_star, time_end 
                FROM khuyenmai 
                WHERE images IS NOT NULL 
                AND images != '' 
                AND xoa = 0 
                AND time_end >= CURDATE()
                ORDER BY id_km DESC";
        return $this->db->select($query);
    }
    public function get_km_detail($id)
    {
        $query = "SELECT * FROM khuyenmai WHERE id_km = ? AND xoa = 0";
        $stmt = $this->db->link->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
}
