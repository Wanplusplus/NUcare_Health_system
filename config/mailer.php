<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendResetEmail($email, $resetLink)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'seanjhanz111@gmail.com';
        $mail->Password = 'ibwl otru owny rvtc';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('seanjhanz111@gmail.com', 'NUCARE Health System');

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = 'NUCARE Password Reset';

        $mail->Body = "
            <h2>Password Reset Request</h2>

            <p>Click the button below to reset your password:</p>

            <a href='$resetLink'
               style='
                    background:#2563eb;
                    color:white;
                    padding:12px 20px;
                    text-decoration:none;
                    border-radius:8px;
               '>
                Reset Password
            </a>

            <p>This link expires in 20 minutes.</p>
        ";

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}
?>