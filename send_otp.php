<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

/* ================= DATABASE CONNECTION ================= */
require_once('config.php'); // Included external database configuration

/* ================= CHECK REQUEST ================= */
if(isset($_POST['resend']) && $_POST['resend'] == 1){
    if(!isset($_SESSION['reg']['email'])){
        header("Location: registration.php");
        exit;
    }
    $email = $_SESSION['reg']['email'];
    $fullname = $_SESSION['reg']['fullname'] ?? 'User';
    $phone = $_SESSION['reg']['phone'] ?? ''; 
} else {
    header("Location: registration.php");
    exit;
}

/* ================= OTP CONFIG ================= */
$otp = rand(100000, 999999);
$expires = date("Y-m-d H:i:s", strtotime("+10 minutes")); 

/* ================= DB TRANSACTION ================= */
$del = $conn->prepare("DELETE FROM otp_table WHERE email=?");
$del->bind_param("s", $email);
$del->execute();

$ins = $conn->prepare("INSERT INTO otp_table (email, otp, expires_at) VALUES (?, ?, ?)");
$ins->bind_param("sss", $email, $otp, $expires);
$ins->execute();

/* ================= SEND EMAIL ================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username = '';
    $mail->Password = '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('rotraxproject@gmail.com', 'InnovaHub');
    $mail->addAddress($email);

    // -----------------------------------------------------------
    // 1. IMAGE PATH & EMBED (Absolute Path Logic)
    // -----------------------------------------------------------
    $image_path = __DIR__ . '/assets/images/flyer.jpg';
    
    // Check if file really exists
    if (!file_exists($image_path)) {
        die("Error: Image File Not Found at: " . $image_path);
    }

    $mail->addEmbeddedImage($image_path, 'flyer_bg');

    $mail->isHTML(true);
    $mail->Subject = 'OTP Verification - InnovaHub';

    // -----------------------------------------------------------
    // 2. HTML BODY (Optimized for Mobile View Responsiveness)
    // -----------------------------------------------------------
    $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                @media only screen and (max-width: 600px) {
                    .container { width: 100% !important; padding: 20px !important; }
                    .otp-text { font-size: 35px !important; letter-spacing: 3px !important; }
                    .hero-cell { padding: 30px 10px !important; }
                }
                .otp-text {
                    font-size: 50px;
                    color: #FFD700 !important;
                    font-weight: 800;
                    letter-spacing: 5px;
                    text-shadow: 2px 2px 5px #000;
                }
            </style>
        </head>
        <body style='margin:0; padding:0; background-color:#ffffff;'>
            
            <center>
            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center' valign='top' background='cid:flyer_bg' class='hero-cell' style='
                        background-image: url(cid:flyer_bg);
                        background-repeat: no-repeat;
                        background-position: center center;
                        background-size: cover;
                        padding: 60px 20px;'>
                        
                        <div class='container' style='
                            background-color: rgba(0, 0, 0, 0.82); 
                            max-width: 600px; 
                            width: 100%;
                            margin: 0 auto; 
                            border-radius: 12px; 
                            padding: 40px; 
                            text-align: center;
                            border: 1px solid rgba(255,255,255,0.3);'>
                            
                            <h2 style='
                                color: #ffffff; 
                                font-family: Arial, sans-serif;
                                margin-top: 0; 
                                font-size: 24px;
                                text-transform: uppercase; 
                                font-weight: 800;
                                text-shadow: 2px 2px 4px #000;'>
                                NEW OTP VERIFICATION
                            </h2>

                            <p style='
                                font-family: Arial, sans-serif;
                                font-size: 18px; 
                                color: #ffffff; 
                                margin-top: 20px; 
                                text-shadow: 1px 1px 2px #000;'>
                                Hello $fullname,
                            </p>

                            <p style='
                                font-family: Arial, sans-serif;
                                font-size: 15px; 
                                color: #eeeeee; 
                                margin-bottom: 25px;'>
                                You requested a new OTP. Use the code below:
                            </p>

                            <table border='0' cellspacing='0' cellpadding='0' align='center' style='margin: 20px auto;'>
                                <tr>
                                    <td align='center' style='
                                        border: 3px dashed #FFD700; 
                                        border-radius: 10px; 
                                        padding: 15px 30px; 
                                        background-color: rgba(0,0,0,0.4);'>
                                        
                                        <span class='otp-text' style='
                                            font-family: Arial, sans-serif;
                                            color: #FFD700; 
                                            font-weight: bold;'>
                                            $otp
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <p style='
                                font-family: Arial, sans-serif;
                                color: #ffffff; 
                                font-size: 14px; 
                                margin-top: 25px;'>
                                This OTP is valid for <b>10 minutes</b>.
                            </p>

                            <hr style='border: 0; border-top: 1px solid rgba(255,255,255,0.3); margin: 30px 0;'>

                            <p style='
                                font-family: Arial, sans-serif;
                                font-size: 12px; 
                                color: #ccc;'>
                                InnovaHub - YSPM Satara
                            </p>
                        </div>
                        
                        </td>
                </tr>
            </table>
            </center>

        </body>
        </html>
    ";

    $mail->send();
    
    // Set flag for the main page to show success message
    $_SESSION['show_otp'] = true;
    $_SESSION['otp_resent'] = true;

    /* ================= SEND SMS (UNCHANGED) ================= */
    if(!empty($phone) && strlen($phone) == 10) {
        
        $api_key = "367E3CACE28824";
        $sender_id = "YSPMSR";
        $campaign_id = "14105";
        $route_id = "3";
        $pe_id = "1001539170000013821";
        $template_id = "1007435206259983287";

        $raw_message = "Your OTP for registration is $otp. This OTP is valid for 5 minutes. Do not share it with anyone. - YSPM Satara";
        $sms_text = urlencode($raw_message);

        $url = "https://jskbulkmarketing.in/app/smsapi/index.php?key=$api_key&campaign=$campaign_id&routeid=$route_id&type=text&contacts=$phone&senderid=$sender_id&msg=$sms_text&template_id=$template_id&pe_id=$pe_id";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $sms_response = curl_exec($ch);
        curl_close($ch);
    }

    // Redirect back to registration.php
    header("Location: registration.php");
    exit;

} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>