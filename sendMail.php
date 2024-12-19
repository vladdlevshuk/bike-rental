<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'PHPMailer.php';
require 'SMTP.php';
require 'LoggerInterface.php';
require 'Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$mail = new PHPMailer\PHPMailer\PHPMailer();

try {
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'smtp.mail.ru';
    $mail->SMTPAuth = true;
    $mail->Username = 'levshukvlad@mail.ru';
    $mail->Password = 'b1SyiKnHxfazEvk9mVfZ';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('levshukvlad@mail.ru', 'Ваше Имя');

    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['email']) && isset($data['message'])) {
        $recipientEmail = $data['email'];
        $message = $data['message'];

        $mail->addAddress($recipientEmail);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = 'Уведомление о бронировании';
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        if ($mail->send()) {
            echo 'Письмо отправлено!';
        } else {
            echo 'Ошибка отправки: ' . $mail->ErrorInfo;
        }
    } else {
        echo 'Необходимы параметры email и message';
    }
} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
