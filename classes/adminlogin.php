<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/session.php');
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

class adminlogin
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    public function login_admin($adminuser, $adminpass)
    {
        $adminuser = $this->fm->validation($adminuser);
        $adminpass = $this->fm->validation($adminpass);

        $adminuser = mysqli_real_escape_string($this->db->link, $adminuser);
        $adminpass = mysqli_real_escape_string($this->db->link, $adminpass);

        if (empty($adminuser) || empty($adminpass)) {
            $alert = "User and password cannot be empty.";
            return $alert;
        } else {
            $query  = "SELECT * FROM tb_admin WHERE adminuser='$adminuser' AND adminpass='$adminpass' LIMIT 1";
            $result = $this->db->select($query);

            if ($result != false) {
                $value = $result->fetch_assoc();

                // Lưu thông tin vào session, phân quyền
                Session::set('adminlogin', true);
                Session::set('idadmin',   $value['id_admin']);
                Session::set('adminuser',  $value['adminuser']);
                Session::set('adminname',  $value['Name_admin']);
                Session::set('adminlevel', $value['level']);

                // Hiện alert rồi chuyển hướng
                echo "<script>
                        alert('Đăng nhập thành công!');
                        window.location.href = 'index.php';
                    </script>";
                exit();
            } else {
                $alert = "User and password do not match.";
                return $alert;
            }
        }
    }


    public function change_password($id, $oldPass, $newPass, $confirmPass)
    {
        $oldPassHash = md5($oldPass);
        $newPassHash = md5($newPass);
        $confirmPassHash = md5($confirmPass);

        $queryCheck = "SELECT * FROM tb_admin WHERE id_admin = '$id' AND adminpass = '$oldPassHash'";
        $resultCheck = $this->db->select($queryCheck);

        if ($resultCheck) {
            if ($newPass === '') {
                return '<script>alert("Mật khẩu mới không được để trống!");</script>';
            }
            if ($newPassHash === $confirmPassHash) {
                $queryUpdate = "UPDATE tb_admin SET adminpass = '$newPassHash' WHERE id_admin = '$id'";
                $updateResult = $this->db->update($queryUpdate);

                if ($updateResult) {
                    return '<script>
                        alert("Đổi mật khẩu thành công! Bạn sẽ được chuyển về trang chính.");
                        window.location.href = "index.php";
                    </script>';
                } else {
                    return '<script>alert("Đổi mật khẩu thất bại!");</script>';
                }
            } else {
                return '<script>alert("Mật khẩu xác nhận không khớp!");</script>';
            }
        } else {
            return '<script>alert("Mật khẩu cũ không đúng!");</script>';
        }
    }
}
