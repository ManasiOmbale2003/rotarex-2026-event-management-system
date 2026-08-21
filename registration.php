<?php
// Check if session is already active to prevent "Notice: session_start()"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user wants to reset their session to start over
if(isset($_GET['reset'])){
    session_unset();
    session_destroy();
    header("Location: registration.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

/* ---------- DB CONNECTION ---------- */
include 'config.php'; 

/* ================= 1. PROCESS: SEND OTP (EMAIL + SMS) ================= */
if(isset($_POST['action']) && $_POST['action'] === "send_otp"){

    $fullname   = trim($_POST['fullname']);
    $phone      = trim($_POST['phone']);
    $alt_phone  = trim($_POST['alternate_phone']);
    $email      = strtolower(trim($_POST['email'])); 
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // --- UPDATED PHONE LOGIC: STRIP LEADING 0 ---
    if (substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    if ($alt_phone !== "" && substr($alt_phone, 0, 1) === '0') {
        $alt_phone = substr($alt_phone, 1);
    }

    // Validations
    if(!preg_match("/^([A-Z][a-z]+)(\s[A-Z][a-z]+)+$/", $fullname)) die("Invalid name format");
    if(!preg_match("/^\d{10}$/",$phone)) die("Phone must be 10 digits (excluding leading 0)");
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) die("Invalid Email Format");
    if($password !== $confirm) die("Passwords do not match");

    /* Check existing email */
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $check->store_result();
    if($check->num_rows > 0) die("Email already registered");

    // Generate OTP
    $otp = rand(100000,999999);
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Database Operations for OTP
    $stmt = $conn->prepare("DELETE FROM otp_table WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO otp_table (email, otp, expires_at) VALUES (?,?,?)");
    $stmt->bind_param("sis",$email,$otp,$expires);
    $stmt->execute();

    // Store in Session
    $_SESSION['reg'] = compact('fullname','phone','alt_phone','email','password');
    $_SESSION['show_otp'] = true;

    // -----------------------------------------------------------
    // A. SEND EMAIL (UPDATED: FLYER BACKGROUND DESIGN)
    // -----------------------------------------------------------
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

        $mail->setFrom('rotraxproject@gmail.com','ROTAREX 2026');
        $mail->addAddress($email); 
        
        // --- EMBED IMAGE (Watermark Logic) ---
        $image_path = __DIR__ . '/assets/images/flyer.jpg';
        
        if (file_exists($image_path)) {
            $mail->addEmbeddedImage($image_path, 'flyer_bg');
        } else {
            die("Error: Flyer image not found at " . $image_path);
        }

        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification - ROTAREX 2026';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .otp-text {
                        font-size: 50px !important;
                        color: #FFD700 !important;
                        font-weight: 800 !important;
                        letter-spacing: 5px !important;
                        text-shadow: 2px 2px 5px #000 !important;
                    }
                </style>
            </head>
            <body style='margin:0; padding:0; background-color:#ffffff;'>
                <center>
                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td align='center' valign='top' background='cid:flyer_bg' style='
                            background-image: url(cid:flyer_bg);
                            background-repeat: no-repeat;
                            background-position: center center;
                            background-size: cover;
                            padding: 60px 20px;'>
                            
                            <div style='
                                background-color: rgba(0, 0, 0, 0.75); 
                                max-width: 600px; 
                                width: 100%;
                                margin: 0 auto; 
                                border-radius: 10px; 
                                padding: 40px; 
                                text-align: center;
                                border: 1px solid rgba(255,255,255,0.3);'>
                                
                                <h2 style='
                                    color: #ffffff; 
                                    font-family: Arial, sans-serif;
                                    margin-top: 0; 
                                    font-size: 26px;
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
                                                font-size: 45px; 
                                                color: #FFD700; 
                                                font-weight: bold; 
                                                letter-spacing: 5px;'>
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
                                    ROTAREX 2026 - YSPM Satara
                                </p>
                            </div>
                            
                            </td>
                    </tr>
                </table>
                </center>
            </body>
            </html>";

        $mail->send();

    } catch (Exception $e) {
        die("Mail Error: {$mail->ErrorInfo}. Please check your internet or DNS.");
    }

    // -----------------------------------------------------------
    // B. SEND OTP SMS
    // -----------------------------------------------------------
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
    curl_exec($ch);
    curl_close($ch);

    header("Location: registration.php");
    exit;
}

