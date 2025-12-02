    <?php
    $filepath = realpath(dirname(__FILE__));
    include_once($filepath . '/../lib/database.php');
    include_once($filepath . '/../helpers/format.php');

    class mon
    {
        private $db;
        private $fm;

        public function __construct()
        {
            $this->db = new Database();
            $this->fm = new Format();
        }

        public function insert_mon($data, $files)
        {
            $name_mon = mysqli_real_escape_string($this->db->link, $data['name_mon']);
            $loaimon = mysqli_real_escape_string($this->db->link, $data['loaimon']);
            $gia = mysqli_real_escape_string($this->db->link, $data['gia']);
            $ghichu = mysqli_real_escape_string($this->db->link, $data['ghichu']);
            $tinhtrang = mysqli_real_escape_string($this->db->link, $data['tinhtrang']);

            $file_name = $_FILES['image']['name'];
            $file_temp = $_FILES['image']['tmp_name'];

            $div = explode('.', $file_name);
            $file_ext = strtolower(end($div));
            $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
            $uploaded_image = "../images/food/" . $unique_image;

            if ($name_mon == "" || $loaimon == "" || $gia == "" || $tinhtrang == "" || $file_name == "") {
                echo "<script>alert('Vui lòng nhập đầy đủ thông tin.'); window.location.href='http://restaurant_v2.test/admin/productlist.php';</script>";
                exit();
            } else {
                move_uploaded_file($file_temp, $uploaded_image);
                $query = "INSERT INTO monan(name_mon, id_loai, gia_mon, ghichu_mon, images, tinhtrang, xoa) 
                        VALUES('$name_mon', '$loaimon', '$gia', '$ghichu', '$unique_image', '$tinhtrang', 0)";
                $result = $this->db->insert($query);
                $msg = $result ? 'Thêm món thành công!' : 'Thêm món thất bại!';
                echo "<script>alert('$msg'); window.location.href='http://restaurant_v2.test/admin/productlist.php';</script>";
                exit();
            }
        }

        public function update_mon($data, $files, $id)
        {
            $tinhtrang = mysqli_real_escape_string($this->db->link, $data['tinhtrang']);
            $gia = mysqli_real_escape_string($this->db->link, $data['gia']);
            $ghichu = mysqli_real_escape_string($this->db->link, $data['ghichu']);

            $permited = array('jpg', 'jpeg', 'png', 'gif');
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];
            $file_temp = $_FILES['image']['tmp_name'];

            $div = explode('.', $file_name);
            $file_ext = strtolower(end($div));
            $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
            $uploaded_image = "../images/food/" . $unique_image;

            if (!empty($file_name)) {
                if ($file_size > 2000000) {
                    echo "<script>alert('Ảnh quá lớn, tối đa 2MB.'); window.location.href='../admin/productlist.php';</script>";
                    exit();
                }
                if (!in_array($file_ext, $permited)) {
                    echo "<script>alert('Chỉ cho phép định dạng ảnh: " . implode(', ', $permited) . "'); window.location.href='../admin/productlist.php';</script>";
                    exit();
                }
                move_uploaded_file($file_temp, $uploaded_image);
                $query = "UPDATE monan SET gia_mon='$gia', ghichu_mon='$ghichu', images='$unique_image', tinhtrang='$tinhtrang' WHERE id_mon='$id'";
            } else {
                $query = "UPDATE monan SET gia_mon='$gia', ghichu_mon='$ghichu', tinhtrang='$tinhtrang' WHERE id_mon='$id'";
            }

            $result = $this->db->update($query);
            $msg = $result ? 'Cập nhật món thành công!' : 'Cập nhật thất bại!';
            echo "<script>alert('$msg'); window.location.href='../admin/productlist.php';</script>";
            exit();
        }

        public function del_mon($id)
        {
            $query = "UPDATE monan SET xoa = 1 WHERE id_mon='$id'";
            $result = $this->db->update($query);
            $msg = $result ? 'Đã ẩn món ăn thành công!' : 'Ẩn món ăn thất bại!';
            echo "<script>alert('$msg'); window.location.href='../admin/productlist.php';</script>";
            exit();
        }

        public function restore_mon($id)
        {
            $query = "UPDATE monan SET xoa = 0 WHERE id_mon='$id'";
            $result = $this->db->update($query);
            $msg = $result ? 'Khôi phục món ăn thành công!' : 'Khôi phục món ăn thất bại!';
            echo "<script>alert('$msg'); window.location.href='../admin/productlist.php';</script>";
            exit();
        }

        public function show_mon()
        {
            $query = "SELECT monan.*, loai_mon.name_loai 
                    FROM monan 
                    INNER JOIN loai_mon ON monan.id_loai = loai_mon.id_loai 
                    WHERE monan.xoa = 0
                    ORDER BY monan.id_mon DESC";
            return $this->db->select($query);
        }

        public function show_mon_deleted()
        {
            $query = "SELECT monan.*, loai_mon.name_loai 
                    FROM monan 
                    INNER JOIN loai_mon ON monan.id_loai = loai_mon.id_loai 
                    WHERE monan.xoa = 1
                    ORDER BY monan.id_mon DESC";
            return $this->db->select($query);
        }

        public function getmonbyid($id)
        {
            $query = "SELECT * FROM monan WHERE id_mon='$id'";
            return $this->db->select($query);
        }

        public function getmonbyall()
        {
            $query = "
                SELECT m.*, l.name_loai
                FROM monan AS m
                INNER JOIN loai_mon AS l ON m.id_loai = l.id_loai
                WHERE m.xoa = 0 AND l.xoa = 0
            ";
            return $this->db->select($query);
        }

        public function getmonbyloai($id)
        {
            $query = "SELECT * FROM monan WHERE id_loai='$id' AND tinhtrang=1 AND xoa = 0";
            return $this->db->select($query);
        }

        public function getmonkey($key)
        {
            $k = $this->db->link->real_escape_string(trim($key));
            $query = "SELECT * FROM monan 
                    WHERE name_mon LIKE '%{$k}%' AND tinhtrang=1 AND xoa = 0
                    ORDER BY id_mon DESC";
            return $this->db->select($query);
        }

        public function get_detail($id)
        {
            $query = "SELECT monan.*, loai_mon.name_loai 
                    FROM monan 
                    INNER JOIN loai_mon ON monan.id_loai = loai_mon.id_loai  
                    WHERE monan.id_mon = '$id'";
            return $this->db->select($query);
        }

        public function getMonByIds($ids)
        {
            if (empty($ids) || !is_array($ids)) {
                return false;
            }
            $id_list = implode(',', array_map('intval', $ids));
            $query = "SELECT * FROM monan WHERE id_mon IN ($id_list) AND xoa = 0";
            return $this->db->select($query);
        }

        /* ================== PHÂN TRANG ================== */
        public function dem_all()
        {
            $sql = "SELECT COUNT(*) AS total
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0";
            $rs = $this->db->select($sql);
            return $rs ? (int)$rs->fetch_assoc()['total'] : 0;
        }

        public function dem_loai($id_loai)
        {
            $id_loai = (int)$id_loai;
            $sql = "SELECT COUNT(*) AS total
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0 AND m.id_loai = {$id_loai}";
            $rs = $this->db->select($sql);
            return $rs ? (int)$rs->fetch_assoc()['total'] : 0;
        }

        public function dem_key($key)
        {
            $k = $this->db->link->real_escape_string(trim($key));
            $sql = "SELECT COUNT(*) AS total
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0
                    AND m.name_mon LIKE '%{$k}%'";
            $rs = $this->db->select($sql);
            return $rs ? (int)$rs->fetch_assoc()['total'] : 0;
        }

        // public function get_all_trang($limit, $offset) {
        //     $limit = (int)$limit; $offset = (int)$offset;
        //     $sql = "SELECT m.*, l.name_loai
        //             FROM monan m
        //             INNER JOIN loai_mon l ON m.id_loai = l.id_loai
        //             WHERE m.xoa = 0 AND l.xoa = 0
        //             ORDER BY m.id_mon DESC
        //             LIMIT {$limit} OFFSET {$offset}";
        //     return $this->db->select($sql);
        // }
        public function get_all_trang($limit, $offset)
        {
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql = "SELECT m.*, l.name_loai
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0
                    ORDER BY m.id_loai ASC, m.id_mon DESC  
                    LIMIT {$limit} OFFSET {$offset}";
            return $this->db->select($sql);
        }


        public function get_loai_trang($id_loai, $limit, $offset)
        {
            $id_loai = (int)$id_loai;
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql = "SELECT m.*, l.name_loai
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0 AND m.id_loai = {$id_loai}
                    ORDER BY m.id_mon DESC
                    LIMIT {$limit} OFFSET {$offset}";
            return $this->db->select($sql);
        }

        public function get_key_trang($key, $limit, $offset)
        {
            $k = $this->db->link->real_escape_string(trim($key));
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql = "SELECT m.*, l.name_loai
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0
                    AND m.name_mon LIKE '%{$k}%'
                    ORDER BY m.id_mon DESC
                    LIMIT {$limit} OFFSET {$offset}";
            return $this->db->select($sql);
        }
        public function dem_loai_and_key($id_loai, $key)
        {
            $id_loai = (int)$id_loai;
            $k = $this->db->link->real_escape_string(trim($key));
            $sql = "SELECT COUNT(*) AS total
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0
                    AND m.id_loai = {$id_loai}
                    AND m.name_mon LIKE '%{$k}%'";
            $rs = $this->db->select($sql);
            return $rs ? (int)$rs->fetch_assoc()['total'] : 0;
        }

        public function get_loai_and_key_trang($id_loai, $key, $limit, $offset)
        {
            $id_loai = (int)$id_loai;
            $k = $this->db->link->real_escape_string(trim($key));
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql = "SELECT m.*, l.name_loai
                    FROM monan m
                    INNER JOIN loai_mon l ON m.id_loai = l.id_loai
                    WHERE m.xoa = 0 AND l.xoa = 0
                    AND m.id_loai = {$id_loai}
                    AND m.name_mon LIKE '%{$k}%'
                    ORDER BY m.id_mon DESC
                    LIMIT {$limit} OFFSET {$offset}";
            return $this->db->select($sql);
        }

        public function get_menu_with_monan($ma_menu = null)
        {
            // Lấy các món thuộc combo, chỉ những menu có trang_thai = 0
            $sql = "
        SELECT 
            m.id_menu AS ma_menu,
            mct.id_mon,
            mo.name_mon,
            mo.gia_mon
        FROM menu AS m
        INNER JOIN menu_chitiet AS mct ON mct.ma_menu = m.id_menu
        INNER JOIN monan AS mo        ON mo.id_mon   = mct.id_mon
        WHERE m.trang_thai = 0
    ";

            if (!is_null($ma_menu)) {
                $ma_menu = (int)$ma_menu;
                $sql .= " AND m.id_menu = {$ma_menu}";
            }

            $sql .= " ORDER BY m.id_menu ASC, mct.id ASC";
            return $this->db->select($sql);
        }

        public function get_menu_chitiet_for_edit($id_menu)
        {
            $id = (int)$id_menu;

            $sql = "
        SELECT
            m.id_menu,
            m.ten_menu,
            m.ghi_chu,
            m.trang_thai,

            mct.id         AS ct_id,
            mct.id_mon,
            mct.so_luong,

            mo.name_mon,
            mo.gia_mon
        FROM menu AS m
        LEFT JOIN menu_chitiet AS mct
               ON mct.ma_menu = m.id_menu
        LEFT JOIN monan AS mo
               ON mo.id_mon = mct.id_mon
        WHERE m.id_menu = {$id}
          AND m.trang_thai = 0
        ORDER BY mct.id ASC
    ";

            return $this->db->select($sql);
        }
    }
    ?>
