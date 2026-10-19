<?php
// these lines load the PHPMailer library you downloaded
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendVerificationEmail($user_email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // ---------------------------------------------------------
        // 👇 EDIT THESE TWO LINES WITH YOUR NEW APP PASSWORD 👇
        // ---------------------------------------------------------
        $my_email = 'dustinperrera10@gmail.com'; 
        $my_app_password = 'hjgy qods nmqp ovkb'; 
        // ---------------------------------------------------------

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $my_email;
        $mail->Password   = $my_app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($my_email, 'SNEAKDUP Support');
        $mail->addAddress($user_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SNEAKDUP - Account Verification Code';
        
        // Custom SNEAKDUP HTML Email Template (Using Inline CSS for Email Clients)
        $mail->Body = "
        <div style='font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; padding: 40px 20px; margin: 0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                
                <div style='background-color: #111111; padding: 30px 20px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 28px; color: #ffffff; letter-spacing: 2px; text-transform: uppercase;'>
                        SNEAK<span style='color: #cc0000;'>D</span>UP
                    </h1>
                </div>

                <div style='padding: 40px 30px; text-align: center;'>
                    <h2 style='margin-top: 0; color: #111111; font-size: 22px;'>Verify Your Account</h2>
                    <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>
                        What's good! Thank you for registering at SNEAKDUP. To complete your setup and access exclusive drops, please use the verification code below.
                    </p>

                    <div style='background-color: #f9f9f9; border: 2px dashed #cccccc; border-radius: 8px; padding: 20px; max-width: 250px; margin: 0 auto 30px auto;'>
                        <h1 style='margin: 0; font-size: 42px; color: #cc0000; letter-spacing: 8px;'>$otp</h1>
                    </div>

                    <p style='color: #888888; font-size: 14px; margin: 0;'>
                        This code will expire soon. Please do not share this code with anyone.
                    </p>
                </div>

                <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                    <p style='margin: 0; color: #aaaaaa; font-size: 12px;'>
                        &copy; " . date('Y') . " SNEAKDUP. All Rights Reserved.
                    </p>
                    <p style='margin: 5px 0 0 0; color: #aaaaaa; font-size: 12px;'>
                        Datamex Parañaque Branches
                    </p>
                </div>

            </div>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // Email failed to send
    }
}
?>