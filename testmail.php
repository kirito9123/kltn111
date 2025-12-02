<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tknhahangtriskiet@gmail.com';   // Gmail bạn dùng để gửi
    $mail->Password   = 'gdbgtupmxquhytms';              // App Password 16 ký tự, KHÔNG có dấu cách
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('tknhahangtriskiet@gmail.com', 'Test App'); 
    $mail->addAddress('tringuyen9123@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test Mail';
    $mail->Body    = 'Đây là mail test từ PHPMailer qua Composer autoload.';

    $mail->send();
    echo "Gửi mail thành công!";
} catch (Exception $e) {
    echo "Lỗi khi gửi mail: {$mail->ErrorInfo}";
}