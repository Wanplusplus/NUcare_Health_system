<?php
// PHPMailer setup using Gmail SMTP for NUcare password reset emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendResetEmail(string $recipientEmail, string $resetLink): bool
{
    $mail = new PHPMailer(true);

    // Ensure we can see the real Gmail/SMTP rejection reason in logs
    $mail->SMTPDebug = 0;

    try {

        // ── SMTP Configuration ────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seanjhanz111@gmail.com';       // ← Your Gmail here
        $mail->Password   = 'ibwl otru owny rvtc';       // ← Your 16-char App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ── Sender & Recipient ────────────────────────────────────────────
        $mail->setFrom('seanjhanz111@gmail.com', 'NUcare Health System');
        $mail->addAddress($recipientEmail);

        // ── Email Content ─────────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = 'NUcare Password Reset Request';
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 30px; }
                .wrapper { max-width: 520px; margin: auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,.10); }
                .header { background: linear-gradient(135deg, #0f766e, #0d9488); padding: 36px 32px; text-align: center; }
                .header img { width: 52px; margin-bottom: 10px; }
                .header h1 { margin: 0; color: #fff; font-size: 22px; letter-spacing: 2px; }
                .header p { margin: 4px 0 0; color: #ccfbf1; font-size: 13px; }
                .body { padding: 36px 32px; }
                .body h2 { color: #0f172a; font-size: 20px; margin: 0 0 12px; }
                .body p { color: #475569; font-size: 15px; line-height: 1.7; margin: 0 0 20px; }
                .btn { display: inline-block; background: linear-gradient(135deg, #0f766e, #0d9488); color: #ffffff !important; padding: 14px 36px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 15px; letter-spacing: .5px; }
                .notice { background: #f8fafc; border-left: 4px solid #0f766e; border-radius: 8px; padding: 14px 18px; margin-top: 24px; font-size: 13px; color: #64748b; }
                .footer { text-align: center; padding: 20px; background: #f8fafc; font-size: 12px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <div class='header'>
                    <h1>NUCARE</h1>
                    <p>Health Management System</p>
                </div>
                <div class='body'>
                    <h2>This is NUcare</h2>
                    <p>We received a password reset request for your account.<br>Click the button below to create a new password:</p>
                    <p style='text-align:center;'>
                        <a href='{$resetLink}' class='btn'>Reset Password</a>
                    </p>
                    <div class='notice'>
                        ⏳ This link will expire in <strong>20 minutes</strong>.<br>
                        If you did not request this, please ignore this email — your password will remain unchanged.
                    </div>
                </div>
                <div class='footer'>© " . date('Y') . " NUcare Health System. All rights reserved.</div>
            </div>
        </body>
        </html>";

        $mail->AltBody = "Reset your NUcare password: {$resetLink} (Expires in 20 minutes)";
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        error_log('PHPMailer Exception: ' . $e->getMessage());
        return false;
    }
}

