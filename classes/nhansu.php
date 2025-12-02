<?php
// FILE: classes/nhansu.php (ĐÃ CẬP NHẬT)

// Lấy đường dẫn tuyệt đối đến thư mục chứa file này
$filepath = realpath(dirname(__FILE__));
// Include các file cần thiết từ thư mục cha (..)
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class NhanSu
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database(); // Class kết nối CSDL của bạn
        $this->fm = new Format();   // Class helper của bạn (nếu có)
    }

    /**
     * Thêm nhân sự mới (tài khoản tb_admin + hồ sơ nhansu)
     * @param array $data Dữ liệu từ form POST
     * @return mixed ID admin mới nếu thành công, thông báo lỗi (string) nếu thất bại
     */
    public function themNhanSu($data)
    {
        // Lấy và kiểm tra dữ liệu (ĐÃ BỎ sdt, email)
        $adminUser = $this->fm->validation($data['adminuser'] ?? ''); // Lấy username từ form
        $adminPass = md5('123456'); // Mật khẩu mặc định
        $nameAdmin = $this->fm->validation($data['hoten']);
        $level = isset($data['level']) ? (int)$data['level'] : -1; // -1 nếu không chọn

        $ngaysinh = $this->fm->validation($data['ngaysinh']);
        $gioitinh = $this->fm->validation($data['gioitinh'] ?? null);
        $diachi = $this->fm->validation($data['diachi']);
        $ngayvaolam = $this->fm->validation($data['ngayvaolam']);
        $cccd = $this->fm->validation($data['cccd']);
        $ngaycap_cccd = $this->fm->validation($data['ngaycap_cccd']);
        $noicap_cccd = $this->fm->validation($data['noicap_cccd']);
        $quequan = $this->fm->validation($data['quequan'] ?? null);
        // ... (các trường khác nếu có) ...

        // Xử lý upload ảnh đại diện
        $anh_dai_dien_name = "";
        $uploaded_image_path = ""; // Lưu đường dẫn để xóa nếu rollback
        if (isset($_FILES["anh_dai_dien"]) && $_FILES["anh_dai_dien"]["error"] == 0) {
            $permited = array('jpg', 'jpeg', 'png', 'gif');
            $file_name = $_FILES['anh_dai_dien']['name'];
            $file_size = $_FILES['anh_dai_dien']['size'];
            $file_temp = $_FILES['anh_dai_dien']['tmp_name'];
            $div = explode('.', $file_name);
            $file_ext = strtolower(end($div));
            // Tạo tên file duy nhất
            $anh_dai_dien_name = 'avt_' . substr(md5(time() . rand()), 0, 10) . '.' . $file_ext;
            $uploaded_image_path = "../images/avt/" . $anh_dai_dien_name; // Đường dẫn lưu file

            if ($file_size > 2097152) { // 2MB
                return "<span class='error'>Ảnh không được lớn hơn 2MB!</span>";
            } elseif (in_array($file_ext, $permited) === false) {
                return "<span class='error'>Chỉ có thể upload ảnh: " . implode(', ', $permited) . "</span>";
            }
            // Di chuyển file vào thư mục đích
            if (!move_uploaded_file($file_temp, $uploaded_image_path)) {
                return "<span class='error'>Lỗi khi di chuyển file ảnh upload.</span>";
            }
        }
        // Kết thúc xử lý ảnh

        // Kiểm tra dữ liệu bắt buộc
        if (empty($adminUser) || empty($nameAdmin) || $level < 0 || empty($ngaysinh) || empty($diachi) || empty($ngayvaolam) || empty($cccd) || empty($ngaycap_cccd) || empty($noicap_cccd)) {
            // Xóa file ảnh đã upload nếu có lỗi validation
            if (!empty($uploaded_image_path) && file_exists($uploaded_image_path)) {
                unlink($uploaded_image_path);
            }
            return "<span class='error'>Vui lòng điền đầy đủ thông tin bắt buộc (*). Tên đăng nhập và Chức vụ là bắt buộc.</span>";
        }

        // Bắt đầu transaction
        $this->db->link->begin_transaction();

        try {
            // Kiểm tra username (adminuser) đã tồn tại chưa
            $checkUserQuery = "SELECT id_admin FROM tb_admin WHERE adminuser = '$adminUser'";
            $userExists = $this->db->select($checkUserQuery);
            if ($userExists) {
                throw new Exception("<span class='error'>Tên đăng nhập '$adminUser' đã tồn tại.</span>");
            }

            // 1. Thêm vào tb_admin (ĐÃ BỎ email, sdt)
            $queryAdmin = "INSERT INTO tb_admin (adminuser, adminpass, Name_admin, level)
                           VALUES ('$adminUser', '$adminPass', '$nameAdmin', $level)";
            $resultAdmin = $this->db->insert($queryAdmin);
            if (!$resultAdmin) {
                throw new Exception("Thêm tài khoản thất bại.");
            }
            $id_admin_moi = $this->db->link->insert_id; // Lấy ID admin vừa tạo

            // 2. Thêm vào nhansu
            $queryNhanSu = "INSERT INTO nhansu (
                                id_admin, hoten, ngaysinh, gioitinh, diachi, ngayvaolam, trangthai,
                                cccd, ngaycap_cccd, noicap_cccd, quequan, anh_dai_dien
                            ) VALUES (
                                $id_admin_moi, '$nameAdmin', '$ngaysinh', '$gioitinh', '$diachi', '$ngayvaolam', 1, /* Mặc định là đang làm */
                                '$cccd', '$ngaycap_cccd', '$noicap_cccd', '$quequan', '$anh_dai_dien_name'
                            )";
            $resultNhanSu = $this->db->insert($queryNhanSu);
            if (!$resultNhanSu) {
                throw new Exception("Thêm hồ sơ nhân sự thất bại.");
            }

            // Nếu mọi thứ thành công
            $this->db->link->commit();
            return $id_admin_moi; // Trả về ID admin mới

        } catch (Exception $e) {
            $this->db->link->rollback(); // Hoàn tác nếu có lỗi
            // Xóa file ảnh đã upload nếu transaction thất bại
            if (!empty($uploaded_image_path) && file_exists($uploaded_image_path)) {
                unlink($uploaded_image_path);
            }
            // Trả về thông báo lỗi dạng string
            return "<span class='error'>" . $e->getMessage() . " SQL Error: " . $this->db->link->error . "</span>";
        }
    }

    /**
     * Lấy danh sách nhân sự theo trạng thái (1: đang làm, 0: đã nghỉ/ẩn)
     * @param int $trangthai 1 hoặc 0
     * @return mixed Kết quả query hoặc false
     */
    public function layDanhSachNhanSu($trangthai)
    {
        $trangthai = ($trangthai == 0) ? 0 : 1; // Đảm bảo chỉ là 0 hoặc 1
        $query = "SELECT ns.*, tk.adminuser, tk.level, tk.Name_admin /* Lấy thêm Name_admin */
                  FROM nhansu ns
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE ns.trangthai = '$trangthai'
                  ORDER BY ns.mans ASC";
        $result = $this->db->select($query);
        return $result;
    }

    /**
     * Lấy thông tin chi tiết của 1 nhân sự bằng mans
     * @param int $mans Mã nhân sự
     * @return mixed Mảng thông tin hoặc false
     */
    public function layThongTinNhanSu($mans)
    {
        $mans = (int)$mans;
        $query = "SELECT ns.*, tk.adminuser, tk.level, tk.anh_face, tk.Name_admin
                  FROM nhansu ns
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE ns.mans = '$mans'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    /**
     * Lấy thông tin chi tiết của 1 nhân sự bằng id_admin
     * @param int $id_admin ID admin
     * @return mixed Mảng thông tin hoặc false
     */
    public function layThongTinNhanSuTheoIdAdmin($id_admin)
    {
        $id_admin = (int)$id_admin;
        $query = "SELECT ns.*, tk.adminuser, tk.level, tk.anh_face, tk.Name_admin
                  FROM nhansu ns
                  JOIN tb_admin tk ON ns.id_admin = tk.id_admin
                  WHERE tk.id_admin = '$id_admin'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : false;
    }

    /**
     * Cập nhật thông tin hồ sơ nhân sự (bảng nhansu) VÀ Name_admin (bảng tb_admin)
     * @param int $mans Mã nhân sự
     * @param array $data Dữ liệu cần cập nhật từ form
     * @return string Thông báo thành công/lỗi
     */
    public function capNhatHoSoNhanSu($mans, $data)
    {
        $mans = (int)$mans;
        // Lấy thông tin hiện tại để lấy id_admin và ảnh cũ
        $info = $this->layThongTinNhanSu($mans);
        if (!$info) return "<span class='error'>Không tìm thấy nhân sự để cập nhật.</span>";
        $id_admin = $info['id_admin'];
        $anh_cu = $info['anh_dai_dien'];

        // Lấy và kiểm tra dữ liệu
        $hoten = $this->fm->validation($data['hoten']);
        $ngaysinh = $this->fm->validation($data['ngaysinh']);
        $gioitinh = $this->fm->validation($data['gioitinh'] ?? null);
        $diachi = $this->fm->validation($data['diachi']);
        $ngayvaolam = $this->fm->validation($data['ngayvaolam']);
        $cccd = $this->fm->validation($data['cccd']);
        $ngaycap_cccd = $this->fm->validation($data['ngaycap_cccd']);
        $noicap_cccd = $this->fm->validation($data['noicap_cccd']);
        $quequan = $this->fm->validation($data['quequan'] ?? null);
        // ... các trường khác ...

        // Xử lý upload ảnh mới nếu có
        $anh_dai_dien_sql = ""; // Chuỗi SQL để cập nhật ảnh
        $anh_moi = ""; // Tên file ảnh mới
        $path_anh_moi = ""; // Đường dẫn file ảnh mới
        if (isset($_FILES["anh_dai_dien"]) && !empty($_FILES["anh_dai_dien"]["name"]) && $_FILES["anh_dai_dien"]["error"] == 0) {
            $permited = array('jpg', 'jpeg', 'png', 'gif');
            $file_name = $_FILES['anh_dai_dien']['name'];
            $file_size = $_FILES['anh_dai_dien']['size'];
            $file_temp = $_FILES['anh_dai_dien']['tmp_name'];
        }

        // Kiểm tra dữ liệu bắt buộc
        if (empty($hoten) || empty($ngaysinh) || empty($diachi) || empty($ngayvaolam) || empty($cccd) || empty($ngaycap_cccd) || empty($noicap_cccd)) {
            // Xóa ảnh mới đã upload nếu có lỗi validation
            if (!empty($path_anh_moi) && file_exists($path_anh_moi)) unlink($path_anh_moi);
            return "<span class='error'>Vui lòng điền đầy đủ thông tin bắt buộc.</span>";
        }

        // Bắt đầu transaction
        $this->db->link->begin_transaction();
        try {
            // 1. Cập nhật bảng nhansu
            $queryNS = "UPDATE nhansu SET
                        hoten = '$hoten', ngaysinh = '$ngaysinh', gioitinh = '$gioitinh',
                        diachi = '$diachi', ngayvaolam = '$ngayvaolam', cccd = '$cccd',
                        ngaycap_cccd = '$ngaycap_cccd', noicap_cccd = '$noicap_cccd', quequan = '$quequan'
                        {$anh_dai_dien_sql} /* Thêm phần cập nhật ảnh nếu có */
                      WHERE mans = '$mans'";
            if (!$this->db->update($queryNS)) throw new Exception("Lỗi cập nhật hồ sơ nhân sự.");

            // 2. Cập nhật Name_admin trong tb_admin để đồng bộ
            $queryAdmin = "UPDATE tb_admin SET Name_admin = '$hoten' WHERE id_admin = '$id_admin'";
            if (!$this->db->update($queryAdmin)) throw new Exception("Lỗi cập nhật tên tài khoản.");

            // Xóa ảnh cũ nếu upload ảnh mới thành công
            if (!empty($anh_moi) && !empty($anh_cu) && file_exists("../images/avt/" . $anh_cu)) {
                unlink("../images/avt/" . $anh_cu);
            }

            // Cập nhật session nếu tự sửa
            if (isset($_SESSION['adminId']) && $_SESSION['adminId'] == $id_admin) {
                $_SESSION['adminName'] = $hoten;
            }

            $this->db->link->commit();
            return "<span class='success'>Cập nhật hồ sơ thành công.</span>";
        } catch (Exception $e) {
            $this->db->link->rollback();
            // Xóa ảnh mới đã upload nếu transaction thất bại
            if (!empty($path_anh_moi) && file_exists($path_anh_moi)) unlink($path_anh_moi);
            return "<span class='error'>" . $e->getMessage() . " SQL Error: " . $this->db->link->error . "</span>";
        }
    }

    /**
     * Chỉ cập nhật chức vụ (level) trong tb_admin
     * @param int $id_admin ID admin
     * @param int $level Chức vụ mới
     * @return string Thông báo thành công/lỗi
     */
    public function capNhatChucVuAdmin($id_admin, $level)
    {
        $id_admin = (int)$id_admin;
        $level = (int)$level;

        if ($level < 0) {
            return "<span class='error'>Vui lòng chọn chức vụ hợp lệ.</span>";
        }

        $query = "UPDATE tb_admin SET level = '$level' WHERE id_admin = '$id_admin'";
        $result = $this->db->update($query);
        if ($result) {
            if (isset($_SESSION['adminId']) && $_SESSION['adminId'] == $id_admin) {
                $_SESSION['adminlevel'] = $level;
            }
            return "<span class='success'>Cập nhật chức vụ thành công.</span>";
        } else {
            return "<span class='error'>Cập nhật chức vụ thất bại. SQL Error: " . $this->db->link->error . "</span>";
        }
    }

    /**
     * Ẩn hoặc Hiện nhân sự (cập nhật cột trangthai trong bảng nhansu)
     * @param int $mans Mã nhân sự
     * @param int $trangthai_moi 0 = Ẩn (Nghỉ), 1 = Hiện (Làm lại)
     * @return string Thông báo
     */
    public function anHienNhanSu($mans, $trangthai_moi)
    {
        $mans = (int)$mans;
        $trangthai_moi = ($trangthai_moi == 0) ? 0 : 1; // Chỉ nhận 0 hoặc 1

        $query = "UPDATE nhansu SET trangthai = '$trangthai_moi' WHERE mans = '$mans'";
        $result = $this->db->update($query);
        if ($result) {
            $statusText = ($trangthai_moi == 0) ? "ẩn (cho nghỉ)" : "hiện (cho làm lại)";
            return "<span class='success'>Đã $statusText nhân sự thành công.</span>";
        } else {
            return "<span class='error'>Cập nhật trạng thái thất bại.</span>";
        }
    }

    /**
     * Xóa VĨNH VIỄN nhân sự (bao gồm tài khoản tb_admin và hồ sơ nhansu)
     * !!! CẨN THẬN KHI SỬ DỤNG HÀM NÀY !!!
     * @param int $mans Mã nhân sự
     * @return string Thông báo
     */
    public function xoaVinhVienNhanSu($mans)
    {
        $mans = (int)$mans;

        // Lấy thông tin cần thiết trước khi xóa
        $info = $this->layThongTinNhanSu($mans);
        if (!$info) {
            return "<span class='error'>Không tìm thấy nhân sự (Mã: $mans) để xóa vĩnh viễn.</span>";
        }
        $id_admin = $info['id_admin'];
        $anh_dai_dien = $info['anh_dai_dien'];

        // Bắt đầu transaction
        $this->db->link->begin_transaction();
        try {
            // 1. Xóa hồ sơ nhân sự
            // Do có khóa ngoại ON DELETE CASCADE, các bản ghi liên quan trong
            // luong, lichlamviec, nghiphep sẽ tự động bị xóa theo.
            $queryNS = "DELETE FROM nhansu WHERE mans = '$mans'";
            $deleteNS = $this->db->delete($queryNS);
            if (!$deleteNS) {
                // Kiểm tra xem có phải do khóa ngoại không (thường thì không vì đã set ON DELETE CASCADE)
                if ($this->db->link->errno == 1451) { // Lỗi khóa ngoại
                    throw new Exception("Không thể xóa hồ sơ nhân sự do còn dữ liệu liên quan (lỗi khóa ngoại).");
                }
                throw new Exception("Lỗi khi xóa hồ sơ nhân sự.");
            }

            // 2. Xóa tài khoản admin
            // Cần đảm bảo không còn ràng buộc nào khác trỏ đến id_admin này
            $queryAdmin = "DELETE FROM tb_admin WHERE id_admin = '$id_admin'";
            $deleteAdmin = $this->db->delete($queryAdmin);
            if (!$deleteAdmin) {
                if ($this->db->link->errno == 1451) {
                    throw new Exception("Không thể xóa tài khoản admin do còn dữ liệu liên quan (vd: người duyệt nghỉ phép?).");
                }
                throw new Exception("Lỗi khi xóa tài khoản admin.");
            }

            // 3. Xóa ảnh đại diện khỏi server
            if (!empty($anh_dai_dien) && file_exists("../images/avt/" . $anh_dai_dien)) {
                if (!unlink("../images/avt/" . $anh_dai_dien)) {
                    // Ghi log lỗi xóa file nhưng vẫn commit transaction
                    error_log("Không thể xóa file ảnh: ../images/avt/" . $anh_dai_dien);
                }
            }
            // 4. (QUAN TRỌNG) Gọi API Python để xóa dữ liệu khuôn mặt nếu có
            // Bạn cần thêm code gọi API xóa ở đây, truyền $id_admin
            // Ví dụ: $result_api = call_api_delete_face($id_admin);
            // if (!$result_api['success']) { error_log("Lỗi xóa khuôn mặt API cho id_admin: $id_admin"); }

            // Nếu mọi thứ thành công
            $this->db->link->commit();
            return "<span class='success'>Đã xóa vĩnh viễn nhân sự (Mã NS: $mans, ID Admin: $id_admin) thành công.</span>";
        } catch (Exception $e) {
            $this->db->link->rollback(); // Hoàn tác tất cả nếu có lỗi
            return "<span class='error'>Xóa vĩnh viễn nhân sự thất bại: " . $e->getMessage() . "</span>";
        }
    }

    /**
     * Lấy danh sách Vai trò/Level (Hardcoded)
     * @return array
     */
    public function layDanhSachVaiTro()
    {
        // Trả về mảng PHP cố định vì không còn bảng role
        return [
            ['id_role' => 0, 'ten_role' => 'Admin'],
            ['id_role' => 1, 'ten_role' => 'Kế toán'],
            ['id_role' => 2, 'ten_role' => 'Nhân viên quầy'],
            ['id_role' => 3, 'ten_role' => 'Nhân viên bếp'],
            ['id_role' => 4, 'ten_role' => 'Nhân viên chạy bàn']
            // Thêm các level khác nếu có
        ];
    }

    /**
     * Lấy tên vai trò từ Level
     * @param int $level
     * @return string Tên vai trò
     */
    public function layTenVaiTro($level)
    {
        $roles = $this->layDanhSachVaiTro();
        foreach ($roles as $role) {
            if (isset($role['id_role']) && $role['id_role'] == $level) {
                return $role['ten_role'] ?? 'Lỗi tên vai trò';
            }
        }
        return 'Không xác định (' . $level . ')'; // Trả về cả số level nếu không tìm thấy
    }

   public function getNhanSuByAdminId($id_admin)
    {
        $id_admin = (int)$id_admin;
        $query = "SELECT * FROM nhansu WHERE id_admin = '$id_admin' LIMIT 1";
        $result = $this->db->select($query);
        return $result;
    }

    public function updateNhanSu($id_admin, $data, $files)
    {
        $id_admin = mysqli_real_escape_string($this->db->link, $id_admin);
        
        $hoten = mysqli_real_escape_string($this->db->link, $data['hoten']);
        $ngaysinh = !empty($data['ngaysinh']) ? "'".mysqli_real_escape_string($this->db->link, $data['ngaysinh'])."'" : "NULL";
        $gioitinh = mysqli_real_escape_string($this->db->link, $data['gioitinh']);
        $diachi = mysqli_real_escape_string($this->db->link, $data['diachi']);
        $quequan = mysqli_real_escape_string($this->db->link, $data['quequan']);
        $dantoc = mysqli_real_escape_string($this->db->link, $data['dantoc']);
        $quoctich = mysqli_real_escape_string($this->db->link, $data['quoctich']);
        $noisinh = mysqli_real_escape_string($this->db->link, $data['noisinh']);
        $cccd = mysqli_real_escape_string($this->db->link, $data['cccd']);
        $ngaycap_cccd = !empty($data['ngaycap_cccd']) ? "'".mysqli_real_escape_string($this->db->link, $data['ngaycap_cccd'])."'" : "NULL";
        $noicap_cccd = mysqli_real_escape_string($this->db->link, $data['noicap_cccd']);
        
        $hoten_cha = mysqli_real_escape_string($this->db->link, $data['hoten_cha']);
        $namsinh_cha = !empty($data['namsinh_cha']) ? (int)$data['namsinh_cha'] : "NULL";
        $nghenghiep_cha = mysqli_real_escape_string($this->db->link, $data['nghenghiep_cha']);
        $sdt_cha = mysqli_real_escape_string($this->db->link, $data['sdt_cha']);
        
        $hoten_me = mysqli_real_escape_string($this->db->link, $data['hoten_me']);
        $namsinh_me = !empty($data['namsinh_me']) ? (int)$data['namsinh_me'] : "NULL";
        $nghenghiep_me = mysqli_real_escape_string($this->db->link, $data['nghenghiep_me']);
        $sdt_me = mysqli_real_escape_string($this->db->link, $data['sdt_me']);
        $thongtin_them = mysqli_real_escape_string($this->db->link, $data['thongtin_them']);

        $anh_sql = ""; $anh_ins_k = ""; $anh_ins_v = "";
        if (!empty($files['anh_dai_dien']['name'])) {
            $div = explode('.', $files['anh_dai_dien']['name']);
            $file_ext = strtolower(end($div));
            $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
            $uploaded_image = "../images/avt/" . $unique_image;
            move_uploaded_file($files['anh_dai_dien']['tmp_name'], $uploaded_image);
            $anh_sql = ", anh_dai_dien = '$unique_image'";
            $anh_ins_k = ", anh_dai_dien";
            $anh_ins_v = ", '$unique_image'";
        }

        $check = $this->db->select("SELECT id_admin FROM nhansu WHERE id_admin = '$id_admin'");
        if ($check) {
            $query = "UPDATE nhansu SET 
                hoten='$hoten', ngaysinh=$ngaysinh, gioitinh='$gioitinh', diachi='$diachi', 
                quequan='$quequan', dantoc='$dantoc', quoctich='$quoctich', noisinh='$noisinh',
                cccd='$cccd', ngaycap_cccd=$ngaycap_cccd, noicap_cccd='$noicap_cccd',
                hoten_cha='$hoten_cha', namsinh_cha=$namsinh_cha, nghenghiep_cha='$nghenghiep_cha', sdt_cha='$sdt_cha',
                hoten_me='$hoten_me', namsinh_me=$namsinh_me, nghenghiep_me='$nghenghiep_me', sdt_me='$sdt_me',
                thongtin_them='$thongtin_them' $anh_sql
                WHERE id_admin='$id_admin'";
            $result = $this->db->update($query);
        } else {
            $query = "INSERT INTO nhansu (id_admin, hoten, ngaysinh, gioitinh, diachi, quequan, dantoc, quoctich, noisinh, cccd, ngaycap_cccd, noicap_cccd, hoten_cha, namsinh_cha, nghenghiep_cha, sdt_cha, hoten_me, namsinh_me, nghenghiep_me, sdt_me, thongtin_them, trangthai $anh_ins_k) 
            VALUES ('$id_admin', '$hoten', $ngaysinh, '$gioitinh', '$diachi', '$quequan', '$dantoc', '$quoctich', '$noisinh', '$cccd', $ngaycap_cccd, '$noicap_cccd', '$hoten_cha', $namsinh_cha, '$nghenghiep_cha', '$sdt_cha', '$hoten_me', $namsinh_me, '$nghenghiep_me', '$sdt_me', '$thongtin_them', 1 $anh_ins_v)";
            $result = $this->db->insert($query);
        }

        if ($result) {
            // Đồng bộ tên qua bảng tb_admin
            $this->db->update("UPDATE tb_admin SET Name_admin = '$hoten' WHERE id_admin = '$id_admin'");
            if (isset($_SESSION['adminName'])) $_SESSION['adminName'] = $hoten;
            
            // TRẢ VỀ CHỮ THƯỜNG (để file profile.php kiểm tra), KHÔNG CẦN THẺ SPAN NỮA
            return "Cập nhật thành công"; 
        } else {
            return "Lỗi: " . $this->db->link->error;
        }
    }
}
