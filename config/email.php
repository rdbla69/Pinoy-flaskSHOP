<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Site configuration
$site_config = [
    'domain' => 'pinoyflask.com', // Change this to your actual domain
    'name' => 'Pinoy Shop',
    'email' => 'kathrinakrizell@gmail.com'
];

function sendEmail($to, $subject, $body) {
    global $site_config;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $site_config['email'];
        $mail->Password = 'antdyfispauedyqm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom($site_config['email'], $site_config['name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function sendOTPEmail($email, $otp) {
    global $site_config;
    $subject = "Your Verification Code";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #333;'>Welcome to {$site_config['name']}!</h2>
        <p>Your verification code is:</p>
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0;'>
            <h1 style='color: #0d6efd; margin: 0; font-size: 32px; letter-spacing: 5px;'>{$otp}</h1>
        </div>
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request this code, please ignore this email.</p>
        <hr style='border: 1px solid #eee; margin: 20px 0;'>
        <p style='color: #666; font-size: 12px;'>This is an automated message, please do not reply.</p>
    </div>
    ";
    
    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $token) {
    global $site_config;
    $subject = "Reset your password";
    $resetLink = "https://" . $site_config['domain'] . "/reset-password.php?token=" . $token;
    
    $body = "
    <h2>Password Reset Request</h2>
    <p>You have requested to reset your password. Click the link below to proceed:</p>
    <p><a href='{$resetLink}'>{$resetLink}</a></p>
    <p>This link will expire in 1 hour.</p>
    <p>If you did not request a password reset, please ignore this email.</p>
    ";
    
    return sendEmail($email, $subject, $body);
}
?> 