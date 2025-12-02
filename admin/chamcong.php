<?php
// Include header và sidebar như bình thường
include 'inc/header.php';
include 'inc/sidebar.php';
// Bạn có thể giữ lại include class nếu cần cho các chức năng khác sau này
// include '../classes/lichdangky.php';
// $lich = new lichdangky(); // Không cần thiết nếu chỉ chuyển hướng
?>

<style>
    /* CSS cơ bản */
    * { box-sizing: border-box; }
    .form-wrapper {
        max-width: 600px;
        margin: 50px auto;
        padding: 40px; /* Tăng padding */
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        text-align: center; /* Căn giữa nội dung */
    }
    .form-wrapper h2 {
        margin-bottom: 35px; /* Tăng khoảng cách */
        font-size: 26px; /* Tăng cỡ chữ */
        color: #34495e;
        border-bottom: 1px solid #ecf0f1;
        padding-bottom: 15px;
        font-weight: 600;
    }
     /* CSS cho các nút chức năng */
    .action-buttons {
        display: flex; /* Sắp xếp nút ngang */
        justify-content: center; /* Căn giữa các nút */
        gap: 20px; /* Khoảng cách giữa các nút */
        margin-top: 30px;
    }
    .action-buttons a { /* Style nút như button */
        display: inline-block;
        padding: 15px 35px; /* Tăng kích thước nút */
        font-size: 18px; /* Tăng cỡ chữ nút */
        border: none;
        border-radius: 8px; /* Bo tròn hơn */
        cursor: pointer;
        color: white;
        text-decoration: none; /* Bỏ gạch chân link */
        transition: background-color 0.3s, box-shadow 0.2s, transform 0.1s;
        font-weight: 600;
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    }
     .action-buttons a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }
    /* Màu riêng cho từng nút */
    .btn-face-check-in { background-color: #2ecc71; } /* Xanh lá */
    .btn-face-check-in:hover { background-color: #27ae60; }
    .btn-face-check-out { background-color: #e74c3c; } /* Đỏ */
    .btn-face-check-out:hover { background-color: #c0392b; }

     .info-text { /* Đoạn text hướng dẫn */
        margin-top: 30px;
        font-size: 14px;
        color: #555;
        line-height: 1.6;
     }

</style>

<div class="grid_10">
    <div class="box round first grid">
         <div class="form-wrapper">
             <h2>📷 Chấm Công Bằng Khuôn Mặt</h2>

             <div class="info-text">
                 Vui lòng chọn chức năng bạn muốn thực hiện. Hệ thống sẽ sử dụng camera để nhận diện khuôn mặt và ghi nhận thời gian chấm công.
             </div>

             <div class="action-buttons">
                 <a href="http://localhost:5000/diem_danh" target="_blank" class="btn-face-check-in">✅ Check In</a>
                 <a href="http://localhost:5000/check_out" target="_blank" class="btn-face-check-out">🚪 Check Out</a>
             </div>

             <p style="text-align: center; margin-top: 25px; font-size: 12px; color: #888;">
                (Các trang chấm công sẽ mở trong tab mới)
             </p>
         </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>