/* ================= 2. VERIFY OTP & SEND SUCCESS SMS ================= */
if(isset($_POST['action']) && $_POST['action'] === "verify_otp"){
    if(!isset($_SESSION['reg'])) die("Session expired");

    $email = $_SESSION['reg']['email'];
    $otp   = $_POST['otp'];
    $phone = $_SESSION['reg']['phone']; 

    $stmt = $conn->prepare("SELECT otp, expires_at FROM otp_table WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if(!$res || $res['otp'] != $otp || $res['expires_at'] < date("Y-m-d H:i:s")){
        echo "<script>alert('Invalid or expired OTP');</script>";
    } else {
        $data = $_SESSION['reg'];
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert User
        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, alternate_phone, email, password, email_verified) VALUES (?,?,?,?,?,1)");
        $stmt->bind_param("sssss", $data['fullname'], $data['phone'], $data['alt_phone'], $data['email'], $hash);
        
        if($stmt->execute()){
            
            // --- C. SEND SUCCESS SMS ---
            $api_key = "367E3CACE28824";
            $sender_id = "YSPMSR";
            $campaign_id = "14105";
            $route_id = "3";
            $pe_id = "1001539170000013821"; 
            $template_id = "1007072890883656649"; 

            $msg = "Registration completed successfully! Now please complete all remaining steps to finalize your registration for Rotarex 2026. - YSPM Satara";
            $sms_text = urlencode($msg);

            $url = "https://jskbulkmarketing.in/app/smsapi/index.php?key=$api_key&campaign=$campaign_id&routeid=$route_id&type=text&contacts=$phone&senderid=$sender_id&msg=$sms_text&template_id=$template_id&pe_id=$pe_id";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);

            // Cleanup
            $_SESSION['user'] = $data['email'];
            unset($_SESSION['reg']);
            unset($_SESSION['show_otp']);
            unset($_SESSION['otp_resent']);

            // Redirect to Login
            header("Location: login.php");
            exit;
        } else {
            echo "<script>alert('Database Error');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InnovaHub | Register</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{ --purple-main:#a855f7; --text-color:#000; }
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body{ min-height:100vh; display:flex; justify-content:center; align-items:center; background: url("assets/images/img.jpeg") center/cover fixed no-repeat; padding: 15px; }
.form-box{ width:100%; max-width:420px; padding:30px 25px 20px; border-radius:22px; background:rgba(255,255,255,0.12); backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px); border:1px solid rgba(255,255,255,0.2); box-shadow:0 18px 45px rgba(0,0,0,0.55); color:#fff; position: relative; z-index: 1; }
.header-logos { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 10px; }
.header-logos img { height: 50px; width: auto; max-width: 45%; object-fit: contain; }
.college-header{ text-align:center; margin-bottom:10px; }
.college-header h3{ font-size:10px; letter-spacing:0.5px; color:#333; }
.college-header h1{ font-size:16px; font-weight:800; color:#0b5ed7; margin: 2px 0; }
.college-header p{ font-size:10px; color:#333; }
.college-header h4{ font-size:11px; color:#7b2cbf; font-weight:700; }
h2{ text-align:center; margin:5px 0 10px; font-size:22px; font-weight:700; background:linear-gradient(90deg,#9d4edd,#5a189a); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
input{ width:100%; padding:12px; margin:8px 0; border-radius:10px; border:1px solid #000; outline:none; background:rgba(255,255,255,0.2); color:#000; font-size:14px; }
input::placeholder{ color: rgba(0,0,0,0.5); }
button{ width:100%; padding:12px; margin-top:10px; border:none; border-radius:30px; font-weight:600; font-size:14px; background:linear-gradient(45deg,#6a0dad,#b57edc,#ffd700); color:#1a0b2e; cursor:pointer; transition:0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
button:active{ transform:scale(0.98); }
.resend-btn { background: rgba(255,255,255,0.1); color: #000; margin-top: 8px; padding: 10px; border: 1px solid rgba(0,0,0,0.1); }
small{ color:#cc0000; font-size:11px; display: block; height: 14px; padding-left: 5px; }
@media(max-width:480px){ 
    body { align-items: flex-start; padding-top: 30px; }
    .form-box{ padding: 20px 15px; border-radius: 15px; } 
    .header-logos img { height: 45px; } 
    .college-header h1 { font-size: 14px; }
    h2 { font-size: 20px; }
    input { padding: 10px; font-size: 16px; }
}
</style>
</head>
<body>
<div class="form-box">
    
    <div class="header-logos">
        <img src="/rotrax/assets/images/Yashoda.png" alt="Yashoda Logo">
        <img src="/rotrax/assets/images/logooo.png" alt="ROTRAX Logo">
    </div>

    <div class="college-header">
        <h3>Yashoda Shikshan Prasarak Mandal's</h3>
        <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
        <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
        <h4>ROTARY CLUB OF SATARA</h4>
    </div>

    <?php if(isset($_SESSION['show_otp'])): ?>
        <h2>Verify OTP</h2>
        <p style="text-align:center; margin-bottom:10px; font-size: 12px; color:#000;">OTP sent to Email & SMS</p>
        
        <?php if(isset($_SESSION['otp_resent'])): ?>
            <p style="text-align:center; color:#008000; font-size:11px; margin-bottom:5px;">New OTP sent successfully!</p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="verify_otp">
            <input type="text" name="otp" placeholder="Enter 6-digit OTP" required maxlength="6" inputmode="numeric">
            <button type="submit">Verify & Register</button>
        </form>

        <form action="send_otp.php" method="post">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="resend-btn">Resend OTP via Email & SMS</button>
        </form>

        <p style="text-align:center; margin-top:15px;"><a href="?reset=1" style="color:#000; font-size:13px; text-decoration:none; font-weight:600;">Back to Register</a></p>

    <?php else: ?>
        <h2>Registration</h2>
        <form method="post" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="send_otp">
            <input type="text" name="fullname" id="fullname" placeholder="Full Name (Ex: John Smith )" required>
            <small id="nameError"></small>
            
            <input type="text" name="phone" id="phone" placeholder="Phone Number" required inputmode="tel" maxlength="11">
            <small id="phoneError"></small>
            
            <input type="text" name="alternate_phone" id="altPhone" placeholder="Alternate Number" inputmode="tel" maxlength="11">
            <small id="altPhoneError"></small>
            
            <input type="email" name="email" placeholder="Email Address" required>
            <small></small>

            <input type="password" name="password" id="password" placeholder="Create Password" required>
            <small id="passError"></small>
            
            <input type="password" name="confirm_password" id="confirm" placeholder="Confirm Password" required>
            <small id="confirmError"></small>
            
            <button type="submit">Get OTP via Email & SMS</button>
        </form>
    <?php endif; ?>
</div>

<script>
    const fullname = document.getElementById("fullname");
    const nameError = document.getElementById("nameError");
    const phone = document.getElementById("phone");
    const phoneError = document.getElementById("phoneError");
    const altPhone = document.getElementById("altPhone");
    const altPhoneError = document.getElementById("altPhoneError");
    const password = document.getElementById("password");
    const confirm = document.getElementById("confirm");
    const passError = document.getElementById("passError");
    const confirmError = document.getElementById("confirmError");

    // Helper to validate phone with optional leading zero
    function validatePhoneInput(val) {
        let clean = val.replace(/\D/g, '');
        if (clean.startsWith('0')) {
            // Allow 11 digits if starts with 0
            return { valid: clean.length === 11, clean: clean };
        } else {
            // Allow 10 digits if no 0
            return { valid: clean.length === 10, clean: clean };
        }
    }

    if(fullname) {
        fullname.addEventListener("input", () => {
            const pattern = /^([A-Z][a-z]+)(\s[A-Z][a-z]+)+$/;
            nameError.textContent = pattern.test(fullname.value.trim()) ? "" : "First letters must be Capital.";
        });
    }

    if(phone) {
        phone.addEventListener("input", () => {
            const result = validatePhoneInput(phone.value);
            phone.value = result.clean.slice(0, phone.value.startsWith('0') ? 11 : 10);
            const finalResult = validatePhoneInput(phone.value);
            phoneError.textContent = finalResult.valid ? "" : (phone.value.startsWith('0') ? "Enter 10 digits after 0" : "Must be 10 digits");
        });
    }

    if(altPhone) {
        altPhone.addEventListener("input", () => {
            if(altPhone.value.length === 0) {
                altPhoneError.textContent = "";
                return;
            }
            const result = validatePhoneInput(altPhone.value);
            altPhone.value = result.clean.slice(0, altPhone.value.startsWith('0') ? 11 : 10);
            const finalResult = validatePhoneInput(altPhone.value);
            altPhoneError.textContent = finalResult.valid ? "" : (altPhone.value.startsWith('0') ? "Enter 10 digits after 0" : "Must be 10 digits");
        });
    }

    if(password) {
        password.addEventListener("input", () => {
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            passError.textContent = regex.test(password.value) ? "" : "Password like (e.g. Abc@123) .";
            if(confirm.value) {
                confirmError.textContent = password.value === confirm.value ? "" : "Passwords do not match";
            }
        });
    }

    if(confirm) {
        confirm.addEventListener("input", () => {
            confirmError.textContent = password.value === confirm.value ? "" : "Passwords do not match";
        });
    }

    function validateForm(){
        if(nameError.textContent || phoneError.textContent || altPhoneError.textContent || passError.textContent || confirmError.textContent) return false;
        
        if(password.value !== confirm.value) {
            confirmError.textContent = "Passwords do not match";
            return false;
        }
        return true;
    }
</script>
</body>
</html>