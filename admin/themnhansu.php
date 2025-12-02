<?php
// FILE: themnhansu.php (NGANG CẤP VỚI THƯ MỤC 'inc' và 'classes')

// ========== INCLUDES VÀ KHỞI TẠO ==========
include 'inc/header.php'; // Header chung
include 'inc/sidebar.php'; // Sidebar chung

// Include Class NhanSu và Format Helper
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../classes/nhansu.php');
include_once($filepath . '/../helpers/format.php');

// Chỉ Admin (level 0) mới được vào trang này
if (Session::get('adminlevel') != 0) {
    echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='index.php';</script>";
    exit();
}

// Khởi tạo đối tượng
$nhansu_class = new NhanSu();
$fm = new Format();

// ========== XỬ LÝ SUBMIT FORM ==========
$insertMsg = ''; // Lưu thông báo
$id_admin_moi = null; // Lưu ID nếu thêm thành công để dùng cho camera
$hoten_moi = '';     // Lưu họ tên nếu thêm thành công

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Gọi hàm themNhanSu từ class
    $ket_qua_them = $nhansu_class->themNhanSu($_POST, $_FILES); // Truyền cả $_FILES

    if (is_numeric($ket_qua_them) && $ket_qua_them > 0) {
        $id_admin_moi = $ket_qua_them; // Lưu ID admin mới thành công
        $hoten_moi = $_POST['hoten']; // Lấy lại họ tên để dùng cho camera và JS
        $insertMsg = "<span class='success'>Thêm nhân sự thành công! Mã Admin mới: $id_admin_moi.</span>";
        // KHÔNG chuyển hướng vội, chờ xử lý camera bên dưới
    } else {
        // Nếu $ket_qua_them là string -> là thông báo lỗi
        $insertMsg = $ket_qua_them; // Hiển thị lỗi từ class
        $id_admin_moi = null; // Đảm bảo không hiện camera nếu có lỗi
    }
}
?>

