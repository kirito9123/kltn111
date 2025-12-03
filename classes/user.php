<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../lib/database.php');
include_once($filepath . '/../helpers/format.php');

/**
 * PHPMailer autoload (dùng Composer)
 * Nếu bạn chưa có vendor, chạy: composer require phpmailer/phpmailer
 * Hoặc tự include các file PHPMailer thủ công (ghi chú phía dưới).
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class user
{
    private $db;
    private $fm;

    public function __construct()
    {
        $this->db = new Database();
        $this->fm = new Format();
    }

    /* ====================== AUTH (giữ nguyên) ====================== */

    public function login_user($sdt, $pass)
    {
        $sdt  = $this->fm->validation($sdt);
        $pass = $this->fm->validation($pass);
        $sdt  = mysqli_real_escape_string($this->db->link, $sdt);
        $pass = mysqli_real_escape_string($this->db->link, $pass);

        if (empty($sdt) || empty($pass)) {
            return "<span class='error'>Vui lòng nhập đầy đủ thông tin!</span>";
        }

        // Lấy user theo SĐT + mật khẩu (chưa kiểm is_active)
        $q = "SELECT id, ten, sodienthoai, is_active
                FROM khach_hang
                WHERE sodienthoai='$sdt' AND passwords='$pass' AND xoa=0
                LIMIT 1";
        $rs = $this->db->select($q);

        if (!$rs || !$rs->num_rows) {
            return "<span class='error'>Số điện thoại hoặc mật khẩu không đúng!</span>";
        }

        $u = $rs->fetch_assoc();
        if ((int)$u['is_active'] !== 1) {
            // Tài khoản chưa kích hoạt
            return "<span class='error'>Tài khoản của bạn chưa được kích hoạt. Vui lòng kiểm tra email để bấm liên kết kích hoạt (hoặc yêu cầu gửi lại liên kết).</span>";
        }

        // OK: cho đăng nhập
        Session::set('userlogin', true);
        Session::set('id',  $u['id']);
        Session::set('sdt', $u['sodienthoai']);
        Session::set('name', $u['ten']);

        echo "<script>
                    alert('Đăng nhập thành công!');
                    window.location.href = 'index.php';
                </script>";
        exit();
    }

    public function test_phone($sdt1)
    {
        $sdt1 = $this->fm->validation($sdt1);
        $sdt1 = mysqli_real_escape_string($this->db->link, $sdt1);
        $query = "SELECT * FROM khach_hang WHERE sodienthoai='$sdt1'";
        return $this->db->select($query);
    }

    public function test_pass($pass0)
    {
        $pass0 = $this->fm->validation($pass0);
        $pass0 = mysqli_real_escape_string($this->db->link, $pass0);
        $query = "SELECT * FROM khach_hang WHERE passwords='$pass0'";
        return $this->db->select($query);
    }

    public function check_email($email)
    {
        $email = $this->fm->validation($email);
        $email = mysqli_real_escape_string($this->db->link, $email);
        $query = "SELECT * FROM khach_hang WHERE email='$email'";
        return $this->db->select($query);
    }

    public function insert_user($ten, $sdt1, $sex, $email, $pass1, $repass)
    {
        $query = "INSERT INTO khach_hang(ten, sodienthoai, gioitinh, email, passwords, xoa)
                    VALUES('$ten', '$sdt1', '$sex', '$email', '$pass1', 0)";
        $result = $this->db->insert($query);

        if ($result) {
            return "<script>alert('Đăng ký thành công! Đang chuyển đến trang đăng nhập...');
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 1000);</script>";
        } else {
            return "<span class='error'>Đăng ký thất bại!</span>";
        }
    }


    public function admin_insert_user($ten, $sdt1, $sex, $email, $pass1, $repass)
    {
        // Thêm 'is_active' vào danh sách cột và giá trị '1' vào danh sách VALUES
        $query = "INSERT INTO khach_hang(ten, sodienthoai, gioitinh, email, passwords, xoa, is_active)
              VALUES('$ten', '$sdt1', '$sex', '$email', '$pass1', 0, 1)"; // <-- Đã thêm '1'
        $result = $this->db->insert($query);
        if ($result) {
            // SỬA LẠI: Thêm đường dẫn vào thư mục admin
            return "<script>
                            alert('Thêm khách hàng thành công!');
                            window.location.href = 'admin/customerlist.php'; 
                        </script>";
        } else {
            return "<span class='error'>Thêm khách hàng thất bại!</span>";
        }
    }

    public function update_user($ten, $sdt1, $sex, $email, $id)
    {
        $ten  = mysqli_real_escape_string($this->db->link, $this->fm->validation($ten));
        $sdt1 = mysqli_real_escape_string($this->db->link, $this->fm->validation($sdt1));
        $sex  = mysqli_real_escape_string($this->db->link, $this->fm->validation($sex));
        $email = mysqli_real_escape_string($this->db->link, $this->fm->validation($email));
        $id   = mysqli_real_escape_string($this->db->link, $this->fm->validation($id));

        $query = "UPDATE khach_hang 
                        SET ten = '$ten', 
                            sodienthoai = '$sdt1', 
                            gioitinh = '$sex',
                            email = '$email'
                        WHERE id = '$id'";
        $result = $this->db->update($query);

        if ($result) {
            return "<script>alert('Cập nhật thông tin thành công!');
                    window.location.href = 'userblog.php?id=$id';</script>";
        } else {
            return "<span class='error'>Cập nhật thất bại!</span>";
        }
    }

    public function update_user_admin($ten, $sdt1, $sex, $email, $id)
    {
        $ten  = mysqli_real_escape_string($this->db->link, $this->fm->validation($ten));
        $sdt1 = mysqli_real_escape_string($this->db->link, $this->fm->validation($sdt1));
        $sex  = mysqli_real_escape_string($this->db->link, $this->fm->validation($sex));
        $email = mysqli_real_escape_string($this->db->link, $this->fm->validation($email));
        $id   = mysqli_real_escape_string($this->db->link, $this->fm->validation($id));

        $chkQ = "
                SELECT id
                FROM khach_hang
                WHERE (sodienthoai = '$sdt1' OR email = '$email')
                AND id != '$id'
                LIMIT 1
            ";
        $chkR = $this->db->select($chkQ);
        if ($chkR && $chkR->num_rows) {
            echo "<script>alert('Số điện thoại hoặc email này đã được sử dụng bởi khách hàng khác.');</script>";
            return;
        }

        $sql = "
                UPDATE khach_hang
                SET ten = '$ten',
                    sodienthoai = '$sdt1',
                    gioitinh = '$sex',
                    email = '$email'
                WHERE id = '$id'
            ";
        $result = $this->db->update($sql);

        if ($result) {
            echo "<script>
                    alert('Cập nhật khách hàng thành công!');
                    window.location.href = 'customerlist.php';
                </script>";
        } else {
            echo "<script>alert('Cập nhật thất bại, vui lòng thử lại.');</script>";
        }
    }

    public function show_thongtin($id)
    {
        $query = "SELECT * FROM khach_hang WHERE id='$id'";
        return $this->db->select($query);
    }

    public function list_hopdong_by_user($userId)
    {
        // Đổi h.user_id thành cột đang liên kết với user của bạn (ví dụ: h.khachhang_id)
        $userId = (int)$userId;
        $sql = "
                SELECT
                    h.id_hopdong AS sesis,
                    DATE(h.ngaytochuc) AS dates,
                    h.noidung,
                    -- Ưu tiên h.tongtien, nếu chưa set thì tính SUM chi tiết
                    COALESCE(
                        h.tongtien,
                        SUM( COALESCE(c.thanhtien, c.soluong * COALESCE(c.gia, m.gia_mon)) )
                    ) AS tongtien,
                    h.tinhtrang,
                    h.payment_status
                FROM hopdong h
                LEFT JOIN hopdong_chitiet c ON c.hopdong_id = h.id_hopdong
                LEFT JOIN monan m           ON m.id_mon      = c.monan_id
                WHERE h.user_id = {$userId}   -- <=== đổi đúng tên cột liên kết user tại đây
                GROUP BY h.id_hopdong, dates, h.noidung, h.tinhtrang, h.payment_status, h.tongtien
                ORDER BY h.id_hopdong DESC
            ";
        return $this->db->select($sql);
    }

    public function show_all()
    {
        $query = "SELECT * FROM khach_hang WHERE xoa = 0";
        return $this->db->select($query);
    }

    public function show_deleted_users()
    {
        $query = "SELECT * FROM khach_hang WHERE xoa = 1";
        return $this->db->select($query);
    }

    public function restore_user($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE khach_hang SET xoa = 0 WHERE id = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            return "<script>
                    alert('Khôi phục khách hàng thành công!');
                    window.location.href = 'customerlist.php';
                </script>";
        } else {
            return "<script>
                    alert('Khôi phục thất bại!');
                    window.location.href = 'customerlist.php';
                </script>";
        }
    }

    public function soft_delete_user($id)
    {
        $id = mysqli_real_escape_string($this->db->link, $id);
        $query = "UPDATE khach_hang SET xoa = 1 WHERE id = '$id'";
        $result = $this->db->update($query);
        if ($result) {
            return "<script>
                    alert('Đã ẩn khách hàng!');
                    window.location.href = 'customerlist.php';
                </script>";
        } else {
            return "<script>
                    alert('Ẩn thất bại!');
                    window.location.href = 'customerlist.php';
                </script>";
        }
    }

    public function change_pass($id, $pass0, $pass1, $repass)
    {
        $a = $this->test_pass($pass0);
        if ($a) {
            if ($pass1 == $repass) {
                $query = "UPDATE khach_hang SET passwords='$pass1' WHERE id='$id'";
                $result = $this->db->insert($query);
                if ($result) {
                    return "<script>
                            alert('Đổi mật khẩu thành công!');
                            window.location.href = 'userblog.php?id=$id';
                        </script>";
                } else {
                    return "<span class='error'>Password change failed!</span>";
                }
            } else {
                return "<span class='error'>Password incorrect!</span>";
            }
        } else {
            return "<span class='error'>Enter the wrong old password!</span>";
        }
    }

    public function get_user_by_id($id)
    {
        $query = "SELECT * FROM khach_hang WHERE id = '$id' LIMIT 1";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /* ====================== EMAIL KÍCH HOẠT (MỚI) ====================== */

    // 1) Tạo token 10 phút & lưu vào DB, trả token thô để gửi qua email
    public function createActivationTokenForUserId($userId)
    {
        $userId = mysqli_real_escape_string($this->db->link, $userId);
        $rawToken  = bin2hex(random_bytes(32));                 // 64 hex
        $tokenHash = hash('sha256', $rawToken);
        $expires   = (new DateTimeImmutable('+10 minutes'))->format('Y-m-d H:i:s');

        $sql = "UPDATE khach_hang
                    SET is_active = 0,
                        activation_token_hash = '$tokenHash',
                        activation_expires = '$expires'
                    WHERE id = '$userId' AND xoa = 0
                    LIMIT 1";
        $ok = $this->db->update($sql);
        return $ok ? $rawToken : null;
    }

    // 2) Gửi email kích hoạt (tái dùng cấu hình SMTP như mail khuyến mãi)
    public function sendActivationEmail($toEmail, $toName, $rawToken)
    {
        // Validate địa chỉ nhận
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Activation mail error: invalid recipient <$toEmail>");
            return false;
        }
        // Lưu ý: đổi http thành https cho uy tín và sửa tên miền
        $link = "https://triskietnhahang.io.vn/activate.php?token=" . urlencode($rawToken);

        $mail = new PHPMailer(true);
        try {
            // === DEBUG (bật khi cần xem lỗi chi tiết trong error_log) ===
            // $mail->SMTPDebug  = 2;
            // $mail->Debugoutput = 'error_log';

            // === SMTP ===
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tknhahangtriskiet@gmail.com';
            $mail->Password   = 'gdbgtupmxquhytms'; // App Password 16 ký tự, KHÔNG khoảng trắng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 20;     // tránh treo

            // (DEV – nếu server dùng cert tự ký, chỉ bật khi test)
            // $mail->SMTPOptions = ['ssl' => [
            //     'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
            // ]];

            // From/Reply-To (trùng Username Gmail)
            $mail->setFrom('tknhahangtriskiet@gmail.com', 'Nhà Hàng TRisKiet');
            $mail->addReplyTo('tknhahangtriskiet@gmail.com', 'Nhà Hàng TRisKiet');

            // Người nhận
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            // Nội dung
            $safeName = htmlspecialchars($toName ?: $toEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mail->isHTML(true);
            $mail->Subject = 'Xác nhận tài khoản của bạn';
            $mail->Body = "
                <div style='font-family:Arial,Helvetica,sans-serif;line-height:1.6'>
                <p>Chào {$safeName},</p>
                <p>Vui lòng bấm vào nút dưới đây để <strong>kích hoạt tài khoản</strong>:</p>
                <p><a href=\"{$link}\" style=\"display:inline-block;padding:10px 16px;border-radius:6px;background:#2b7cff;color:#fff;text-decoration:none\">Kích hoạt tài khoản</a></p>
                <p>Nếu nút không bấm được, sao chép liên kết này và dán vào trình duyệt:</p>
                <p><a href=\"{$link}\">{$link}</a></p>
                <p><em>Lưu ý:</em> Liên kết có hiệu lực trong 10 phút.</p>
                </div>";
            $mail->AltBody = "Chào {$safeName}\n\nMở liên kết để kích hoạt (hết hạn 10 phút):\n{$link}";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            // LOG đầy đủ để debug
            error_log("Activation mail exception: " . $e->getMessage());
            if (property_exists($mail, 'ErrorInfo') && $mail->ErrorInfo) {
                error_log("Activation mail ErrorInfo: " . $mail->ErrorInfo);
            }
            return false;
        }
    }

    // 3) Kích hoạt tài khoản từ token trong link email
    public function activateAccountByToken($rawToken)
    {
        $rawToken = trim((string)$rawToken);
        if (!preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return "Liên kết không hợp lệ.";
        }
        $tokenHash = mysqli_real_escape_string($this->db->link, hash('sha256', $rawToken));

        $rs = $this->db->select("SELECT id, is_active, activation_expires
                                    FROM khach_hang
                                    WHERE activation_token_hash = '$tokenHash'
                                    LIMIT 1");
        if (!$rs || !$rs->num_rows) return "Liên kết kích hoạt không hợp lệ hoặc đã dùng.";
        $u = $rs->fetch_assoc();

        if ((int)$u['is_active'] === 1) return "Tài khoản đã kích hoạt. Bạn có thể đăng nhập.";
        if (!$u['activation_expires'] || new DateTimeImmutable() > new DateTimeImmutable($u['activation_expires'])) {
            return "Liên kết đã hết hạn (10 phút). Vui lòng yêu cầu gửi lại.";
        }

        $id = mysqli_real_escape_string($this->db->link, $u['id']);
        $ok = $this->db->update("UPDATE khach_hang
                                    SET is_active = 1,
                                        activation_token_hash = NULL,
                                        activation_expires = NULL
                                    WHERE id = '$id'
                                    LIMIT 1");
        return $ok ? "Kích hoạt thành công! Bạn có thể đăng nhập." : "Có lỗi khi kích hoạt. Vui lòng thử lại.";
    }

    // 4) Đăng ký + tạo token + gửi email (dangky.php sẽ gọi hàm này)
    public function registerWithActivation($ten, $sdt1, $sex, $email, $pass1_md5)
    {
        $ten   = mysqli_real_escape_string($this->db->link, $this->fm->validation($ten));
        $sdt1  = mysqli_real_escape_string($this->db->link, $this->fm->validation($sdt1));
        $sex   = mysqli_real_escape_string($this->db->link, $this->fm->validation($sex));
        $email = mysqli_real_escape_string($this->db->link, $this->fm->validation($email));
        $pass  = mysqli_real_escape_string($this->db->link, $this->fm->validation($pass1_md5));

        $ok = $this->db->insert("INSERT INTO khach_hang(ten, sodienthoai, gioitinh, email, passwords, xoa, is_active)
                                    VALUES('$ten', '$sdt1', '$sex', '$email', '$pass', 0, 0)");
        if (!$ok) return "Đăng ký thất bại! Vui lòng thử lại.";

        $newId = $this->db->link->insert_id;

        $rawToken = $this->createActivationTokenForUserId($newId);
        if (!$rawToken) return "Tạo mã kích hoạt thất bại. Vui lòng thử lại.";

        $sent = $this->sendActivationEmail($email, $ten, $rawToken);
        if (!$sent) return "Gửi email kích hoạt thất bại. Vui lòng thử lại sau.";

        return "Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt (hết hạn 10 phút).";
    }
}

/* Nếu bạn KHÔNG dùng Composer, thay vì autoload ở trên:
    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    */