<style>
    /* Reset cơ bản & Form Styling */
    *, *::before, *::after { box-sizing: border-box; }
    .form-container { max-width: 800px; margin: 20px auto; background-color: #fff; padding: 30px 40px; border-radius: 10px; box-shadow: 0 4px 25px rgba(0,0,0,0.1); }
    h2 { text-align: center; margin-bottom: 30px; font-weight: 700; font-size: 1.8rem; color: #0d6efd; }
    form { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px 30px; }
    label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.95rem; }
    input[type="text"], input[type="date"], select, input[type="file"] { width: 100%; padding: 9px 12px; border: 1.5px solid #ccc; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.3s ease; }
    input[type="text"]:focus, input[type="date"]:focus, select:focus, input[type="file"]:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 4px rgba(13, 110, 253, 0.5); }
    .full-width { grid-column: 1 / -1; }
    button[type="submit"], #captureBtn, #retryBtn, .skip-button { padding: 8px 15px; margin: 10px 5px 0; border-radius: 5px; border: none; cursor: pointer; color: white; font-size: 0.95rem; transition: background-color 0.3s; }
    button[type="submit"] { grid-column: 1 / -1; padding: 12px 0; background-color: #198754; font-size: 1.1rem; margin-top: 15px; }
    button[type="submit"]:hover { background-color: #157347; }
    .error-message { color: #dc3545; font-size: 0.85rem; margin-top: 3px; display: block; min-height: 16px; }
    .note { font-size: 0.85rem; color: #6c757d; display: block; margin-top: 3px; }
     /* Thông báo */
     .message { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; grid-column: 1 / -1; /* Đảm bảo thông báo chiếm đủ rộng */ }
     .success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
     .error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
     /* Camera */
     #camera_container { margin-top: 20px; padding: 15px; border: 1px dashed #ccc; border-radius: 8px; text-align: center; display: none; /* Ẩn ban đầu */ }
     #video { width: 100%; max-width: 320px; border: 1px solid #eee; border-radius: 5px; background-color: #f0f0f0; /* Màu nền chờ camera */ min-height: 240px; /* Chiều cao tối thiểu */ }
     #result { margin-top: 10px; font-weight: bold; min-height: 20px; /* Đảm bảo có khoảng trống */ }
     #captureBtn { background-color: #0d6efd; }
     #retryBtn { background-color: #ffc107; color: #333; display: none; }
     .skip-button { background-color:#6c757d; }
     /* Toast */
     #toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background-color: rgba(0,0,0,0.8); color: white; padding: 12px 25px; border-radius: 6px; z-index: 1000; opacity: 0; transition: opacity 0.5s ease; font-size: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
     #toast.show { opacity: 1; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <div class="form-container">
            <h2>➕ Thêm Nhân Sự Mới</h2>

            <?php
            // Hiển thị thông báo (nếu có)
            if ($insertMsg) {
                // Xác định class dựa trên sự thành công hay thất bại
                $msgClass = ($id_admin_moi !== null && strpos($insertMsg, 'success') !== false) ? 'success' : 'error';
                echo "<div class='message {$msgClass}'>{$insertMsg}</div>";
            }
            ?>

            <?php
            // Chỉ hiển thị form khi chưa thêm thành công HOẶC khi có lỗi (ngay cả khi đã POST)
            $showForm = !$id_admin_moi || (!empty($insertMsg) && $id_admin_moi === null);
            if ($showForm):
            ?>
            <form method="POST" enctype="multipart/form-data" id="formThemNhanSu" novalidate>
                <h3 class="full-width" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Thông tin tài khoản</h3>
                <div>
                    <label for="hoten">Họ Tên (Name_admin) *</label>
                    <input type="text" name="hoten" id="hoten" value="<?php echo isset($_POST['hoten']) ? htmlspecialchars($_POST['hoten']) : ''; ?>" required />
                    <span class="error-message" id="err_hoten"></span>
                </div>
                <div>
                    <label for="adminuser">Tên Đăng Nhập *</label>
                    <input type="text" name="adminuser" id="adminuser" value="<?php echo isset($_POST['adminuser']) ? htmlspecialchars($_POST['adminuser']) : ''; ?>" required />
                    <span class="error-message" id="err_adminuser"></span>
                    <small class="note">Mật khẩu mặc định sẽ là '123456'.</small>
                </div>
                 <div>
                    <label for="level">Chức Vụ *</label>
                    <select name="level" id="level" required>
                      <option value="">-- Chọn Chức Vụ --</option>
                      <?php
                      $roles = $nhansu_class->layDanhSachVaiTro();
                      if ($roles) {
                          foreach ($roles as $role) {
                              $selected = (isset($_POST['level']) && $_POST['level'] == $role['id_role']) ? 'selected' : '';
                              echo '<option value="' . $role['id_role'] . '" ' . $selected . '>' . htmlspecialchars($role['ten_role']) . '</option>';
                          }
                      }
                      ?>
                    </select>
                    <span class="error-message" id="err_level"></span>
                  </div>

                <h3 class="full-width" style="margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Thông tin cá nhân</h3>
                <div>
                  <label for="ngaysinh">Ngày Sinh *</label>
                  <input type="date" name="ngaysinh" id="ngaysinh" value="<?php echo isset($_POST['ngaysinh']) ? htmlspecialchars($_POST['ngaysinh']) : ''; ?>" required />
                  <span class="error-message" id="err_ngaysinh"></span>
                </div>
                <div>
                  <label for="gioitinh">Giới Tính</label>
                  <select name="gioitinh" id="gioitinh">
                      <option value="" <?php echo (!isset($_POST['gioitinh'])) ? 'selected' : ''; ?>>-- Chọn --</option>
                      <option value="Nam" <?php echo (isset($_POST['gioitinh']) && $_POST['gioitinh'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                      <option value="Nữ" <?php echo (isset($_POST['gioitinh']) && $_POST['gioitinh'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                      <option value="Khác" <?php echo (isset($_POST['gioitinh']) && $_POST['gioitinh'] == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                  </select>
                </div>
                 <div class="full-width">
                  <label for="diachi">Địa Chỉ *</label>
                  <input type="text" name="diachi" id="diachi" value="<?php echo isset($_POST['diachi']) ? htmlspecialchars($_POST['diachi']) : ''; ?>" required />
                  <span class="error-message" id="err_diachi"></span>
                </div>
                <div>
                  <label for="ngayvaolam">Ngày Vào Làm *</label>
                  <input type="date" name="ngayvaolam" id="ngayvaolam" value="<?php echo isset($_POST['ngayvaolam']) ? htmlspecialchars($_POST['ngayvaolam']) : date('Y-m-d'); ?>" required />
                  <span class="error-message" id="err_ngayvaolam"></span>
                </div>
                <div>
                  <label for="cccd">Số CCCD *</label>
                  <input type="text" name="cccd" id="cccd" value="<?php echo isset($_POST['cccd']) ? htmlspecialchars($_POST['cccd']) : ''; ?>" required pattern="\d{12}" title="CCCD phải đủ 12 số" />
                  <span class="error-message" id="err_cccd"></span>
                </div>
                <div>
                  <label for="ngaycap_cccd">Ngày Cấp CCCD *</label>
                  <input type="date" name="ngaycap_cccd" id="ngaycap_cccd" value="<?php echo isset($_POST['ngaycap_cccd']) ? htmlspecialchars($_POST['ngaycap_cccd']) : ''; ?>" required />
                  <span class="error-message" id="err_ngaycap_cccd"></span>
                </div>
                <div>
                  <label for="noicap_cccd">Nơi Cấp CCCD *</label>
                  <input type="text" name="noicap_cccd" id="noicap_cccd" value="<?php echo isset($_POST['noicap_cccd']) ? htmlspecialchars($_POST['noicap_cccd']) : ''; ?>" required />
                  <span class="error-message" id="err_noicap_cccd"></span>
                </div>
                <div>
                  <label for="quequan">Quê Quán</label>
                  <input type="text" name="quequan" id="quequan" value="<?php echo isset($_POST['quequan']) ? htmlspecialchars($_POST['quequan']) : ''; ?>" />
                </div>
                <div class="full-width">
                  <label for="anh_dai_dien">Ảnh Đại Diện (Tùy chọn)</label>
                  <input type="file" name="anh_dai_dien" id="anh_dai_dien" accept="image/*" />
                  <small class="note">Dung lượng tối đa 2MB.</small>
                </div>
                <button type="submit" name="submit">Thêm Nhân Sự</button>
            </form>
            <?php endif; // Kết thúc if ($showForm) ?>

            <?php
            // Chỉ hiển thị phần camera KHI đã thêm thành công ($id_admin_moi có giá trị)
            if ($id_admin_moi !== null):
            ?>
            <div id="camera_container">
                <h3>Thêm Dữ Liệu Khuôn Mặt (Tùy chọn)</h3>
                <video id="video" autoplay playsinline muted></video> <div id="result" class="result-info">Đang khởi tạo camera...</div> <button id="captureBtn" style="display: none;">Chụp ảnh</button>
                <button id="retryBtn" style="display: none;">Thử lại</button>
                <button onclick="skipFace()" class="skip-button">Bỏ qua</button>
            </div>
            <div id="toast"></div>
            <?php endif; // Kết thúc if ($id_admin_moi !== null) ?>

             <div style="text-align: center; margin-top: 15px;">
                  <a href="quanlynhansu_list.php" style="color: #6c757d; text-decoration: none;">&laquo; Quay lại danh sách</a>
              </div>
        </div>
    </div>
</div>

<script>
    // ----- Client-side Validation -----
    const form = document.getElementById('formThemNhanSu');
    if(form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            document.querySelectorAll('.error-message').forEach(el => el.innerText = '');

            // --- Thêm các kiểm tra còn thiếu hoặc chi tiết hơn nếu cần ---
            const fields = [
                { id: 'hoten', errorId: 'err_hoten', message: 'Vui lòng nhập họ tên.' },
                { id: 'adminuser', errorId: 'err_adminuser', message: 'Vui lòng nhập tên đăng nhập.', pattern: /^\S*$/, patternMessage: 'Tên đăng nhập không được chứa khoảng trắng.'}, // Thêm kiểm tra khoảng trắng
                { id: 'level', errorId: 'err_level', message: 'Vui lòng chọn chức vụ.' },
                { id: 'ngaysinh', errorId: 'err_ngaysinh', message: 'Vui lòng chọn ngày sinh.' },
                { id: 'diachi', errorId: 'err_diachi', message: 'Vui lòng nhập địa chỉ.' },
                { id: 'ngayvaolam', errorId: 'err_ngayvaolam', message: 'Vui lòng chọn ngày vào làm.' },
                { id: 'cccd', errorId: 'err_cccd', message: 'Vui lòng nhập số CCCD.', pattern: /^\d{12}$/, patternMessage: 'CCCD phải đủ 12 chữ số.' },
                { id: 'ngaycap_cccd', errorId: 'err_ngaycap_cccd', message: 'Vui lòng chọn ngày cấp CCCD.' },
                { id: 'noicap_cccd', errorId: 'err_noicap_cccd', message: 'Vui lòng nhập nơi cấp CCCD.' }
            ];

            fields.forEach(field => {
                const element = document.getElementById(field.id);
                const value = element.value.trim();
                const errorElement = document.getElementById(field.errorId);

                if (!value && element.required) { // Chỉ kiểm tra required
                    errorElement.innerText = field.message;
                    isValid = false;
                } else if (field.pattern && !field.pattern.test(value)) {
                     errorElement.innerText = field.patternMessage;
                     isValid = false;
                }

                // Kiểm tra tuổi (ví dụ)
                if (field.id === 'ngaysinh' && value) {
                    const birthDate = new Date(value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const m = today.getMonth() - birthDate.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) { age--; }
                    if (age < 18) {
                        errorElement.innerText = 'Nhân viên phải đủ 18 tuổi.';
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Vui lòng kiểm tra lại các thông tin bắt buộc.');
            }
        });
    }

    // ----- Camera Code -----
    let stream = null;
    const video = document.getElementById('video');
    const result = document.getElementById('result');
    const container = document.getElementById('camera_container');
    const captureBtn = document.getElementById('captureBtn');
    const retryBtn = document.getElementById('retryBtn');
    const toast = document.getElementById('toast');
    let current_id_admin = null;
    let current_Name_admin = null; // Sửa tên biến cho nhất quán

    <?php
    // Chỉ chạy code JS này KHI thêm nhân sự thành công
    // Dùng kiểm tra strpos để chắc chắn là thông báo thành công
    if ($id_admin_moi !== null && !empty($insertMsg) && strpos($insertMsg, 'success') !== false):
    ?>
        console.log("PHP xác nhận thêm thành công, chuẩn bị hỏi bật camera.");
        // Gán giá trị từ PHP vào biến JS
        current_id_admin = <?php echo json_encode($id_admin_moi); ?>;
        current_Name_admin = <?php echo json_encode($hoten_moi); ?>; // Dùng $hoten_moi từ PHP

        // Đợi DOM sẵn sàng một chút rồi mới hỏi
        document.addEventListener('DOMContentLoaded', (event) => {
            if (confirm('Thêm nhân sự thành công! Bạn có muốn thêm khuôn mặt ngay bây giờ không? (Cần cấp quyền camera)')) {
                console.log("Người dùng đồng ý, gọi startCamera().");
                startCamera(); // Bật camera
            } else {
                console.log("Người dùng từ chối, chuyển hướng.");
                window.location.href = 'quanlynhansu_list.php';
            }
        });
    <?php elseif (!empty($insertMsg) && $id_admin_moi === null): ?>
        console.log("Có lỗi khi thêm nhân sự từ PHP.");
        // Không làm gì cả, chỉ hiển thị lỗi PHP
    <?php endif; ?>

    function startCamera() {
        console.log("Hàm startCamera() được gọi.");
        if (!current_id_admin || !container || !video || !result || !captureBtn || !retryBtn) {
            console.error("Thiếu các element HTML cần thiết cho camera.");
            setResult("Lỗi: Thiếu element HTML cho camera.", "error");
            return;
        }

        container.style.display = 'block'; // Hiện phần camera
        setResult("Đang yêu cầu quyền truy cập camera...", "info"); // Cập nhật trạng thái

        if (stream) { stopCamera(); } // Tắt stream cũ nếu có

        // Yêu cầu truy cập camera
        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(s => {
                console.log("Đã cấp quyền camera.");
                stream = s;
                video.srcObject = stream;
                // Đợi video sẵn sàng để lấy kích thước (quan trọng)
                video.onloadedmetadata = () => {
                    console.log("Video metadata đã load.");
                    setResult("Camera đã bật cho: " + current_Name_admin + ". Nhấn nút 'Chụp ảnh'.", "info");
                    captureBtn.style.display = 'inline-block'; // Hiện nút chụp
                    retryBtn.style.display = 'none';
                };
            })
            .catch(e => {
                console.error('Lỗi khi gọi getUserMedia:', e);
                let errorMsg = 'Không thể bật camera: ';
                if (e.name === "NotAllowedError") {
                    errorMsg += "Bạn đã từ chối quyền truy cập camera.";
                } else if (e.name === "NotFoundError") {
                    errorMsg += "Không tìm thấy thiết bị camera nào.";
                } else if (e.name === "NotReadableError") {
                    errorMsg += "Không thể đọc dữ liệu từ camera (có thể đang bị dùng bởi ứng dụng khác?).";
                } else {
                    errorMsg += e.message;
                }
                 setResult(errorMsg + " Vui lòng kiểm tra cài đặt trình duyệt hoặc cấp lại quyền.", "error");
                container.style.display = 'block'; // Vẫn hiện container để thấy lỗi
                captureBtn.style.display = 'none';
                retryBtn.style.display = 'none';
            });
    }

    if(captureBtn) {
         captureBtn.onclick = () => {
              console.log("Nút Chụp ảnh được nhấn.");
              takePhoto();
         };
    }
    if(retryBtn) {
        retryBtn.onclick = () => {
            console.log("Nút Thử lại được nhấn.");
            setResult("", "info"); // Xóa thông báo cũ
            captureBtn.style.display = 'inline-block';
            retryBtn.style.display = 'none';
            if (!stream) {
                 console.log("Stream không tồn tại, gọi lại startCamera().");
                 startCamera(); // Bật lại camera nếu stream đã bị dừng
            } else {
                 setResult("Camera đã bật cho: " + current_Name_admin + ". Nhấn nút 'Chụp ảnh'.", "info");
            }
        };
    }

    function takePhoto() {
        if (!current_id_admin || !video || !result || !captureBtn || !retryBtn || !stream) {
             console.error("takePhoto(): Thiếu điều kiện cần thiết.");
             setResult("Lỗi: Không thể chụp ảnh lúc này.", "error");
             return;
        }
        // Kiểm tra xem video đã sẵn sàng chưa
        if (video.readyState < video.HAVE_CURRENT_DATA) {
            console.warn("takePhoto(): Video chưa sẵn sàng.");
             setResult("Camera chưa sẵn sàng, vui lòng chờ...", "warning");
            return;
        }


        captureBtn.disabled = true; // Vô hiệu hóa nút chụp
        captureBtn.innerText = "Đang xử lý...";
        setResult("Đang chụp và gửi ảnh...", "info");

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        console.log(`Kích thước ảnh chụp: ${canvas.width}x${canvas.height}`);
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(blob => {
            if (!blob) {
                console.error("Lỗi tạo ảnh blob.");
                setResult("Lỗi tạo ảnh blob.", "error");
                retryBtn.style.display = 'inline-block';
                captureBtn.innerText = "Chụp ảnh"; // Reset text nút
                captureBtn.disabled = false; // Bật lại nút
                captureBtn.style.display = 'none'; // Ẩn lại nút chụp chính
                return;
            }
            console.log("Ảnh blob đã được tạo, chuẩn bị gửi.");
            const formData = new FormData();
            formData.append('id_admin', current_id_admin);
            // === SỬA LỖI TÊN BIẾN ===
            formData.append('Name_admin', current_Name_admin); // Gửi Name_admin
            formData.append('image', blob, `face_${current_id_admin}.jpg`);

            const apiUrl = 'http://localhost:5000/api/them_khuon_mat'; // URL API Python
             console.log(`Gửi ảnh đến: ${apiUrl}`);

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => {
                // Luôn cố gắng parse JSON, nhưng chuẩn bị cho lỗi
                 console.log(`Server phản hồi với status: ${res.status}`);
                 return res.json().catch(err => {
                      console.error("Lỗi parse JSON:", err);
                      // Nếu parse lỗi, ném lỗi mới chứa status code
                      throw new Error(`Server không trả về JSON hợp lệ (Status: ${res.status})`);
                 });
            })
            .then(data => {
                console.log("Nhận dữ liệu JSON từ server:", data);
                if (data.success) {
                    showToast('Thêm khuôn mặt thành công!');
                    setResult(data.message || 'Thêm khuôn mặt thành công.', "success");
                    stopCamera(); // Tắt camera
                    // Chuyển hướng về trang danh sách sau khi thêm mặt thành công
                    setTimeout(() => {
                        window.location.href = 'quanlynhansu_list.php';
                    }, 2500); // Tăng thời gian chờ lên 1 chút
                } else {
                     setResult("Lỗi từ server: " + (data.message || "Không có thông báo lỗi chi tiết."), "error");
                    retryBtn.style.display = 'inline-block'; // Cho phép thử lại
                    captureBtn.style.display = 'none'; // Ẩn nút chụp chính
                }
            })
            .catch(e => {
                console.error('Lỗi fetch hoặc xử lý response:', e);
                setResult("Lỗi khi gửi ảnh đến server API: " + e.message + ". Vui lòng kiểm tra xem server Python đã chạy chưa và CORS đã được cấu hình đúng.", "error");
                retryBtn.style.display = 'inline-block'; // Cho phép thử lại
                captureBtn.style.display = 'none'; // Ẩn nút chụp chính
            })
            .finally(() => {
                 // Luôn bật lại nút chụp (hoặc nút thử lại đã hiện) và reset text
                 captureBtn.innerText = "Chụp ảnh";
                 captureBtn.disabled = false;
                 // Không hiện lại captureBtn ở đây, để retryBtn kiểm soát
            });
        }, 'image/jpeg', 0.9); // Chất lượng ảnh JPEG
    }

    function skipFace() {
        console.log("Người dùng chọn Bỏ qua.");
        stopCamera();
        if(container) container.style.display = 'none';
        showToast('Đã bỏ qua thêm khuôn mặt.');
        // Chuyển hướng về trang danh sách
        setTimeout(() => {
            window.location.href = 'quanlynhansu_list.php';
        }, 1500);
    }

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000); // Tự ẩn sau 3 giây
    }

    function setResult(message, type = 'info') { // Hàm tiện ích để cập nhật #result
        if (!result) return;
        result.innerText = message;
        // Đặt class CSS dựa trên type (success, error, warning, info)
        result.className = `result-${type}`;
        console.log(`[Result UI] ${type}: ${message}`); // Log ra console
    }


    function stopCamera() {
        if (stream) {
            console.log("Đang dừng camera stream.");
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            if(video) video.srcObject = null;
            if(captureBtn) captureBtn.style.display = 'none';
            if(retryBtn) retryBtn.style.display = 'none';
            console.log("Đã dừng camera thành công.");
        }
    }

     // Đảm bảo dừng camera khi người dùng rời khỏi trang
     window.addEventListener('beforeunload', stopCamera);

</script>

<?php include 'inc/footer.php'; ?>